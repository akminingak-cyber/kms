<?php

declare(strict_types=1);

namespace Modules\Delivery\Contracts;

/**
 * Delivery's inbound port.
 *
 * No other context knows a CDN vendor exists (ADR-0008). Playback asks for an
 * abstract set of delivery targets and never builds a vendor URL itself.
 */
interface DeliveryPlanner
{
    /**
     * @return list<DeliveryTarget> ordered, best first; a list from day one so
     *                              multi-CDN needs no client update later.
     *                              Empty means nothing is published — the caller
     *                              must treat that as a denial rather than as
     *                              an empty success.
     */
    public function plan(DeliveryRequest $request): array;
}
