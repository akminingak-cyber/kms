<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base provider for a bounded-context module.
 *
 * A module owns its migrations and its routes. Keeping both inside the module
 * is what makes extraction to a separate service a build-configuration change
 * rather than an archaeology project (ADR-0001).
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Module directory name, e.g. "Identity". */
    abstract protected function moduleName(): string;

    public function register(): void
    {
        $this->registerBindings();
    }

    public function boot(): void
    {
        $base = $this->moduleBasePath();

        $migrations = $base.'/Infrastructure/Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        $this->registerRoutes($base.'/Http/Routes');
    }

    /** Override to bind ports to adapters. */
    protected function registerBindings(): void {}

    private function registerRoutes(string $routeDir): void
    {
        foreach (['client', 'admin', 'internal'] as $surface) {
            $file = $routeDir.'/'.$surface.'.php';

            if (! is_file($file)) {
                continue;
            }

            Route::prefix((string) config("kms.api.{$surface}_prefix"))
                ->middleware($surface)
                ->group($file);
        }
    }

    private function moduleBasePath(): string
    {
        return dirname(__DIR__, 2).'/'.$this->moduleName();
    }
}
