<?php

declare(strict_types=1);

namespace Modules\Device\Domain;

/**
 * Device class is authoritative platform data, not a display label.
 *
 * From Phase 4 it is an input to the rights and DRM decision — which is why it
 * is constrained here, constrained again by a database CHECK, and never taken
 * from an unverified client claim that would *raise* capability.
 */
enum DeviceClass: string
{
    case Web = 'web';
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case AndroidTv = 'androidtv';
    case Tizen = 'tizen';
    case WebOs = 'webos';
    case Stb = 'stb';

    /**
     * Classes that sign in by pairing code rather than by typing credentials.
     *
     * Televisions have no usable keyboard: typing an email and a password with
     * a remote control measurably reduces sign-up conversion and pushes people
     * toward weak passwords.
     */
    public function requiresPairing(): bool
    {
        return match ($this) {
            self::AndroidTv, self::Tizen, self::WebOs, self::Stb => true,
            self::Web, self::Mobile, self::Tablet => false,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    /** @return list<string> */
    public static function pairingValues(): array
    {
        return array_values(array_map(
            static fn (self $c): string => $c->value,
            array_filter(self::cases(), static fn (self $c): bool => $c->requiresPairing()),
        ));
    }
}
