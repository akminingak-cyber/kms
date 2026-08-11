<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\AuthController;

/*
 * Client surface — /api/client/v1/auth
 *
 * Rate limits are layered deliberately: per IP bounds an attacker working from
 * one address, per identity bounds a distributed attacker targeting one account.
 * Neither alone is sufficient.
 */
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:register-ip');

    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])
        ->middleware('throttle:auth-ip');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['throttle:auth-ip', 'throttle:auth-identity']);

    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:auth-ip');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware(['client.auth', 'throttle:client-write']);

    Route::post('/password/forgot', [AuthController::class, 'requestPasswordReset'])
        ->middleware('throttle:password-reset-ip');

    Route::post('/password/reset', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:password-reset-ip');
});
