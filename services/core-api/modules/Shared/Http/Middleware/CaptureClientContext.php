<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records which client build made the request.
 *
 * This is what makes installed-version distribution measurable — and without
 * that measurement no API major version can ever be retired safely, because
 * nobody can say whether a television released three years ago still calls it
 * (docs/api/versioning.md §4).
 *
 * The headers are deliberately *not* allowed to vary a cacheable response: one
 * cache entry per client build would fragment the cache to uselessness.
 */
final class CaptureClientContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $platform = (string) $request->header('X-KMS-Client', '');
        $version = (string) $request->header('X-KMS-Client-Version', '');

        $known = (array) config('kms.clients.platforms');

        $request->attributes->set(
            'kms.client_platform',
            in_array($platform, $known, true) ? $platform : null,
        );

        $request->attributes->set(
            'kms.client_version',
            preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/', $version) === 1 ? $version : null,
        );

        return $next($request);
    }
}
