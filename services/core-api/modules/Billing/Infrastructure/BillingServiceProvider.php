<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure;

use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class BillingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Billing';
    }
}
