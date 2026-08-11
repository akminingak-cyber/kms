<?php

declare(strict_types=1);

namespace Modules\Schedule\Infrastructure;

use Modules\Schedule\Application\ChannelDirectoryService;
use Modules\Schedule\Contracts\ChannelDirectory;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class ScheduleServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Schedule';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(ChannelDirectory::class, ChannelDirectoryService::class);
    }
}
