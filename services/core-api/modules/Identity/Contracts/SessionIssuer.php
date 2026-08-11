<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

use DateTimeImmutable;

/**
 * Identity's inbound port for session issuance.
 *
 * Device calls this once an activation has been approved, so a television
 * receives exactly the same kind of session as any other client. Device does
 * not know how a session is built, and Identity does not know what an
 * activation code is.
 */
interface SessionIssuer
{
    /**
     * @return array{access_token:string, refresh_token:string, expires_in:int, refresh_expires_at:DateTimeImmutable}
     */
    public function issueForActivatedDevice(string $accountUuid, string $deviceUuid): array;
}
