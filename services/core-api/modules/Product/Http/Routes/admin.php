<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\AdminProductController;

Route::middleware(['staff.auth', 'throttle:admin'])->group(function (): void {
    // Pricing changes need a commercial owner, not any authenticated staff member.
    Route::post('/packages', [AdminProductController::class, 'storePackage'])
        ->middleware('staff.role:commercial_operator,platform_engineer');
    Route::post('/plans', [AdminProductController::class, 'storePlan'])
        ->middleware('staff.role:commercial_operator,platform_engineer');
});
