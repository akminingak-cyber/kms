<?php

declare(strict_types=1);

namespace Modules\Administration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Infrastructure\Token\AccessTokenService;
use Modules\Shared\Infrastructure\Token\InvalidAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the admin surface.
 *
 * Role authorization is applied per route on top of this, so a route that
 * forgets to declare a role is not silently open to every staff member — it is
 * closed, because `RequireStaffRole` is what grants access, not this.
 */
final class AuthenticateStaff
{
    public function __construct(private readonly AccessTokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            throw ApiProblem::of(ErrorCode::AuthRequired);
        }

        try {
            $claims = $this->tokens->verify(substr($header, 7));
        } catch (InvalidAccessToken $e) {
            throw ApiProblem::of($e->errorCode);
        }

        // A viewer token must never reach the admin surface.
        if (($claims['claims']['typ'] ?? null) !== 'staff') {
            throw ApiProblem::of(ErrorCode::Forbidden);
        }

        $role = $claims['claims']['role'] ?? null;

        if (! is_string($role)) {
            throw ApiProblem::of(ErrorCode::Forbidden);
        }

        $request->attributes->set('kms.staff_uuid', $claims['sub']);
        $request->attributes->set('kms.staff_role', $role);

        return $next($request);
    }
}
