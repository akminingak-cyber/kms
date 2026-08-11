<?php

declare(strict_types=1);

namespace Modules\Playback\Contracts;

use DateTimeImmutable;

final readonly class PlaybackResult
{
    /**
     * @param  list<array<string,mixed>>  $deliveryTargets  an ordered list from day
     *                                                      one, even when it has a single entry: clients that cannot handle a
     *                                                      list would still be in the field years later, blocking multi-CDN
     *                                                      adoption (ADR-0008)
     * @param  array<string,mixed>  $restrictions  rights-derived, not hardcoded
     */
    public function __construct(
        public string $sessionId,
        public string $decisionId,
        public array $deliveryTargets,
        public array $restrictions,
        public int $heartbeatIntervalSeconds,
        public DateTimeImmutable $expiresAt,
    ) {}
}
