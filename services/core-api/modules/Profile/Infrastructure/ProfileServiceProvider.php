<?php

declare(strict_types=1);

namespace Modules\Profile\Infrastructure;

use Modules\Profile\Application\ProfileService;
use Modules\Profile\Contracts\ParentalPolicy;
use Modules\Profile\Contracts\ProfileDirectory;
use Modules\Profile\Contracts\ProfileProvisioning;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class ProfileServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Profile';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(ProfileProvisioning::class, ProfileService::class);
        $this->app->bind(ParentalPolicy::class, ProfileService::class);
        $this->app->bind(ProfileDirectory::class, ProfileService::class);
    }
}
