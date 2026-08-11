<?php

declare(strict_types=1);

namespace Modules\Delivery\Infrastructure;

use Modules\Delivery\Application\OriginDeliveryPlanner;
use Modules\Delivery\Contracts\DeliveryPlanner;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class DeliveryServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Delivery';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(DeliveryPlanner::class, OriginDeliveryPlanner::class);
    }
}
