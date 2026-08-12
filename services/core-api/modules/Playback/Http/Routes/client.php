<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Playback\Http\Controllers\PlaybackController;

/*
 * The critical path.
 *
 * Its own rate limiter: deliberately high, because a television retries hard on
 * a flaky connection, but not unlimited.
 */
Route::middleware(['client.auth', 'throttle:playback'])->prefix('playback')->group(function (): void {
    Route::get('/sessions', [PlaybackController::class, 'index']);
    Route::post('/sessions', [PlaybackController::class, 'start']);
    Route::post('/sessions/{sessionId}/heartbeat', [PlaybackController::class, 'heartbeat']);
    Route::post('/sessions/{sessionId}/stop', [PlaybackController::class, 'stop']);
});
