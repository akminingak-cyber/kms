<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Http\Controllers\AdminScheduleController;

Route::middleware(['staff.auth', 'throttle:admin'])->group(function (): void {
    Route::post('/channels', [AdminScheduleController::class, 'storeChannel'])
        ->middleware('staff.role:content_operator,platform_engineer');
    Route::post('/schedule/ingest', [AdminScheduleController::class, 'ingest'])
        ->middleware('staff.role:content_operator,platform_engineer');
});
