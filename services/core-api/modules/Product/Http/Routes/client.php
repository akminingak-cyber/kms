<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\PackageController;

Route::middleware('throttle:client-read')->group(function (): void {
    Route::get('/packages', [PackageController::class, 'index']);
    Route::get('/plans', [PackageController::class, 'plans']);
});
