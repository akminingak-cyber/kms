<?php

declare(strict_types=1);

namespace Modules\Device\Contracts;

/**
 * Device's inbound port.
 *
 * Identity calls this during sign-in to bind a session to a device; nothing
 * outside the Device module touches the `device` schema.
 */
interface DeviceRegistry
{
    /**
     * Register the device if it is new to this account, or record that an
     * existing one was seen again. Idempotent by (account, fingerprint).
     *
     * @throws DeviceLimitExceeded when the account already holds its maximum
     *                             number of live registrations
     */
    public function registerOrTouch(
        string $accountUuid,
        string $deviceClass,
        string $name,
        ?string $fingerprint,
        ?string $platformVersion = null,
    ): DeviceDescriptor;

    /** True when the device exists, belongs to the account, and is not removed. */
    public function isActive(string $accountUuid, string $deviceUuid): bool;

    /** Marks the device removed. Callers revoke that device's sessions separately. */
    public function remove(string $accountUuid, string $deviceUuid): void;

    /** @return list<DeviceDescriptor> */
    public function listActive(string $accountUuid): array;
}
