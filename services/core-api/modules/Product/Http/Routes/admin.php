<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\AdminProductController;

Route::middleware(['staff.auth', 'throttle:admin'])->group(function (): void {
    // Support needs to know what a plan includes to answer "should this be in
    // my package?" — which is a different authority from setting its price.
    Route::get('/packages', [AdminProductController::class, 'packages'])
        ->middleware('staff.role:commercial_operator,platform_engineer,support_agent,auditor');
    Route::get('/plans', [AdminProductController::class, 'plans'])
        ->middleware('staff.role:commercial_operator,platform_engineer,support_agent,auditor');

    // Pricing changes need a commercial owner, not any authenticated staff member.
    Route::post('/packages', [AdminProductController::class, 'storePackage'])
        ->middleware('staff.role:commercial_operator,platform_engineer');
    Route::post('/plans', [AdminProductController::class, 'storePlan'])
        ->middleware('staff.role:commercial_operator,platform_engineer');
});
