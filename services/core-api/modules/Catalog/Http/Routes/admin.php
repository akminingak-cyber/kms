<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AdminCategoryController;

Route::middleware(['staff.auth', 'throttle:admin'])->group(function (): void {
    // Reading the taxonomy is wider than editing it: support and rights both
    // need to see how content is organised to answer their own questions.
    Route::get('/categories', [AdminCategoryController::class, 'index'])
        ->middleware('staff.role:content_operator,platform_engineer,support_agent,rights_manager,auditor');

    Route::post('/categories', [AdminCategoryController::class, 'store'])
        ->middleware('staff.role:content_operator,platform_engineer');
});
