<?php

declare(strict_types=1);

namespace Modules\Device\Contracts;

use DateTimeImmutable;

/** What other modules are allowed to know about a device. */
final readonly class DeviceDescriptor
{
    public function __construct(
        public string $uuid,
        public string $deviceClass,
        public string $name,
        public DateTimeImmutable $firstSeenAt,
        public DateTimeImmutable $lastSeenAt,
    ) {}
}
