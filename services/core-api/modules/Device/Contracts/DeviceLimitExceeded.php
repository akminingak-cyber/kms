<?php

declare(strict_types=1);

namespace Modules\Device\Contracts;

use RuntimeException;

/**
 * Part of the port's contract, so callers in other modules can react without
 * importing anything from Device's internals.
 */
final class DeviceLimitExceeded extends RuntimeException
{
    public function __construct(public readonly int $limit)
    {
        parent::__construct('Device limit reached.');
    }
}
