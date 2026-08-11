<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use Modules\Administration\Infrastructure\AdministrationServiceProvider;
use Modules\Device\Infrastructure\DeviceServiceProvider;
use Modules\Identity\Infrastructure\IdentityServiceProvider;
use Modules\Profile\Infrastructure\ProfileServiceProvider;
use Modules\Shared\Infrastructure\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentityServiceProvider::class,
    ProfileServiceProvider::class,
    DeviceServiceProvider::class,
    AdministrationServiceProvider::class,
];
