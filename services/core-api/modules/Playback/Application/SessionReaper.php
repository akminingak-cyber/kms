<?php

declare(strict_types=1);

namespace Modules\Playback\Application;

use Modules\Playback\Infrastructure\Eloquent\ConcurrencySlot;
use Modules\Playback\Infrastructure\Eloquent\PlaybackSession;
use Modules\Shared\Domain\Clock;

/**
 * Closes sessions whose client stopped heartbeating.
 *
 * Clients crash, lose power and lose network, and none of those send a stop.
 * Redis expiry already frees the enforcement counter, but the durable record
 * would otherwise show a viewer watching four channels indefinitely — which
 * makes every concurrency question unanswerable and every usage report wrong.
 *
 * Idempotent and bounded per run, so it can be scheduled frequently and a long
 * outage does not produce one enormous transaction on recovery.
 */
final readonly class SessionReaper
{
    public function __construct(private Clock $clock) {}

    /** @return array{sessions:int,slots:int} */
    public function reap(int $limit = 1000): array
    {
        $now = $this->clock->now();
        $cutoff = $now->modify('-'.(int) config('kms.playback.session_ttl_seconds').' seconds');

        $stale = PlaybackSession::query()
            ->whereNull('ended_at')
            ->where('last_heartbeat_at', '<', $cutoff)
            ->orderBy('last_heartbeat_at')
            ->limit($limit)
            ->pluck('uuid')
            ->all();

        if ($stale === []) {
            return ['sessions' => 0, 'slots' => 0];
        }

        $sessions = PlaybackSession::query()
            ->whereIn('uuid', $stale)
            ->update(['ended_at' => $now, 'end_reason' => 'heartbeat_expired']);

        /*
         * The durable slot mirror is released too. Redis has almost certainly
         * expired the counter already, but the mirror is what a rebuild after a
         * Redis loss reads — and a mirror full of slots nobody holds would
         * rebuild a counter that denies playback to viewers watching nothing.
         */
        $slots = ConcurrencySlot::query()
            ->whereIn('session_uuid', $stale)
            ->whereNull('released_at')
            ->update(['released_at' => $now]);

        return ['sessions' => $sessions, 'slots' => $slots];
    }
}
