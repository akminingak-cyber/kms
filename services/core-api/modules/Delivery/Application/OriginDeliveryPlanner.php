<?php

declare(strict_types=1);

namespace Modules\Delivery\Application;

use Modules\Delivery\Contracts\DeliveryPlanner;
use Modules\Shared\Domain\Clock;

/**
 * Origin-direct delivery.
 *
 * No CDN vendor has been selected (OQ-8), and none is invented here: this
 * adapter addresses our own origin, which is the thing a CDN would sit in front
 * of. The origin is the system of record for media; the CDN is a cache
 * (ADR-0008), so origin-direct is the correct shape for this phase and gains a
 * sibling adapter rather than a rewrite when a vendor is chosen.
 *
 * The delivery token is real: an HMAC over the path, the session and an expiry,
 * so it can be validated at the edge. Two properties matter and are asserted by
 * tests:
 *
 *  - the token goes in the query string, and the path is **identical for every
 *    viewer**, so an edge configured to exclude the token from the cache key
 *    gets one cache entry per asset rather than one per viewer;
 *  - it expires in minutes and is renewed on heartbeat.
 */
final readonly class OriginDeliveryPlanner implements DeliveryPlanner
{
    public function __construct(private Clock $clock) {}

    /** @param array<string,mixed> $capabilities @return list<array<string,mixed>> */
    public function plan(string $contentRef, string $mode, string $sessionUuid, array $capabilities): array
    {
        $base = rtrim((string) config('kms.delivery.origin_base_url'), '/');
        $ttl = (int) config('kms.playback.delivery_token_ttl_seconds');
        $expiresAt = $this->clock->now()->modify("+{$ttl} seconds");

        $targets = [];

        foreach ($this->formatsFor($capabilities) as $format) {
            // Vendor-neutral, stable addressing derived from identifiers, so a
            // CDN can be pointed at the same origin without re-deriving URLs.
            $path = "/live/{$contentRef}/{$mode}/".($format === 'hls' ? 'manifest.m3u8' : 'manifest.mpd');
            $token = $this->sign($path, $sessionUuid, $expiresAt->getTimestamp());

            $targets[] = [
                'format' => $format,
                // Independent of format: a device may need HLS with MPEG-TS
                // rather than HLS with CMAF fMP4, and that is a different
                // problem from needing a different encryption scheme (ADR-0005).
                'container' => 'cmaf',
                'url' => $base.$path.'?token='.$token.'&expires='.$expiresAt->getTimestamp(),
                'priority' => count($targets) + 1,
            ];
        }

        return $targets;
    }

    /** @param array<string,mixed> $capabilities @return list<string> */
    private function formatsFor(array $capabilities): array
    {
        $requested = array_values(array_filter(
            (array) ($capabilities['formats'] ?? []),
            static fn ($f): bool => in_array($f, ['hls', 'dash'], true),
        ));

        // Client capabilities may only narrow. An empty or unrecognised list
        // falls back to what the platform serves, never to something wider.
        return $requested === [] ? ['hls', 'dash'] : $requested;
    }

    private function sign(string $path, string $sessionUuid, int $expiresAt): string
    {
        return hash_hmac(
            'sha256',
            $path.'|'.$sessionUuid.'|'.$expiresAt,
            (string) config('app.key'),
        );
    }
}
