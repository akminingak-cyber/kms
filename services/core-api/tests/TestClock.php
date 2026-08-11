<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use Modules\Shared\Domain\Clock;

/** Time that moves only when a test moves it. */
final class TestClock implements Clock
{
    public function __construct(private DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }

    public function setTo(DateTimeImmutable $instant): void
    {
        $this->now = $instant;
    }
}
