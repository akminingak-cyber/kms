<?php

declare(strict_types=1);

namespace Modules\Media\Application;

use Illuminate\Support\Facades\DB;
use Modules\Media\Contracts\DeliveryPolicy;
use Modules\Media\Contracts\ManifestStore;
use Modules\Media\Contracts\OriginAddressing;
use Modules\Media\Domain\DashManifestWriter;
use Modules\Media\Domain\HlsMultivariantWriter;
use Modules\Media\Domain\Ladder;
use Modules\Media\Domain\PackagingProfile;
use Modules\Media\Infrastructure\Eloquent\Publication;
use Modules\Media\Infrastructure\Eloquent\PublicationManifest;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * Publishing a channel: validate the encode, generate the manifests, record what
 * was generated.
 *
 * A manifest is generated for **every delivery-policy class**, not for every
 * viewer. That is what lets a licensor's resolution cap be enforced server-side
 * — the capped viewer receives a manifest that does not mention the rungs they
 * may not have — while keeping the object cacheable across every viewer who
 * shares that cap (ADR-0012).
 */
final readonly class PublicationService
{
    public function __construct(
        private Clock $clock,
        private LadderAssembler $assembler,
        private ManifestStore $store,
        private HlsMultivariantWriter $hls,
        private DashManifestWriter $dash,
    ) {}

    /**
     * @return array{revision:int,manifests:list<array<string,mixed>>}
     */
    public function publish(Publication $publication): array
    {
        $ladder = $this->assembler->ladder($publication->ladder);
        $packaging = $this->assembler->packaging($publication->packaging);

        $this->assertEncodeIsProducible($ladder, $packaging);

        $now = $this->clock->now();
        $origin = OriginAddressing::fromPrefix((string) $publication->origin_prefix);

        /*
         * The presentation timeline starts when the channel first goes live and
         * does not restart on republication. A DASH player converts wall-clock
         * time into a segment number from this instant, so moving it would jump
         * every player watching to a different point in the stream.
         */
        $availableFrom = $publication->available_from?->toDateTimeImmutable() ?? $now;
        $revision = (int) $publication->revision + 1;

        $generated = [];

        foreach (DeliveryPolicy::all() as $policy) {
            foreach (['hls', 'dash'] as $format) {
                $path = $origin->manifestPath($policy, $format);

                $contents = $format === 'hls'
                    ? $this->hls->write($ladder, $packaging, $policy, $origin)
                    : $this->dash->write($ladder, $packaging, $policy, $origin, $availableFrom, $now);

                $this->store->put($path, $contents);

                $generated[] = [
                    'policy' => $policy->slug,
                    'format' => $format,
                    'storage_path' => $path,
                    'content_hash' => hash('sha256', $contents),
                    'byte_size' => strlen($contents),
                    'revision' => $revision,
                    'generated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($publication, $generated, $revision, $now, $availableFrom): void {
            foreach ($generated as $manifest) {
                PublicationManifest::query()->updateOrCreate(
                    [
                        'publication_id' => $publication->id,
                        'policy' => $manifest['policy'],
                        'format' => $manifest['format'],
                    ],
                    $manifest,
                );
            }

            $publication->forceFill([
                'status' => 'published',
                'revision' => $revision,
                'available_from' => $availableFrom,
                'published_at' => $now,
                'retired_at' => null,
            ])->save();
        });

        return ['revision' => $revision, 'manifests' => $generated];
    }

    public function retire(Publication $publication): void
    {
        DB::transaction(function () use ($publication): void {
            $publication->forceFill([
                'status' => 'retired',
                'retired_at' => $this->clock->now(),
            ])->save();
        });

        /*
         * Manifests are removed, segments are not. The origin is the system of
         * record for media and a retired channel is frequently un-retired;
         * deleting the media on a status change would make that unrecoverable.
         * Removing the manifests is enough to stop new playback finding it.
         */
        $this->store->delete((string) $publication->origin_prefix);
    }

    /**
     * The cross-object invariant neither the ladder nor the profile can check
     * alone: a GOP must be a whole number of frames **at this ladder's frame
     * rate**.
     *
     * If it is not, the encoder rounds — and rounds differently for a rung
     * running at half rate — so IDR frames drift apart between renditions and
     * ABR switching starts producing artefacts that no single-rendition test
     * will ever show.
     */
    private function assertEncodeIsProducible(Ladder $ladder, PackagingProfile $packaging): void
    {
        foreach ($ladder->rungs as $rung) {
            if (! $packaging->gopIsWholeFrames($rung->frameRate)) {
                throw ApiProblem::of(ErrorCode::MediaSegmentAlignmentInvalid, [
                    'rung' => $rung->label,
                    'frame_rate' => $rung->frameRate->toString(),
                    'gop_duration_ms' => $packaging->gopDurationMs,
                    'detail' => 'The GOP duration is not a whole number of frames at this rung\'s frame rate, '
                        .'so IDR frames would not align across renditions.',
                ]);
            }
        }
    }
}
