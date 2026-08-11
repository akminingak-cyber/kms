<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure;

use Modules\Identity\Application\SessionService;
use Modules\Identity\Contracts\SessionIssuer;
use Modules\Identity\Contracts\SessionRevoker;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Identity';
    }

    protected function registerBindings(): void
    {
        // Ports resolve to this module's adapter. Callers depend on the
        // interface, so the binding is the only thing that changes if the
        // implementation later moves behind the network.
        $this->app->bind(SessionIssuer::class, SessionService::class);
        $this->app->bind(SessionRevoker::class, SessionService::class);
    }
}
