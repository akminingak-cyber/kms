<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Http\Controllers\ClientConfigController;
use Modules\Shared\Http\Controllers\HealthController;

/*
 * Platform endpoints that belong to no bounded context.
 *
 * Liveness and readiness are separate: a liveness probe that checks the
 * database restarts every instance when the database has a hiccup.
 */
Route::get('/health', [HealthController::class, 'live']);
Route::get('/health/ready', [HealthController::class, 'ready']);

/*
 * Fetched by every client at start-up. It must stay reachable when the rest of
 * the API is degraded, because it is how a client learns it should stop trying
 * something or prompt for an upgrade.
 */
Route::get(config('kms.api.client_prefix').'/config', ClientConfigController::class)
    ->middleware('client');
