<?php

declare(strict_types=1);

namespace Modules\Entitlement\Infrastructure;

use Modules\Entitlement\Application\EntitlementService;
use Modules\Entitlement\Contracts\EntitlementQuery;
use Modules\Entitlement\Contracts\EntitlementWriter;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class EntitlementServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Entitlement';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(EntitlementQuery::class, EntitlementService::class);
        $this->app->bind(EntitlementWriter::class, EntitlementService::class);
    }
}
