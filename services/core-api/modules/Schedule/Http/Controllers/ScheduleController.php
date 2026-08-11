<?php

declare(strict_types=1);

namespace Modules\Schedule\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Schedule\Infrastructure\Eloquent\Channel;
use Modules\Schedule\Infrastructure\Eloquent\Programme;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * The EPG.
 *
 * Time-range rather than cursor pagination, deliberately: a schedule query is
 * `channel × window` over a time-partitioned table, not an open-ended scroll.
 * Forcing a cursor onto it would buy nothing and make the most cacheable
 * endpoint in the product harder to cache.
 *
 * The range is bounded, because an unbounded one would let a single request
 * scan every partition.
 */
final class ScheduleController
{
    public function __construct(private readonly Clock $clock) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_ids' => ['sometimes', 'array', 'max:100'],
            'channel_ids.*' => ['uuid'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $from = isset($validated['from'])
            ? new DateTimeImmutable($validated['from'])
            : $this->clock->now();

        $to = isset($validated['to'])
            ? new DateTimeImmutable($validated['to'])
            : $from->modify('+'.(int) config('kms.schedule.default_range_hours').' hours');

        $maxHours = (int) config('kms.schedule.max_range_hours');

        if ($to <= $from || ($to->getTimestamp() - $from->getTimestamp()) > $maxHours * 3600) {
            throw ApiProblem::of(ErrorCode::ScheduleRangeTooWide, ['max_range_hours' => $maxHours]);
        }

        $query = Programme::query()
            // Overlap, not containment: a programme already in progress when
            // the window opens is the one the viewer is watching.
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->orderBy('channel_uuid')
            ->orderBy('starts_at');

        if (isset($validated['channel_ids'])) {
            $query->whereIn('channel_uuid', $validated['channel_ids']);
        }

        $programmes = $query->limit(5000)->get();

        return (new JsonResponse([
            'data' => $programmes->map(fn (Programme $p): array => $this->present($p))->all(),
            'window' => ['from' => $from->format(DATE_RFC3339), 'to' => $to->format(DATE_RFC3339)],
        ]))->setPublic()->setMaxAge(60);
    }

    /** Now and next for a channel — the single most requested EPG view. */
    public function nowNext(Request $request, string $channelId): JsonResponse
    {
        if (! Channel::query()->where('uuid', $channelId)->where('status', 'active')->exists()) {
            throw ApiProblem::of(ErrorCode::ChannelNotFound);
        }

        $now = $this->clock->now();

        $current = Programme::query()
            ->where('channel_uuid', $channelId)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->first();

        $next = Programme::query()
            ->where('channel_uuid', $channelId)
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->first();

        return (new JsonResponse([
            'now' => $current === null ? null : $this->present($current),
            'next' => $next === null ? null : $this->present($next),
        ]))->setPublic()->setMaxAge(30);
    }

    /** @return array<string,mixed> */
    private function present(Programme $programme): array
    {
        return [
            'id' => $programme->uuid,
            'channel_id' => $programme->channel_uuid,
            'title' => $programme->title,
            'subtitle' => $programme->subtitle,
            'description' => $programme->description,
            'category_id' => $programme->category_uuid,
            'age_rating' => $programme->age_rating,
            'season_number' => $programme->season_number,
            'episode_number' => $programme->episode_number,
            'starts_at' => $programme->starts_at->format(DATE_RFC3339),
            'ends_at' => $programme->ends_at->format(DATE_RFC3339),
        ];
    }
}
