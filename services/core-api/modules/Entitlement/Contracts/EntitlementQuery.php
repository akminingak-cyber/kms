<?php

declare(strict_types=1);

namespace Modules\Entitlement\Contracts;

/**
 * Entitlement's inbound port — what the playback hot path reads.
 *
 * Playback never reads subscription state directly. It reads the materialised
 * snapshot, so billing being down does not stop existing subscribers watching,
 * and a billing schema change never reaches the latency-critical path.
 */
interface EntitlementQuery
{
    /** Null when no snapshot has been computed for the account. */
    public function snapshotFor(string $accountUuid): ?EntitlementSnapshot;
}
