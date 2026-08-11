<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Profile\Http\Controllers\ProfileController;

Route::middleware('client.auth')->prefix('profiles')->group(function (): void {
    Route::get('/', [ProfileController::class, 'index'])->middleware('throttle:client-read');
    Route::post('/', [ProfileController::class, 'store'])->middleware('throttle:client-write');
    Route::get('/{profileId}', [ProfileController::class, 'show'])->middleware('throttle:client-read');
    Route::patch('/{profileId}', [ProfileController::class, 'update'])->middleware('throttle:client-write');
    Route::delete('/{profileId}', [ProfileController::class, 'destroy'])->middleware('throttle:client-write');
});
