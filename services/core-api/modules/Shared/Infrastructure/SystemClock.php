<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Modules\Shared\Domain\Clock;

/** Always UTC. Broadcast-local times carry their own explicit zone. */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
