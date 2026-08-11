<?php

declare(strict_types=1);

namespace Modules\Administration\Infrastructure;

use Modules\Administration\Application\DatabaseAuditRecorder;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class AdministrationServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Administration';
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(AuditRecorder::class, DatabaseAuditRecorder::class);
    }
}
