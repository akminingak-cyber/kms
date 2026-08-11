<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\SubscriptionController;

Route::middleware('client.auth')->prefix('subscription')->group(function (): void {
    Route::get('/', [SubscriptionController::class, 'show'])->middleware('throttle:client-read');
    Route::post('/', [SubscriptionController::class, 'store'])->middleware('throttle:client-write');
    Route::post('/{subscriptionId}/cancel', [SubscriptionController::class, 'cancel'])->middleware('throttle:client-write');
    Route::post('/{subscriptionId}/resume', [SubscriptionController::class, 'resume'])->middleware('throttle:client-write');
});
