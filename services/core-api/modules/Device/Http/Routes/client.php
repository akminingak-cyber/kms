<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Device\Http\Controllers\ActivationController;
use Modules\Device\Http\Controllers\DeviceController;

/*
 * Television activation. The first two are called by a device with no keyboard
 * and are therefore unauthenticated; the third is called from an already
 * authenticated session on a device that has one.
 */
Route::prefix('auth/device')->group(function (): void {
    Route::post('/authorize', [ActivationController::class, 'start'])
        ->middleware('throttle:auth-ip');

    // Polled repeatedly by the television, so it gets its own, looser limit —
    // and the client is told the interval it should honour.
    Route::post('/token', [ActivationController::class, 'poll'])
        ->middleware('throttle:activation-poll');

    Route::post('/approve', [ActivationController::class, 'approve'])
        ->middleware(['client.auth', 'throttle:client-write']);
});

Route::middleware('client.auth')->prefix('devices')->group(function (): void {
    Route::get('/', [DeviceController::class, 'index'])->middleware('throttle:client-read');
    Route::delete('/{deviceId}', [DeviceController::class, 'destroy'])->middleware('throttle:client-write');
});
