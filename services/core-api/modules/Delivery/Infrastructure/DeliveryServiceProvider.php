<?php

declare(strict_types=1);

namespace Modules\Delivery\Infrastructure;

use Modules\Delivery\Application\EdgeDeliveryPlanner;
use Modules\Delivery\Contracts\CdnProvider;
use Modules\Delivery\Contracts\DeliveryPlanner;
use Modules\Delivery\Domain\DeliveryToken;
use Modules\Media\Contracts\DeviceProfiles;
use Modules\Media\Contracts\MediaPublications;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Infrastructure\ModuleServiceProvider;
use RuntimeException;

final class DeliveryServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Delivery';
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(DeliveryToken::class, static fn (): DeliveryToken => new DeliveryToken(
            (string) config('kms.delivery.token_key'),
        ));

        $this->app->singleton(DeliveryPlanner::class, fn (): DeliveryPlanner => new EdgeDeliveryPlanner(
            $this->app->make(Clock::class),
            $this->app->make(MediaPublications::class),
            $this->app->make(DeviceProfiles::class),
            $this->app->make(DeliveryToken::class),
            $this->edges(),
        ));
    }

    /**
     * The configured edges, in priority order.
     *
     * An unrecognised provider name is a boot-time failure rather than a
     * silently skipped edge. Skipping it would leave the platform serving from
     * whatever edges *did* resolve, which looks healthy and is not what was
     * configured — and if it were the only entry, playback would fail with no
     * indication why.
     *
     * @return list<CdnProvider>
     */
    private function edges(): array
    {
        $edges = [];

        /** @var list<array<string,mixed>> $configured */
        $configured = (array) config('kms.delivery.edges', []);

        foreach ($configured as $edge) {
            $provider = (string) ($edge['provider'] ?? '');

            $edges[] = match ($provider) {
                'origin' => new OriginDirectProvider((string) ($edge['base_url'] ?? '')),
                // No CDN vendor has been selected (OQ-8) and no P3 verification
                // checklist is complete, so there is no vendor adapter to name
                // here. One arrives as another arm of this match.
                default => throw new RuntimeException(
                    "Delivery edge provider '{$provider}' has no adapter. ".
                    'Adding one requires a completed P3 verification checklist.',
                ),
            };
        }

        return $edges;
    }
}
