<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Administration\Http\Controllers\AuditController;

/*
 * Admin surface — /api/admin/v1
 *
 * Served on a separate hostname and network policy in production
 * (docs/api/surfaces.md). Authentication alone grants nothing: every route
 * names the roles that may reach it, so a route that forgets to is closed.
 */
Route::post('/auth/sign-in', [AuditController::class, 'signIn'])
    ->middleware('throttle:auth-ip');

Route::middleware(['staff.auth', 'throttle:admin'])->group(function (): void {
    // Every authenticated staff member may ask who they are. The panel cannot
    // render its navigation without it, and it discloses nothing the caller
    // does not already hold a token for.
    Route::get('/me', [AuditController::class, 'me']);

    Route::get('/audit', [AuditController::class, 'index'])
        ->middleware('staff.role:auditor,platform_engineer,support_agent');

    // An access review needs a list. Support agents are excluded: knowing who
    // else has access is not part of answering a viewer's question.
    Route::get('/staff', [AuditController::class, 'staff'])
        ->middleware('staff.role:auditor,platform_engineer');
});
