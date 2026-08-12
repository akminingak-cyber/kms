<?php

declare(strict_types=1);

namespace Modules\Delivery\Contracts;

use DateTimeImmutable;

final readonly class DeliveryTarget
{
    public function __construct(
        public string $format,
        public string $container,
        public string $url,
        public int $priority,
        public string $edge,
        public string $qualityClass,
        public DateTimeImmutable $expiresAt,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'container' => $this->container,
            'url' => $this->url,
            'priority' => $this->priority,
            // Named so a QoE problem can be attributed to an edge without
            // parsing hostnames out of URLs in a log pipeline.
            'edge' => $this->edge,
            // Surfaced because a viewer capped to 720p who reports "it looks
            // soft" is answered from the response, not from an investigation.
            'quality_class' => $this->qualityClass,
            'expires_at' => $this->expiresAt->format(DATE_RFC3339),
        ];
    }
}
