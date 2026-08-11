<?php

declare(strict_types=1);

namespace Modules\Entitlement\Application;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Entitlement\Contracts\EntitlementQuery;
use Modules\Entitlement\Contracts\EntitlementSnapshot;
use Modules\Entitlement\Contracts\EntitlementWriter;
use Modules\Entitlement\Infrastructure\Eloquent\Grant;
use Modules\Entitlement\Infrastructure\Eloquent\Snapshot;
use Modules\Product\Contracts\PackageCatalog;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Infrastructure\AccountLock;

/**
 * Grants and the materialised snapshot.
 *
 * Entitlements are derived, but **explicitly materialised** rather than
 * recomputed per request: resolving subscription → plan → package → channels on
 * the hot path would put three joins and a billing dependency inside the
 * playback latency budget.
 *
 * Every grant records its source, so "why can this account watch this?" is
 * answerable from a row six months later rather than by reasoning about billing
 * history.
 */
final readonly class EntitlementService implements EntitlementQuery, EntitlementWriter
{
    public function __construct(
        private Clock $clock,
        private PackageCatalog $packages,
    ) {}

    public function grantFromSubscription(
        string $accountUuid,
        string $subscriptionUuid,
        string $packageUuid,
        DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validUntil,
        int $concurrencyLimit,
    ): string {
        $grant = Grant::query()->create([
            'uuid' => (string) Str::uuid7(),
            'account_uuid' => $accountUuid,
            'source' => 'subscription',
            'source_ref' => $subscriptionUuid,
            'scope_type' => 'package',
            'scope_ref' => $packageUuid,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'concurrency_limit' => $concurrencyLimit,
        ]);

        $this->rebuildSnapshot($accountUuid);

        return $grant->uuid;
    }

    public function revokeForSubscription(string $subscriptionUuid, string $reason): void
    {
        $accounts = Grant::query()
            ->where('source', 'subscription')
            ->where('source_ref', $subscriptionUuid)
            ->whereNull('revoked_at')
            ->pluck('account_uuid')
            ->unique();

        Grant::query()
            ->where('source', 'subscription')
            ->where('source_ref', $subscriptionUuid)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $this->clock->now(), 'revoked_reason' => $reason]);

        foreach ($accounts as $accountUuid) {
            $this->rebuildSnapshot((string) $accountUuid);
        }
    }

    /**
     * Recompute the snapshot from live grants.
     *
     * Serialised per account: two concurrent rebuilds could interleave and
     * persist the older result, which would silently restore access that was
     * just revoked.
     */
    public function rebuildSnapshot(string $accountUuid): void
    {
        DB::transaction(function () use ($accountUuid): void {
            AccountLock::acquire($accountUuid, 'entitlement-snapshot');

            $now = $this->clock->now();

            $grants = Grant::query()
                ->where('account_uuid', $accountUuid)
                ->whereNull('revoked_at')
                ->where('valid_from', '<=', $now)
                ->where(function ($q) use ($now): void {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>', $now);
                })
                ->get();

            $channels = [];
            $packages = [];
            $grantIds = [];

            foreach ($grants as $grant) {
                $grantIds[] = (string) $grant->uuid;

                if ($grant->scope_type === 'package') {
                    $packages[] = (string) $grant->scope_ref;

                    foreach ($this->packages->channelsInPackage((string) $grant->scope_ref) as $channelUuid) {
                        $channels[] = $channelUuid;
                    }

                    continue;
                }

                if ($grant->scope_type === 'channel') {
                    $channels[] = (string) $grant->scope_ref;
                }
            }

            $existing = Snapshot::query()->where('account_uuid', $accountUuid)->first();

            Snapshot::query()->updateOrCreate(
                ['account_uuid' => $accountUuid],
                [
                    'version' => (int) ($existing?->version ?? 0) + 1,
                    'channels' => array_values(array_unique($channels)),
                    'packages' => array_values(array_unique($packages)),
                    'grant_ids' => array_values(array_unique($grantIds)),
                    // An account holding more than one grant gets the better
                    // allowance; the licensor's cap still overrides at playback.
                    'concurrency_limit' => max(1, (int) $grants->max('concurrency_limit')),
                    'max_resolution' => null,
                    'computed_at' => $now,
                ],
            );
        });
    }

    public function snapshotFor(string $accountUuid): ?EntitlementSnapshot
    {
        $row = Snapshot::query()->where('account_uuid', $accountUuid)->first();

        if ($row === null) {
            return null;
        }

        return new EntitlementSnapshot(
            channels: (array) $row->channels,
            packages: (array) $row->packages,
            grantIds: (array) $row->grant_ids,
            concurrencyLimit: (int) $row->concurrency_limit,
            maxResolution: $row->max_resolution,
            version: (int) $row->version,
            computedAt: $row->computed_at->toDateTimeImmutable(),
        );
    }
}
