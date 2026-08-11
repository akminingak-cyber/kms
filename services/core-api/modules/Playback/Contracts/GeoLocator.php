<?php

declare(strict_types=1);

namespace Modules\Playback\Contracts;

/**
 * Port P11.
 *
 * No vendor is selected (OQ-8/P11), and none is invented here. The adapter in
 * this phase resolves a territory from configuration and, in non-production
 * environments only, from a trusted test header — enough to exercise every
 * territory branch of the decision honestly, without pretending to geolocate.
 */
interface GeoLocator
{
    /** @return array{territory:?string, method:string} */
    public function locate(?string $ipAddress, ?string $accountTerritory): array;
}
