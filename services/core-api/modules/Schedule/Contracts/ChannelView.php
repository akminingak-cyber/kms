<?php

declare(strict_types=1);

namespace Modules\Schedule\Contracts;

/** What other modules are allowed to know about a channel. */
final readonly class ChannelView
{
    public function __construct(
        public string $uuid,
        public string $slug,
        public string $name,
        public bool $isPlayable,
        public bool $catchupEnabled,
        public ?string $ageRating = null,
    ) {}
}
