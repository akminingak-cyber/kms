<?php

declare(strict_types=1);

namespace Modules\Schedule\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Schedule\Infrastructure\Eloquent\Channel;
use Modules\Schedule\Infrastructure\Eloquent\IngestRun;
use Modules\Schedule\Infrastructure\Eloquent\Programme;
use Modules\Shared\Domain\Clock;

final class AdminScheduleController
{
    public function __construct(
        private readonly Clock $clock,
        private readonly AuditRecorder $audit,
    ) {}

    public function storeChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:60', Rule::unique(Channel::class, 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'number' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'category_id' => ['sometimes', 'nullable', 'uuid'],
            'catchup_enabled' => ['sometimes', 'boolean'],
        ]);

        $channel = Channel::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'number' => $validated['number'] ?? null,
            'category_uuid' => $validated['category_id'] ?? null,
            // Default false: a channel may not be recorded until somebody says
            // the recording right exists.
            'catchup_enabled' => $validated['catchup_enabled'] ?? false,
        ]);

        $this->audit->record(AuditEvent::byStaff(
            (string) $request->attributes->get('kms.staff_uuid'),
            'schedule.channel.created',
            'channel',
            $channel->uuid,
            ['slug' => $channel->slug],
        ));

        return new JsonResponse(['data' => ['id' => $channel->uuid, 'slug' => $channel->slug]], 201);
    }

    /**
     * Ingest a schedule.
     *
     * The provider's raw payload is retained on the run, because providers issue
     * corrections and without the original a mis-mapping cannot be diagnosed or
     * reprocessed.
     *
     * Reconciliation is by `(source, source_ref)`: a corrected programme updates
     * in place and bumps its revision rather than appearing twice.
     */
    public function ingest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:60'],
            'programmes' => ['required', 'array', 'min:1', 'max:5000'],
            'programmes.*.channel_id' => ['required', 'uuid'],
            'programmes.*.source_ref' => ['required', 'string', 'max:120'],
            'programmes.*.title' => ['required', 'string', 'max:300'],
            'programmes.*.starts_at' => ['required', 'date'],
            'programmes.*.ends_at' => ['required', 'date'],
            'programmes.*.subtitle' => ['sometimes', 'nullable', 'string', 'max:300'],
            'programmes.*.description' => ['sometimes', 'nullable', 'string'],
            'programmes.*.category_id' => ['sometimes', 'nullable', 'uuid'],
            'programmes.*.age_rating' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $now = $this->clock->now();
        $knownChannels = Channel::query()->pluck('uuid')->flip();

        $applied = 0;
        $rejected = [];

        $run = IngestRun::query()->create([
            'uuid' => (string) Str::uuid7(),
            'source' => $validated['source'],
            'status' => 'running',
            'programmes_received' => count($validated['programmes']),
            'payload' => $validated['programmes'],
            'received_at' => $now,
        ]);

        DB::transaction(function () use ($validated, $knownChannels, $now, &$applied, &$rejected): void {
            foreach ($validated['programmes'] as $index => $row) {
                $startsAt = new DateTimeImmutable($row['starts_at']);
                $endsAt = new DateTimeImmutable($row['ends_at']);

                // A malformed row is rejected individually and recorded, so one
                // bad entry never costs the whole feed.
                if ($endsAt <= $startsAt) {
                    $rejected[] = ['index' => $index, 'reason' => 'ends_before_start'];

                    continue;
                }

                if (! $knownChannels->has($row['channel_id'])) {
                    $rejected[] = ['index' => $index, 'reason' => 'unknown_channel'];

                    continue;
                }

                $existing = Programme::query()
                    ->where('source', $validated['source'])
                    ->where('source_ref', $row['source_ref'])
                    ->first();

                $attributes = [
                    'channel_uuid' => $row['channel_id'],
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'] ?? null,
                    'description' => $row['description'] ?? null,
                    'category_uuid' => $row['category_id'] ?? null,
                    'age_rating' => $row['age_rating'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'source' => $validated['source'],
                    'source_ref' => $row['source_ref'],
                    'updated_at' => $now,
                ];

                if ($existing !== null) {
                    // A correction bumps the revision. The partition key cannot
                    // change in place, so a moved programme is replaced.
                    if (! $existing->starts_at->equalTo($startsAt)) {
                        $existing->delete();
                        Programme::query()->create($attributes + [
                            'uuid' => (string) Str::uuid7(),
                            'revision' => (int) $existing->revision + 1,
                            'created_at' => $now,
                        ]);
                    } else {
                        $existing->forceFill($attributes + ['revision' => (int) $existing->revision + 1])->save();
                    }
                } else {
                    Programme::query()->create($attributes + [
                        'uuid' => (string) Str::uuid7(),
                        'revision' => 1,
                        'created_at' => $now,
                    ]);
                }

                $applied++;
            }
        });

        $run->forceFill([
            'status' => 'completed',
            'programmes_applied' => $applied,
            'programmes_rejected' => count($rejected),
            'errors' => $rejected === [] ? null : $rejected,
            'completed_at' => $this->clock->now(),
        ])->save();

        return new JsonResponse([
            'data' => [
                'run_id' => $run->uuid,
                'received' => count($validated['programmes']),
                'applied' => $applied,
                'rejected' => count($rejected),
                'errors' => $rejected,
            ],
        ], 202);
    }
}
