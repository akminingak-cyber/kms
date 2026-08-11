<?php

declare(strict_types=1);

namespace Modules\Playback\Application;

use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Playback\Infrastructure\Eloquent\ConcurrencySlot;
use Modules\Shared\Domain\Clock;
use Throwable;

/**
 * Concurrency slots.
 *
 * Redis is authoritative for enforcement because the check is on the hot path;
 * `playback.concurrency_slots` is a durable mirror that lets the counter be
 * rebuilt after a Redis loss and makes leaked slots visible.
 *
 * The degraded behaviour is deliberately asymmetric. If the counter store is
 * unavailable, a **plan** limit may fail open — temporary over-permissiveness
 * there is a revenue nuisance. A **licensor's** cap may not: exceeding it is a
 * contract breach, exactly like ignoring a territory.
 */
final readonly class ConcurrencyLedger
{
    private const PREFIX = 'kms:playback:concurrency:';

    public function __construct(
        private Clock $clock,
        private CacheRepository $cache,
    ) {}

    public function acquire(string $accountUuid, string $sessionUuid, int $limit, bool $failOpenPermitted): SlotOutcome
    {
        $key = self::PREFIX.$accountUuid;
        $ttl = (int) config('kms.playback.session_ttl_seconds');

        try {
            /** @var array<string,int> $sessions */
            $sessions = (array) $this->cache->get($key, []);
            $now = $this->clock->now()->getTimestamp();

            // Sessions whose heartbeat lapsed are released here. Without this a
            // crashed client would consume a viewer's allowance until it
            // expired on its own.
            $sessions = array_filter($sessions, static fn (int $expiry): bool => $expiry > $now);

            if (count($sessions) >= $limit && ! isset($sessions[$sessionUuid])) {
                return new SlotOutcome(false, 'exceeded');
            }

            $sessions[$sessionUuid] = $now + $ttl;
            $this->cache->put($key, $sessions, $ttl);

            return new SlotOutcome(true, null);
        } catch (Throwable) {
            return $failOpenPermitted
                ? new SlotOutcome(true, 'degraded')
                : new SlotOutcome(false, 'unavailable');
        }
    }

    public function renew(string $accountUuid, string $sessionUuid): bool
    {
        $key = self::PREFIX.$accountUuid;
        $ttl = (int) config('kms.playback.session_ttl_seconds');

        try {
            /** @var array<string,int> $sessions */
            $sessions = (array) $this->cache->get($key, []);
            $now = $this->clock->now()->getTimestamp();

            /*
             * Expiry, not merely presence.
             *
             * The store's own TTL and the platform clock are different things,
             * so a slot whose recorded expiry has passed is gone even if the
             * key has not yet been evicted. Checking presence alone would let
             * acquire and renew disagree about what "lapsed" means — and the
             * disagreement would show up as a session that cannot be
             * displaced but also cannot be renewed.
             */
            if (($sessions[$sessionUuid] ?? 0) <= $now) {
                return false;
            }

            $sessions = array_filter($sessions, static fn (int $expiry): bool => $expiry > $now);
            $sessions[$sessionUuid] = $now + $ttl;
            $this->cache->put($key, $sessions, $ttl);

            return true;
        } catch (Throwable) {
            return true;
        }
    }

    public function release(string $accountUuid, string $sessionUuid): void
    {
        $key = self::PREFIX.$accountUuid;

        try {
            /** @var array<string,int> $sessions */
            $sessions = (array) $this->cache->get($key, []);
            unset($sessions[$sessionUuid]);
            $this->cache->put($key, $sessions, (int) config('kms.playback.session_ttl_seconds'));
        } catch (Throwable) {
            // Best effort; the durable mirror and the heartbeat sweep both
            // recover a leaked slot.
        }

        ConcurrencySlot::query()
            ->where('session_uuid', $sessionUuid)
            ->whereNull('released_at')
            ->update(['released_at' => $this->clock->now()]);
    }

    /** The durable mirror, written inside the session transaction. */
    public function mirror(string $accountUuid, string $sessionUuid, DateTimeImmutable $now): void
    {
        ConcurrencySlot::query()->create([
            'session_uuid' => $sessionUuid,
            'account_uuid' => $accountUuid,
            'acquired_at' => $now,
            'expires_at' => $now->modify('+'.(int) config('kms.playback.session_ttl_seconds').' seconds'),
        ]);
    }

    public function activeCount(string $accountUuid): int
    {
        try {
            $now = $this->clock->now()->getTimestamp();
            /** @var array<string,int> $sessions */
            $sessions = (array) $this->cache->get(self::PREFIX.$accountUuid, []);

            return count(array_filter($sessions, static fn (int $e): bool => $e > $now));
        } catch (Throwable) {
            return 0;
        }
    }
}
