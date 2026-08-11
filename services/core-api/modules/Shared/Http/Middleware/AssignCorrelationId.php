<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * One identifier links a client action to its request, its audit entries and
 * its log lines — and, from Phase 4, to the playback decision, the CDN request
 * and the licence request.
 *
 * Without that chain, "it doesn't play on my TV" is guesswork.
 */
final class AssignCorrelationId
{
    public const HEADER = 'X-Correlation-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $supplied = $request->header(self::HEADER);

        // A client-supplied value is accepted so a client can correlate its own
        // telemetry, but it is validated: an unbounded header would otherwise
        // end up in log lines and audit rows.
        $correlationId = is_string($supplied) && preg_match('/^[A-Za-z0-9._:-]{8,64}$/', $supplied) === 1
            ? $supplied
            : (string) Str::uuid7();

        $request->attributes->set('kms.correlation_id', $correlationId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $correlationId);

        return $response;
    }
}
