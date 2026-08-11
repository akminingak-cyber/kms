<?php

declare(strict_types=1);

namespace Modules\Profile\Contracts;

/**
 * Profile's inbound port.
 *
 * Identity calls this during registration so a new account has a usable primary
 * profile in the same transaction. This is the boundary rule working as
 * intended: Identity depends on Profile's Contracts namespace and on nothing
 * else in Profile.
 */
interface ProfileProvisioning
{
    /**
     * Creates the account's primary profile. Idempotent: calling it for an
     * account that already has a primary profile returns the existing one.
     *
     * @return string the profile's public identifier
     */
    public function provisionPrimary(string $accountUuid, string $name, string $locale): string;

    /** Removes every profile belonging to the account. Used on account closure. */
    public function purgeForAccount(string $accountUuid): void;
}
