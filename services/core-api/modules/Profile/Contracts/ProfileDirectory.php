<?php

declare(strict_types=1);

namespace Modules\Profile\Contracts;

/**
 * Profile's inbound port for ownership questions.
 *
 * Playback verifies that a submitted profile belongs to the authenticated
 * account rather than trusting the body: a profile identifier from another
 * account would otherwise select that household's parental settings.
 */
interface ProfileDirectory
{
    public function belongsToAccount(string $accountUuid, string $profileUuid): bool;

    /** The device's class, from the server-side registration. */
    public function deviceClassFor(string $deviceUuid): ?string;
}
