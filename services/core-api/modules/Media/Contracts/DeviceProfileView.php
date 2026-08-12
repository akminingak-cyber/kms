<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

/**
 * A device class's delivery preferences.
 *
 * `formats` is **ordered by preference**, because the choice is not binary: a
 * television may play both HLS and DASH and still be far more reliable on one
 * of them, and that ordering is an empirical result from the device lab rather
 * than something derivable.
 */
final readonly class DeviceProfileView
{
    /** @param list<string> $formats ordered, most preferred first */
    public function __construct(
        public string $deviceClass,
        public string $container,
        public array $formats,
        public ?int $maxHeight,
    ) {}
}
