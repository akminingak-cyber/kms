<?php

declare(strict_types=1);

namespace Modules\Entitlement\Contracts;

use DateTimeImmutable;

/**
 * Entitlement's inbound port for the commercial side.
 *
 * Billing calls this; the direction is deliberate. Billing never reads
 * entitlements, because if it did the playback hot path would inherit a billing
 * dependency through the back door.
 */
interface EntitlementWriter
{
    public function grantFromSubscription(
        string $accountUuid,
        string $subscriptionUuid,
        string $packageUuid,
        DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validUntil,
        int $concurrencyLimit,
    ): string;

    public function revokeForSubscription(string $subscriptionUuid, string $reason): void;

    /** Rebuild the materialised snapshot the playback path reads. */
    public function rebuildSnapshot(string $accountUuid): void;
}
