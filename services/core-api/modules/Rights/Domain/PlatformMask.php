<?php

declare(strict_types=1);

namespace Modules\Rights\Domain;

/**
 * Device classes and monetization models as bitmasks.
 *
 * These are the low-cardinality dimensions of the availability projection. Held
 * as masks on the projection row and tested at read time in a single integer
 * operation, they are what keeps the projection at one row per subject ×
 * exploitation × interval instead of a cross-product running to hundreds of
 * millions of rows (ADR-0011).
 *
 * Bit positions are **append-only**, exactly like error codes: a stored mask
 * from last year must keep meaning what it meant. Reordering them would
 * silently re-license the catalog.
 */
final class PlatformMask
{
    private const PLATFORMS = [
        'web' => 1,
        'mobile' => 2,
        'tablet' => 4,
        'androidtv' => 8,
        'tizen' => 16,
        'webos' => 32,
        'stb' => 64,
    ];

    private const MONETIZATION = [
        'svod' => 1,
        'tvod' => 2,
        'est' => 4,
        'avod' => 8,
        'fvod' => 16,
    ];

    /** Every currently known platform. Used when a right places no restriction. */
    public static function allPlatforms(): int
    {
        return array_sum(self::PLATFORMS);
    }

    public static function allMonetization(): int
    {
        return array_sum(self::MONETIZATION);
    }

    /** @param  list<string>  $platforms */
    public static function forPlatforms(array $platforms): int
    {
        $mask = 0;

        foreach ($platforms as $platform) {
            // An unknown platform contributes nothing rather than everything:
            // default deny extends to vocabulary we do not recognise.
            $mask |= self::PLATFORMS[$platform] ?? 0;
        }

        return $mask;
    }

    /** @param  list<string>  $models */
    public static function forMonetization(array $models): int
    {
        $mask = 0;

        foreach ($models as $model) {
            $mask |= self::MONETIZATION[$model] ?? 0;
        }

        return $mask;
    }

    public static function permitsPlatform(int $mask, string $platform): bool
    {
        $bit = self::PLATFORMS[$platform] ?? null;

        // An unrecognised device class is denied, not waved through.
        return $bit !== null && ($mask & $bit) === $bit;
    }

    public static function permitsMonetization(int $mask, string $model): bool
    {
        $bit = self::MONETIZATION[$model] ?? null;

        return $bit !== null && ($mask & $bit) === $bit;
    }

    /**
     * The inverse of `forPlatforms()`: a stored mask back into names.
     *
     * Needed wherever a right is shown to a human. "platform_mask: 41" is not
     * something anyone should be decoding by hand while a licensor is on the
     * phone, and a person doing that arithmetic in their head is a person about
     * to get it wrong.
     *
     * A bit with no name is dropped rather than rendered as a number. It can
     * only mean a mask written by a newer version than this one, and inventing
     * a label for it would be worse than omitting it.
     *
     * @return list<string>
     */
    public static function platformsFrom(int $mask): array
    {
        return self::namesFrom(self::PLATFORMS, $mask);
    }

    /** @return list<string> */
    public static function monetizationFrom(int $mask): array
    {
        return self::namesFrom(self::MONETIZATION, $mask);
    }

    /**
     * @param  array<string,int>  $bits
     * @return list<string>
     */
    private static function namesFrom(array $bits, int $mask): array
    {
        $names = [];

        foreach ($bits as $name => $bit) {
            if (($mask & $bit) === $bit) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /** @return list<string> */
    public static function platformNames(): array
    {
        return array_keys(self::PLATFORMS);
    }

    /** @return list<string> */
    public static function monetizationNames(): array
    {
        return array_keys(self::MONETIZATION);
    }
}
