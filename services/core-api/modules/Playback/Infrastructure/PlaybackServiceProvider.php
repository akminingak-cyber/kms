<?php

declare(strict_types=1);

namespace Modules\Playback\Infrastructure;

use Modules\Playback\Contracts\GeoLocator;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class PlaybackServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Playback';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(GeoLocator::class, ConfiguredGeoLocator::class);
    }
}
