<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure;

use Modules\Media\Contracts\DeviceProfiles;
use Modules\Media\Contracts\DeviceProfileView;
use Modules\Media\Infrastructure\Eloquent\DeviceProfile;

/**
 * Device profiles from the database, falling back to the platform default.
 *
 * A device class with no row gets what the platform serves by default rather
 * than nothing. This table **narrows**; it does not gate. Gating a device class
 * out of playback is a rights decision and belongs where it can be audited
 * against an agreement, not in a table maintained from device-lab results.
 */
final readonly class ConfiguredDeviceProfiles implements DeviceProfiles
{
    public function for(string $deviceClass): ?DeviceProfileView
    {
        $row = DeviceProfile::query()->where('device_class', $deviceClass)->first();

        if ($row === null) {
            return new DeviceProfileView(
                deviceClass: $deviceClass,
                container: (string) config('kms.delivery.default_container'),
                formats: array_values((array) config('kms.delivery.default_formats')),
                maxHeight: null,
            );
        }

        return new DeviceProfileView(
            deviceClass: (string) $row->device_class,
            container: (string) $row->container,
            formats: array_values(array_map(strval(...), (array) $row->formats)),
            maxHeight: $row->max_height === null ? null : (int) $row->max_height,
        );
    }
}
