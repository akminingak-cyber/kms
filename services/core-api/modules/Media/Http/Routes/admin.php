<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\AdminMediaController;

/*
 * The media control plane is an engineering surface, not a content one.
 *
 * A ladder change alters encoding cost, storage cost and picture quality
 * simultaneously, and a packaging change invalidates every cached object for
 * the content it applies to. Neither belongs to the same role that edits a
 * programme description.
 */
Route::middleware(['staff.auth', 'throttle:admin', 'staff.role:platform_engineer'])
    ->prefix('media')
    ->group(function (): void {
        Route::get('/ladders', [AdminMediaController::class, 'listLadders']);
        Route::post('/ladders', [AdminMediaController::class, 'storeLadder']);
        Route::post('/ladders/{ladderId}/ffmpeg-command', [AdminMediaController::class, 'ffmpegCommand']);

        Route::post('/packaging-profiles', [AdminMediaController::class, 'storePackagingProfile']);

        Route::post('/publications', [AdminMediaController::class, 'storePublication']);
        Route::post('/publications/{publicationId}/publish', [AdminMediaController::class, 'publish']);
        Route::post('/publications/{publicationId}/retire', [AdminMediaController::class, 'retire']);
        // The exact bytes a player receives, because a manifest is a client
        // contract and is reviewed as one.
        Route::get('/publications/{publicationId}/manifests/{policy}/{format}', [AdminMediaController::class, 'showManifest']);

        Route::put('/device-profiles/{deviceClass}', [AdminMediaController::class, 'upsertDeviceProfile']);
    });
