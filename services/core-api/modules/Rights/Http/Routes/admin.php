<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Rights\Http\Controllers\AdminRightsController;

/*
 * Rights changes are contract changes. Only a rights manager may make them,
 * and every one is audited.
 */
Route::middleware(['staff.auth', 'throttle:admin', 'staff.role:rights_manager,platform_engineer'])
    ->prefix('rights')
    ->group(function (): void {
        Route::get('/agreements', [AdminRightsController::class, 'agreements']);
        Route::get('/rights', [AdminRightsController::class, 'rights']);
        Route::get('/blackouts', [AdminRightsController::class, 'blackouts']);

        Route::post('/agreements', [AdminRightsController::class, 'storeAgreement']);
        Route::post('/rights', [AdminRightsController::class, 'storeRight']);
        Route::post('/blackouts', [AdminRightsController::class, 'storeBlackout']);
        // Preview before commit: a rights mistake is a contract breach.
        Route::post('/availability/preview', [AdminRightsController::class, 'preview']);
    });
