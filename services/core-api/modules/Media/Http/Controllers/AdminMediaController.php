<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Media\Application\LadderAssembler;
use Modules\Media\Application\PublicationService;
use Modules\Media\Contracts\DeliveryPolicy;
use Modules\Media\Contracts\ManifestStore;
use Modules\Media\Contracts\OriginAddressing;
use Modules\Media\Domain\AudioRendition;
use Modules\Media\Domain\EncoderPolicy;
use Modules\Media\Domain\FfmpegLadderCommand;
use Modules\Media\Domain\FrameRate;
use Modules\Media\Domain\Ladder;
use Modules\Media\Domain\PackagingProfile;
use Modules\Media\Domain\VideoCodec;
use Modules\Media\Infrastructure\Eloquent\DeviceProfile;
use Modules\Media\Infrastructure\Eloquent\EncodingLadder;
use Modules\Media\Infrastructure\Eloquent\PackagingProfileRow;
use Modules\Media\Infrastructure\Eloquent\Publication;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\CursorList;
use Modules\Shared\Http\ErrorCode;

/**
 * The media control plane's admin surface.
 *
 * Everything here is configuration of the media plane. Nothing here encodes,
 * packages or serves anything: the pipeline that consumes this configuration
 * runs outside this service, and this repository's environment has neither
 * FFmpeg nor a container runtime to run it with (inspection findings E1, E3).
 */
final class AdminMediaController
{
    public function __construct(
        private readonly AuditRecorder $audit,
        private readonly EncoderPolicy $encoders,
        private readonly LadderAssembler $assembler,
        private readonly PublicationService $publications,
        private readonly ManifestStore $manifests,
    ) {}

    public function storeLadder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique(EncodingLadder::class, 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'content_class' => ['required', 'string', Rule::in(['news', 'sport', 'film', 'series', 'generic'])],

            'rungs' => ['required', 'array', 'min:1'],
            'rungs.*.label' => ['required', 'string', 'max:40'],
            'rungs.*.width' => ['required', 'integer', 'min:2', 'max:8192'],
            'rungs.*.height' => ['required', 'integer', 'min:2', 'max:4320'],
            'rungs.*.video_bitrate_kbps' => ['required', 'integer', 'min:1'],
            'rungs.*.max_bitrate_kbps' => ['required', 'integer', 'min:1'],
            'rungs.*.codec' => ['required', 'string', Rule::in(VideoCodec::ALL)],
            'rungs.*.encoder' => ['required', 'string', 'max:40'],
            'rungs.*.profile' => ['required', 'string', 'max:20'],
            'rungs.*.level' => ['required', 'integer', 'min:10', 'max:62'],
            'rungs.*.frame_rate' => ['required', 'string', 'max:20'],
            'rungs.*.codec_string' => ['sometimes', 'nullable', 'string', 'max:60'],

            'audio' => ['required', 'array', 'min:1'],
            'audio.*.label' => ['required', 'string', 'max:40'],
            'audio.*.language' => ['required', 'string', 'max:12'],
            'audio.*.role' => ['required', 'string', Rule::in(AudioRendition::ROLES)],
            'audio.*.codec' => ['required', 'string', 'max:20'],
            'audio.*.bitrate_kbps' => ['required', 'integer', 'min:1'],
            'audio.*.channels' => ['required', 'integer', 'min:1', 'max:16'],
            'audio.*.sample_rate_hz' => ['required', 'integer'],
            'audio.*.source_stream_index' => ['required', 'integer', 'min:0'],
            'audio.*.is_default' => ['required', 'boolean'],
            'audio.*.codec_string' => ['sometimes', 'nullable', 'string', 'max:60'],
        ]);

        /*
         * The licence gate, before anything is written.
         *
         * An encoder with no recorded clearance is refused rather than
         * defaulted: FFmpeg's licence follows its build flags and the common
         * AVC/HEVC encoders are GPL-or-commercial, so "it is the standard
         * choice" is not a reason to run one (CLAUDE.md §1.4).
         */
        foreach ($validated['rungs'] as $rung) {
            if (! $this->encoders->permits((string) $rung['encoder'], (string) $rung['codec'])) {
                throw ApiProblem::of(ErrorCode::MediaEncoderNotPermitted, [
                    'encoder' => $rung['encoder'],
                    'codec' => $rung['codec'],
                    'permitted' => $this->encoders->encoders(),
                    'detail' => $this->encoders->isEmpty()
                        ? 'No encoder licence clearance has been recorded for this deployment.'
                        : 'This encoder has no recorded licence clearance for this codec.',
                ]);
            }
        }

        $ladder = DB::transaction(function () use ($validated): EncodingLadder {
            $ladder = EncodingLadder::query()->create([
                'uuid' => (string) Str::uuid7(),
                'slug' => $validated['slug'],
                'name' => $validated['name'],
                'content_class' => $validated['content_class'],
                'status' => 'active',
            ]);

            foreach (array_values($validated['rungs']) as $position => $rung) {
                $rate = $this->frameRateParts((string) $rung['frame_rate']);

                $ladder->rungs()->create([
                    'label' => $rung['label'],
                    'position' => $position,
                    'width' => $rung['width'],
                    'height' => $rung['height'],
                    'video_bitrate_kbps' => $rung['video_bitrate_kbps'],
                    'max_bitrate_kbps' => $rung['max_bitrate_kbps'],
                    'codec' => $rung['codec'],
                    'encoder' => $rung['encoder'],
                    'profile' => $rung['profile'],
                    'level' => $rung['level'],
                    'frame_rate_numerator' => $rate[0],
                    'frame_rate_denominator' => $rate[1],
                    'codec_string' => $rung['codec_string'] ?? null,
                ]);
            }

            foreach ($validated['audio'] as $rendition) {
                $ladder->audio()->create([
                    'label' => $rendition['label'],
                    'language' => $rendition['language'],
                    'role' => $rendition['role'],
                    'codec' => $rendition['codec'],
                    'bitrate_kbps' => $rendition['bitrate_kbps'],
                    'channels' => $rendition['channels'],
                    'sample_rate_hz' => $rendition['sample_rate_hz'],
                    'source_stream_index' => $rendition['source_stream_index'],
                    'is_default' => $rendition['is_default'],
                    'codec_string' => $rendition['codec_string'] ?? null,
                ]);
            }

            /*
             * Assembled before the transaction commits, so every ladder
             * invariant is enforced against what was actually written rather
             * than against what was requested. A ladder that fails here is
             * rolled back rather than stored in a state that would break a
             * manifest later.
             */
            $this->assembleOrFail($ladder->fresh(['rungs', 'audio']));

            return $ladder;
        });

        $this->audit->record(AuditEvent::byStaff(
            $this->staffUuid($request),
            'media.ladder.created',
            'encoding_ladder',
            (string) $ladder->uuid,
            ['slug' => $ladder->slug, 'rungs' => count($validated['rungs'])],
        ));

        return new JsonResponse(['data' => $this->ladderPayload($ladder->fresh(['rungs', 'audio']))], 201);
    }

    public function listLadders(): JsonResponse
    {
        $ladders = EncodingLadder::query()->with(['rungs', 'audio'])->orderBy('slug')->get();

        return new JsonResponse([
            'data' => $ladders->map(fn (EncodingLadder $ladder): array => $this->ladderPayload($ladder))->all(),
        ]);
    }

    public function listPackagingProfiles(Request $request): JsonResponse
    {
        $query = PackagingProfileRow::query()->orderBy('slug')->orderBy('id');

        return new JsonResponse(CursorList::respond($request, $query, $this->packagingPayload(...)));
    }

    public function listPublications(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', 'string', 'max:20'],
        ]);

        $query = Publication::query()->with(['ladder', 'packaging', 'manifests'])->orderByDesc('id');

        if ($request->filled('subject_id')) {
            $query->where('subject_ref', $request->string('subject_id')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return new JsonResponse(CursorList::respond($request, $query, function (Publication $publication): array {
            return $this->publicationPayload($publication) + [
                'ladder' => $publication->ladder === null ? null : [
                    'id' => $publication->ladder->uuid,
                    'slug' => $publication->ladder->slug,
                ],
                'packaging_profile' => $publication->packaging === null ? null : [
                    'id' => $publication->packaging->uuid,
                    'slug' => $publication->packaging->slug,
                    'container' => $publication->packaging->container,
                ],
                /*
                 * The manifests that were actually written, with their content
                 * hashes. An operator asking "did that republish change
                 * anything?" is answered from the hash rather than by diffing
                 * two manifests by eye.
                 */
                'manifests' => $publication->manifests
                    ->sortBy(['policy', 'format'])
                    ->map(static fn ($manifest): array => [
                        'policy' => $manifest->policy,
                        'format' => $manifest->format,
                        'content_hash' => $manifest->content_hash,
                        'byte_size' => (int) $manifest->byte_size,
                        'generated_at' => $manifest->generated_at?->format(DATE_RFC3339),
                    ])->values()->all(),
            ];
        }));
    }

    public function storePackagingProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique(PackagingProfileRow::class, 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'container' => ['required', 'string', Rule::in(PackagingProfile::CONTAINERS)],
            'segment_duration_ms' => ['required', 'integer', 'min:200', 'max:30000'],
            'gop_duration_ms' => ['required', 'integer', 'min:200', 'max:30000'],
            'timescale' => ['required', 'integer', 'min:1'],
            'playlist_window_segments' => ['required', 'integer', 'min:3', 'max:600'],
            'time_shift_buffer_seconds' => ['required', 'integer', 'min:1'],
            'suggested_presentation_delay_ms' => ['required', 'integer', 'min:0'],
        ]);

        // Constructed before it is stored: the packaging invariants describe
        // encodes that cannot be produced, and storing one would only move the
        // failure to publication time.
        $this->packagingOrFail($validated);

        $row = PackagingProfileRow::query()->create([
            'uuid' => (string) Str::uuid7(),
            ...$validated,
            // Absent until Phase 6, and absent means unencrypted rather than
            // "encrypted with something not yet built".
            'encryption_scheme' => null,
        ]);

        $this->audit->record(AuditEvent::byStaff(
            $this->staffUuid($request),
            'media.packaging_profile.created',
            'packaging_profile',
            (string) $row->uuid,
            ['slug' => $row->slug, 'container' => $row->container],
        ));

        return new JsonResponse(['data' => $this->packagingPayload($row)], 201);
    }

    public function storePublication(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', 'string', Rule::in(['channel'])],
            'subject_id' => ['required', 'uuid'],
            'ladder_id' => ['required', 'uuid'],
            'packaging_profile_id' => ['required', 'uuid'],
        ]);

        $ladder = EncodingLadder::query()->where('uuid', $validated['ladder_id'])->first();

        if ($ladder === null) {
            throw ApiProblem::of(ErrorCode::MediaLadderNotFound);
        }

        $packaging = PackagingProfileRow::query()->where('uuid', $validated['packaging_profile_id'])->first();

        if ($packaging === null) {
            throw ApiProblem::of(ErrorCode::MediaPackagingProfileNotFound);
        }

        $origin = OriginAddressing::forPublication($validated['subject_type'], $validated['subject_id']);

        $publication = Publication::query()->updateOrCreate(
            ['origin_prefix' => $origin->prefix],
            [
                'uuid' => (string) Str::uuid7(),
                'subject_type' => $validated['subject_type'],
                'subject_ref' => $validated['subject_id'],
                'ladder_id' => $ladder->id,
                'packaging_profile_id' => $packaging->id,
                'status' => 'draft',
            ],
        );

        $this->audit->record(AuditEvent::byStaff(
            $this->staffUuid($request),
            'media.publication.created',
            'publication',
            (string) $publication->uuid,
            ['subject_ref' => $publication->subject_ref, 'ladder' => $ladder->slug],
        ));

        return new JsonResponse(['data' => $this->publicationPayload($publication)], 201);
    }

    public function publish(Request $request, string $publicationId): JsonResponse
    {
        $publication = $this->publicationOrFail($publicationId);

        $result = $this->publications->publish($publication);

        $this->audit->record(AuditEvent::byStaff(
            $this->staffUuid($request),
            'media.publication.published',
            'publication',
            (string) $publication->uuid,
            [
                'revision' => $result['revision'],
                // The hashes make a republication that changed nothing visible
                // as such, which matters because a manifest change is a client
                // contract change.
                'manifest_hashes' => array_map(
                    static fn (array $m): string => $m['policy'].'/'.$m['format'].':'.substr((string) $m['content_hash'], 0, 12),
                    $result['manifests'],
                ),
            ],
        ));

        return new JsonResponse([
            'data' => $this->publicationPayload($publication->fresh(['packaging'])) + [
                'manifests' => array_map(static fn (array $m): array => [
                    'policy' => $m['policy'],
                    'format' => $m['format'],
                    'path' => $m['storage_path'],
                    'content_hash' => $m['content_hash'],
                    'byte_size' => $m['byte_size'],
                ], $result['manifests']),
            ],
        ], 201);
    }

    public function retire(Request $request, string $publicationId): JsonResponse
    {
        $publication = $this->publicationOrFail($publicationId);

        $this->publications->retire($publication);

        $this->audit->record(AuditEvent::byStaff(
            $this->staffUuid($request),
            'media.publication.retired',
            'publication',
            (string) $publication->uuid,
        ));

        return new JsonResponse(null, 204);
    }

    /**
     * The generated manifest itself.
     *
     * Manifests are a client contract, so an operator has to be able to read
     * the exact bytes a player will receive — not a rendering of them.
     */
    public function showManifest(string $publicationId, string $policy, string $format): JsonResponse|Response
    {
        $publication = $this->publicationOrFail($publicationId);

        if (! DeliveryPolicy::isKnownSlug($policy) || ! in_array($format, ['hls', 'dash'], true)) {
            throw ApiProblem::of(ErrorCode::MediaManifestNotFound);
        }

        $path = OriginAddressing::fromPrefix((string) $publication->origin_prefix)
            ->manifestPath(DeliveryPolicy::fromSlug($policy), $format);

        $contents = $this->manifests->get($path);

        if ($contents === null) {
            throw ApiProblem::of(ErrorCode::MediaManifestNotFound);
        }

        return response($contents, 200, [
            'Content-Type' => $format === 'hls' ? 'application/vnd.apple.mpegurl' : 'application/dash+xml',
            // An operator inspecting a manifest must never be served a stale
            // copy from anything between them and this response.
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * The exact FFmpeg encode arguments for a ladder.
     *
     * A real operational need — "what will actually run for this channel?" —
     * answered from the same code path the pipeline uses, so the answer cannot
     * drift from the behaviour.
     */
    public function ffmpegCommand(Request $request, string $ladderId): JsonResponse
    {
        $validated = $request->validate([
            'packaging_profile_id' => ['required', 'uuid'],
            'input_url' => ['required', 'string', 'max:500'],
        ]);

        $ladderRow = EncodingLadder::query()->with(['rungs', 'audio'])->where('uuid', $ladderId)->first();

        if ($ladderRow === null) {
            throw ApiProblem::of(ErrorCode::MediaLadderNotFound);
        }

        $packagingRow = PackagingProfileRow::query()->where('uuid', $validated['packaging_profile_id'])->first();

        if ($packagingRow === null) {
            throw ApiProblem::of(ErrorCode::MediaPackagingProfileNotFound);
        }

        $command = new FfmpegLadderCommand(
            $this->assembleOrFail($ladderRow),
            $this->assembler->packaging($packagingRow),
            $validated['input_url'],
        );

        return new JsonResponse([
            'data' => [
                'arguments' => $command->arguments(),
                /*
                 * The muxer stage is absent, not omitted for brevity. Packager
                 * selection is an open decision with a licence gate on it, and
                 * emitting muxing flags for an unchosen packager would be
                 * writing against a guess. The response says so rather than
                 * leaving an operator to discover a command that will not run.
                 */
                'output_stage' => null,
                'output_stage_note' => 'No packager has been selected (OQ-8 and the P3 verification checklist). '
                    .'These arguments are the encode stage only and are not runnable without a muxer.',
                'requires_gpl_build' => $this->encoders->requiresGplBuild(),
            ],
        ]);
    }

    public function upsertDeviceProfile(Request $request, string $deviceClass): JsonResponse
    {
        $validated = $request->validate([
            'container' => ['required', 'string', Rule::in(PackagingProfile::CONTAINERS)],
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => ['string', Rule::in(['hls', 'dash'])],
            'max_height' => ['sometimes', 'nullable', 'integer', 'min:144', 'max:4320'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (! in_array($deviceClass, (array) config('kms.clients.platforms'), true)) {
            throw ApiProblem::of(ErrorCode::DeviceClassUnsupported, ['device_class' => $deviceClass]);
        }

        $row = DeviceProfile::query()->updateOrCreate(
            ['device_class' => $deviceClass],
            [
                'container' => $validated['container'],
                'formats' => array_values($validated['formats']),
                'max_height' => $validated['max_height'] ?? null,
                'note' => $validated['note'] ?? null,
            ],
        );

        $this->audit->record(AuditEvent::byStaff(
            $this->staffUuid($request),
            'media.device_profile.updated',
            'device_profile',
            $deviceClass,
            ['container' => $row->container, 'formats' => $row->formats, 'max_height' => $row->max_height],
        ));

        return new JsonResponse(['data' => [
            'device_class' => $row->device_class,
            'container' => $row->container,
            'formats' => $row->formats,
            'max_height' => $row->max_height,
        ]]);
    }

    private function publicationOrFail(string $publicationId): Publication
    {
        $publication = Publication::query()
            ->with(['ladder.rungs', 'ladder.audio', 'packaging'])
            ->where('uuid', $publicationId)
            ->first();

        if ($publication === null) {
            throw ApiProblem::of(ErrorCode::MediaPublicationNotFound);
        }

        return $publication;
    }

    private function assembleOrFail(EncodingLadder $ladder): Ladder
    {
        try {
            return $this->assembler->ladder($ladder);
        } catch (InvalidArgumentException $e) {
            // The domain message names the rung and the defect, which is what
            // an operator needs; it discloses nothing an operator may not see.
            throw ApiProblem::of(ErrorCode::MediaLadderInvalid, ['detail' => $e->getMessage()]);
        }
    }

    /** @param array<string,mixed> $validated */
    private function packagingOrFail(array $validated): PackagingProfile
    {
        try {
            return new PackagingProfile(
                slug: (string) $validated['slug'],
                container: (string) $validated['container'],
                segmentDurationMs: (int) $validated['segment_duration_ms'],
                gopDurationMs: (int) $validated['gop_duration_ms'],
                timescale: (int) $validated['timescale'],
                playlistWindowSegments: (int) $validated['playlist_window_segments'],
                timeShiftBufferSeconds: (int) $validated['time_shift_buffer_seconds'],
                suggestedPresentationDelayMs: (int) $validated['suggested_presentation_delay_ms'],
            );
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::of(ErrorCode::MediaSegmentAlignmentInvalid, ['detail' => $e->getMessage()]);
        }
    }

    /** @return array{0:int,1:int} */
    private function frameRateParts(string $frameRate): array
    {
        try {
            $rate = FrameRate::fromString($frameRate);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::of(ErrorCode::MediaLadderInvalid, ['detail' => $e->getMessage()]);
        }

        return [$rate->numerator, $rate->denominator];
    }

    /** @return array<string,mixed> */
    private function ladderPayload(EncodingLadder $ladder): array
    {
        return [
            'id' => $ladder->uuid,
            'slug' => $ladder->slug,
            'name' => $ladder->name,
            'content_class' => $ladder->content_class,
            'status' => $ladder->status,
            'rungs' => $ladder->rungs->map(static fn ($rung): array => [
                'label' => $rung->label,
                'width' => (int) $rung->width,
                'height' => (int) $rung->height,
                'video_bitrate_kbps' => (int) $rung->video_bitrate_kbps,
                'max_bitrate_kbps' => (int) $rung->max_bitrate_kbps,
                'codec' => $rung->codec,
                'encoder' => $rung->encoder,
                'frame_rate' => $rung->frame_rate_denominator === 1
                    ? (string) $rung->frame_rate_numerator
                    : $rung->frame_rate_numerator.'/'.$rung->frame_rate_denominator,
            ])->all(),
            'audio' => $ladder->audio->map(static fn ($rendition): array => [
                'label' => $rendition->label,
                'language' => $rendition->language,
                'role' => $rendition->role,
                'bitrate_kbps' => (int) $rendition->bitrate_kbps,
                'channels' => (int) $rendition->channels,
                'is_default' => (bool) $rendition->is_default,
            ])->all(),
        ];
    }

    /** @return array<string,mixed> */
    private function packagingPayload(PackagingProfileRow $row): array
    {
        return [
            'id' => $row->uuid,
            'slug' => $row->slug,
            'name' => $row->name,
            'container' => $row->container,
            'segment_duration_ms' => (int) $row->segment_duration_ms,
            'gop_duration_ms' => (int) $row->gop_duration_ms,
            // Null means unencrypted. DRM is Phase 6 and is absent rather than
            // represented by a plausible value.
            'encryption_scheme' => $row->encryption_scheme,
        ];
    }

    /** @return array<string,mixed> */
    private function publicationPayload(Publication $publication): array
    {
        return [
            'id' => $publication->uuid,
            'subject_type' => $publication->subject_type,
            'subject_id' => $publication->subject_ref,
            'origin_prefix' => $publication->origin_prefix,
            'status' => $publication->status,
            'revision' => (int) $publication->revision,
            'available_from' => $publication->available_from?->format(DATE_RFC3339),
            'published_at' => $publication->published_at?->format(DATE_RFC3339),
        ];
    }

    private function staffUuid(Request $request): string
    {
        return (string) $request->attributes->get('kms.staff_uuid');
    }
}
