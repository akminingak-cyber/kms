<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

use DateTimeImmutable;

/**
 * Time as a dependency.
 *
 * Token expiry, lockout windows, activation codes and removal cooldowns are all
 * time-dependent, and every one of them needs a test that moves time
 * deliberately rather than sleeping.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
