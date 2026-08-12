<?php

declare(strict_types=1);

namespace Modules\Delivery\Contracts;

/**
 * What Playback tells Delivery.
 *
 * Note what is *not* here: no account, no profile, no entitlement, no rights.
 * Delivery addresses media and signs URLs; it does not decide anything, and
 * giving it the inputs to a decision would invite it to start making one.
 * The quality caps arrive already resolved — the decision was made in Playback,
 * where it is recorded.
 */
final readonly class DeliveryRequest
{
    /**
     * @param  list<string|null>  $qualityCaps  resolution labels from every applicable
     *                                          source; the strictest wins
     * @param  array<string,mixed>  $capabilities  client-asserted, and may only narrow
     */
    public function __construct(
        public string $subjectType,
        public string $subjectRef,
        public string $mode,
        public string $sessionUuid,
        public string $deviceClass,
        public array $qualityCaps = [],
        public array $capabilities = [],
    ) {}
}
