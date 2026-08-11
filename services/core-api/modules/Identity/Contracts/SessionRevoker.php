<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

/**
 * Identity's inbound port for revocation.
 *
 * Device calls this when a device is removed. Separated from SessionIssuer so a
 * caller that only needs to revoke cannot also mint sessions.
 */
interface SessionRevoker
{
    public function revokeSessionsForDevice(string $accountUuid, string $deviceUuid, string $reason): void;
}
