<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Playback\Http\Controllers\AdminPlaybackController;

/*
 * The support surface over playback.
 *
 * Read-only, by construction. Nothing here can change a decision, end a session
 * or grant access — an operator who could quietly override a denial would make
 * the decision log a record of what the platform decided rather than of what
 * actually happened, and that log is what a licensor's questions are answered
 * from.
 *
 * A rights manager is included because a licensor's query about a specific date
 * lands with them, and the decision log is where the answer is.
 */
Route::middleware(['staff.auth', 'throttle:admin', 'staff.role:support_agent,platform_engineer,auditor,rights_manager'])
    ->prefix('playback')
    ->group(function (): void {
        Route::get('/decisions', [AdminPlaybackController::class, 'decisions']);
        Route::get('/sessions', [AdminPlaybackController::class, 'sessions']);
    });
