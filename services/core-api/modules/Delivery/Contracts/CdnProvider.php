<?php

declare(strict_types=1);

namespace Modules\Delivery\Contracts;

/**
 * One delivery edge.
 *
 * All interaction with a CDN goes through this port (P3). No other context
 * knows a CDN vendor exists, which is what makes a second vendor a
 * configuration change rather than a rewrite (ADR-0008).
 *
 * **The port is deliberately this small.** Purge, log delivery, geo controls
 * and shield configuration are all real CDN capabilities and all belong here
 * eventually — but their *shapes* differ between vendors, and no vendor has
 * been selected (OQ-8) or verified against the P3 checklist. Declaring methods
 * now would mean inventing an interface from an imagined vendor and then
 * discovering it fits none of them; an adapter may not be built before its
 * checklist is complete
 * (`docs/architecture/05-integration-boundaries.md`), and neither may the
 * interface it would implement.
 *
 * What is here is what the platform genuinely needs today: somewhere to send a
 * viewer, and a way to attach a token that the edge validates without
 * interpreting.
 */
interface CdnProvider
{
    /** Recorded on every delivery target so a QoE problem can be attributed to an edge. */
    public function name(): string;

    /**
     * Turns an origin path into a URL on this edge.
     *
     * The path must be passed through unchanged. Everything that varies per
     * viewer is inside the token, so that the cache key can be the path alone.
     */
    public function urlFor(string $originPath, string $token): string;
}
