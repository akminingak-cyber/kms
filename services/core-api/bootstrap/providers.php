<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Administration\Infrastructure\AdministrationServiceProvider;
use Modules\Billing\Infrastructure\BillingServiceProvider;
use Modules\Catalog\Infrastructure\CatalogServiceProvider;
use Modules\Delivery\Infrastructure\DeliveryServiceProvider;
use Modules\Device\Infrastructure\DeviceServiceProvider;
use Modules\Entitlement\Infrastructure\EntitlementServiceProvider;
use Modules\Identity\Infrastructure\IdentityServiceProvider;
use Modules\Playback\Infrastructure\PlaybackServiceProvider;
use Modules\Product\Infrastructure\ProductServiceProvider;
use Modules\Profile\Infrastructure\ProfileServiceProvider;
use Modules\Rights\Infrastructure\RightsServiceProvider;
use Modules\Schedule\Infrastructure\ScheduleServiceProvider;
use Modules\Shared\Infrastructure\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,

    // Customer
    IdentityServiceProvider::class,
    ProfileServiceProvider::class,
    DeviceServiceProvider::class,

    // Content
    CatalogServiceProvider::class,
    ScheduleServiceProvider::class,

    // Commercial
    ProductServiceProvider::class,
    BillingServiceProvider::class,
    EntitlementServiceProvider::class,

    // Access — Rights is registered before Playback because Playback reads its
    // projection; the order is documentation, not a dependency the container needs.
    RightsServiceProvider::class,
    DeliveryServiceProvider::class,
    PlaybackServiceProvider::class,

    // Platform
    AdministrationServiceProvider::class,
];
