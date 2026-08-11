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
     * @param  array<string,mixed>  $capabilities  client-asserted, and may only narrow
     * @return list<array<string,mixed>> ordered targets; a list from day one so
     *                                   multi-CDN needs no client update later
     */
    public function plan(string $contentRef, string $mode, string $sessionUuid, array $capabilities): array;
}
