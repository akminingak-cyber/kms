<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure;

use Modules\Product\Application\PackageCatalogService;
use Modules\Product\Contracts\PackageCatalog;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class ProductServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Product';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(PackageCatalog::class, PackageCatalogService::class);
    }
}
