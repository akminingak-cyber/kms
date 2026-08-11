<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Http\Controllers\ChannelController;
use Modules\Schedule\Http\Controllers\ScheduleController;

// Channel metadata and the EPG are public and cacheable: they vary by locale
// and territory, never by viewer.
Route::middleware('throttle:client-read')->group(function (): void {
    Route::get('/channels', [ChannelController::class, 'index']);
    Route::get('/channels/{channelId}', [ChannelController::class, 'show']);
    Route::get('/epg', [ScheduleController::class, 'index']);
    Route::get('/channels/{channelId}/now-next', [ScheduleController::class, 'nowNext']);
});

// The personalised overlay is a separate, authenticated, private response.
Route::middleware(['client.auth', 'throttle:client-read'])->group(function (): void {
    Route::get('/entitlements/channels', [ChannelController::class, 'entitlements']);
});
