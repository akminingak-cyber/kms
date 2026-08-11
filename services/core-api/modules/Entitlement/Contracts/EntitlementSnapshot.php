<?php

declare(strict_types=1);

namespace Modules\Entitlement\Contracts;

use DateTimeImmutable;

final readonly class EntitlementSnapshot
{
    /**
     * @param  list<string>  $channels  flattened deliberately: resolving packages
     *                                  to channels on the hot path would put a product
     *                                  lookup inside the latency budget
     * @param  list<string>  $packages
     * @param  list<string>  $grantIds  every allow records which grants produced it
     */
    public function __construct(
        public array $channels,
        public array $packages,
        public array $grantIds,
        public int $concurrencyLimit,
        public ?string $maxResolution,
        public int $version,
        public DateTimeImmutable $computedAt,
    ) {}

    public function includesChannel(string $channelUuid): bool
    {
        return in_array($channelUuid, $this->channels, true);
    }

    /** Staleness is measurable so it can be alerted on. */
    public function ageInSeconds(DateTimeImmutable $now): int
    {
        return max(0, $now->getTimestamp() - $this->computedAt->getTimestamp());
    }
}
