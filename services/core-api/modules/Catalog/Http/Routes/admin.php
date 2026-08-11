<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AdminCategoryController;

Route::middleware(['staff.auth', 'throttle:admin'])->group(function (): void {
    Route::post('/categories', [AdminCategoryController::class, 'store'])
        ->middleware('staff.role:content_operator,platform_engineer');
});
