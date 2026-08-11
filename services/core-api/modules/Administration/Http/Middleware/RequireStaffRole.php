<?php

declare(strict_types=1);

namespace Modules\Administration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based authorization for the admin surface.
 *
 * Default deny: a route that names no role gets no access. Absence of a
 * permission is a prohibition, never a permission.
 */
final class RequireStaffRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->attributes->get('kms.staff_role');

        if ($roles === [] || ! is_string($role) || ! in_array($role, $roles, true)) {
            throw ApiProblem::of(ErrorCode::Forbidden);
        }

        return $next($request);
    }
}
