<?php

declare(strict_types=1);

namespace Modules\Device\Contracts;

use Modules\Device\Domain\DeviceClass;

/**
 * The device-class vocabulary, published for other modules.
 *
 * Identity needs to know which classes may sign in with a password and which
 * must pair, but it must not reach into Device's Domain to find out — so the
 * lists are exposed here, as plain strings, on the port side of the boundary.
 */
final class DeviceClasses
{
    /** @return list<string> */
    public static function all(): array
    {
        return DeviceClass::values();
    }

    /** Classes that pair by code because they have no usable keyboard. @return list<string> */
    public static function requiringPairing(): array
    {
        return DeviceClass::pairingValues();
    }

    /** Classes that may authenticate with a password. @return list<string> */
    public static function passwordCapable(): array
    {
        return array_values(array_diff(self::all(), self::requiringPairing()));
    }
}
