<?php

declare(strict_types=1);

namespace Modules\Playback\Application;

final readonly class SlotOutcome
{
    /** @param 'exceeded'|'unavailable'|'degraded'|null $reason */
    public function __construct(public bool $granted, public ?string $reason) {}
}
