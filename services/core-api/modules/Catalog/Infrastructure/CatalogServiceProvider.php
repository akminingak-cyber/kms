<?php

declare(strict_types=1);

namespace Modules\Catalog\Infrastructure;

use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Catalog';
    }
}
