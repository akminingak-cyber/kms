<?php

declare(strict_types=1);

namespace Modules\Rights\Contracts;

use DateTimeImmutable;

final readonly class AvailabilityRequest
{
    public function __construct(
        public string $subjectType,
        public string $subjectRef,
        public string $exploitation,
        public string $territory,
        public string $platform,
        public string $monetization,
        public DateTimeImmutable $at,
    ) {}
}
