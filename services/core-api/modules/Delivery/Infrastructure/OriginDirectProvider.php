<?php

declare(strict_types=1);

namespace Modules\Delivery\Infrastructure;

use Modules\Delivery\Contracts\CdnProvider;
use Modules\Delivery\Domain\DeliveryToken;

/**
 * Delivery straight from our own origin, with no CDN in front.
 *
 * This is not a placeholder for a CDN adapter — it is a real delivery mode, and
 * the correct one right now. No CDN vendor has been selected (OQ-8) and the P3
 * verification checklist is not complete, so no vendor adapter exists; the
 * origin is the system of record for media and a CDN is a cache in front of it
 * (ADR-0008), which means origin-direct is exactly the shape a CDN slots into.
 *
 * It stays useful afterwards, too: it is what an origin shield's health check
 * and a synthetic playback probe address, and it is the fallback when every
 * configured edge is failing.
 */
final readonly class OriginDirectProvider implements CdnProvider
{
    public function __construct(private string $baseUrl) {}

    public function name(): string
    {
        return 'origin';
    }

    public function urlFor(string $originPath, string $token): string
    {
        return rtrim($this->baseUrl, '/').$originPath.'?'.DeliveryToken::PARAMETER.'='.$token;
    }
}
