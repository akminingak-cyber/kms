<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Http\Controllers\AdminScheduleController;

Route::middleware(['staff.auth', 'throttle:admin'])->group(function (): void {
    /*
     * Read access is wider than write access, deliberately. A support agent
     * fielding "why can't I watch this channel?" and a rights manager checking
     * what a licence applies to both need to see the channel; neither should be
     * able to create one.
     */
    Route::get('/channels', [AdminScheduleController::class, 'channels'])
        ->middleware('staff.role:content_operator,platform_engineer,support_agent,rights_manager,commercial_operator,auditor');
    Route::get('/channels/{channelId}', [AdminScheduleController::class, 'channel'])
        ->middleware('staff.role:content_operator,platform_engineer,support_agent,rights_manager,commercial_operator,auditor');

    Route::post('/channels', [AdminScheduleController::class, 'storeChannel'])
        ->middleware('staff.role:content_operator,platform_engineer');
    Route::post('/schedule/ingest', [AdminScheduleController::class, 'ingest'])
        ->middleware('staff.role:content_operator,platform_engineer');
});
