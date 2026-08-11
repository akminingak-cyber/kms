<?php

declare(strict_types=1);

namespace Modules\Device\Infrastructure;

use Modules\Device\Application\DeviceRegistryService;
use Modules\Device\Contracts\DeviceRegistry;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class DeviceServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Device';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(DeviceRegistry::class, DeviceRegistryService::class);
    }
}
