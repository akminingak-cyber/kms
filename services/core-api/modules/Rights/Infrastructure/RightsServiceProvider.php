<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure;

use Modules\Rights\Application\AvailabilityResolver;
use Modules\Rights\Contracts\AvailabilityQuery;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class RightsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Rights';
    }

    protected function registerBindings(): void
    {
        // Scoped, not singleton: the resolver caches interned rule sets
        // for the life of a request, which is exactly one decision's worth.
        $this->app->scoped(AvailabilityQuery::class, AvailabilityResolver::class);
    }
}
