<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Liveness and readiness are different questions, and conflating them causes
 * outages: a liveness probe that checks the database restarts every application
 * instance when the database has a hiccup, turning a recoverable dependency
 * failure into a total one.
 */
final class HealthController
{
    /** Liveness: is this process able to serve at all? No dependencies checked. */
    public function live(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    /** Readiness: should this instance receive traffic? Dependencies checked. */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->check(static fn () => DB::select('select 1')),
            'redis' => $this->check(static fn () => Redis::connection()->ping()),
        ];

        $healthy = ! in_array(false, $checks, true);

        return new JsonResponse(
            ['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks],
            $healthy ? 200 : 503,
        );
    }

    private function check(callable $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (Throwable) {
            // The reason is logged, never returned: a readiness endpoint that
            // reports connection strings or driver errors is reconnaissance.
            return false;
        }
    }
}
