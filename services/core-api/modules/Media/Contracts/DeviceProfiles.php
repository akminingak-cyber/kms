<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

/**
 * What this platform offers a given class of device.
 *
 * A second inbound port rather than a method on `MediaPublications`, because
 * the two answer different questions and change for different reasons: a
 * publication changes when a channel is re-encoded, a device profile changes
 * when the device lab reports back.
 */
interface DeviceProfiles
{
    public function for(string $deviceClass): ?DeviceProfileView;
}
