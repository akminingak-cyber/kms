<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\OriginAuthorizationController;

/*
 * Called by the origin before it serves any media object, so it runs at segment
 * request rate. Deliberately not rate limited: the only legitimate caller is
 * our own origin, restricted at the network layer by the `internal` group, and
 * a throttle firing here would stop playback for every viewer behind that
 * origin rather than slowing an abuser.
 */
Route::get('/delivery/authorize', [OriginAuthorizationController::class, 'authorize']);
