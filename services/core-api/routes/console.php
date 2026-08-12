<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Reclaims sessions whose client stopped heartbeating.
 *
 * Scheduled rather than event-driven, because the event being reacted to is the
 * *absence* of one. `withoutOverlapping` because a long-running sweep must not
 * stack up behind itself, and `onOneServer` because every application instance
 * runs this scheduler and only one of them should do the work.
 */
Schedule::command('kms:playback:reap-sessions')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
