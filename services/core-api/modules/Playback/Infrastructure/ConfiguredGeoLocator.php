<?php

declare(strict_types=1);

namespace Modules\Playback\Infrastructure;

use Modules\Playback\Contracts\GeoLocator;
use Modules\Playback\Domain\TerritoryDetermination;

/**
 * The Phase 2 adapter behind {@see GeoLocator}.
 *
 * It does not geolocate. A real IP-to-territory adapter needs a vendor that has
 * not been chosen, and guessing at one would be inventing a third-party
 * capability. What this does is resolve the territory from data we actually
 * hold, and record honestly which signal it used — so every territory branch of
 * the playback decision is exercised for real, and the decision record is
 * already shaped for the vendor adapter that replaces this.
 */
final class ConfiguredGeoLocator implements GeoLocator
{
    /** @return array{territory:?string, method:string} */
    public function locate(?string $ipAddress, ?string $accountTerritory): array
    {
        if ($accountTerritory !== null && $accountTerritory !== '') {
            return [
                'territory' => strtoupper($accountTerritory),
                'method' => TerritoryDetermination::ACCOUNT_DEFAULT,
            ];
        }

        $default = config('kms.playback.default_territory');

        if (is_string($default) && $default !== '') {
            return [
                'territory' => strtoupper($default),
                'method' => TerritoryDetermination::PLATFORM_DEFAULT,
            ];
        }

        // Fail closed. An undetermined territory denies territory-restricted
        // content rather than guessing.
        return ['territory' => null, 'method' => TerritoryDetermination::UNDETERMINED];
    }
}
