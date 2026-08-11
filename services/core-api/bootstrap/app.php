<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Administration\Http\Middleware\AuthenticateStaff;
use Modules\Administration\Http\Middleware\RequireStaffRole;
use Modules\Identity\Http\Middleware\AuthenticateClient;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Http\Middleware\AssignCorrelationId;
use Modules\Shared\Http\Middleware\CaptureClientContext;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Module routes are registered by each module's service provider, so a
        // module owns its own surface (docs/architecture/03-repository-structure.md).
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Global, not per-group: route middleware only runs for a matched
         * route, so a 404 or a 405 would otherwise return a problem document
         * with no correlation id — and those are exactly the responses a
         * confused client reports.
         */
        $middleware->prepend(AssignCorrelationId::class);

        // Both surfaces are declared so a route naming one cannot silently
        // resolve to nothing.
        $middleware->group('client', [
            CaptureClientContext::class,
        ]);

        $middleware->group('admin', []);

        $middleware->alias([
            'client.auth' => AuthenticateClient::class,
            'staff.auth' => AuthenticateStaff::class,
            'staff.role' => RequireStaffRole::class,
        ]);

        // The API is stateless. No session, no CSRF token, no cookie.
        $middleware->statefulApi(false);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Every failure leaves as an RFC 9457 problem document with a stable
         * `code`. Clients switch on the code and never on prose, so a code's
         * meaning must never change once shipped.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $correlationId = (string) ($request->attributes->get('kms.correlation_id') ?? '');
            $instance = '/'.ltrim($request->path(), '/');

            $problem = match (true) {
                $e instanceof ApiProblem => $e,

                $e instanceof ValidationException => ApiProblem::fromValidation($e),

                $e instanceof TooManyRequestsHttpException => ApiProblem::of(
                    ErrorCode::RateLimited,
                    array_filter(['retry_after_seconds' => $e->getHeaders()['Retry-After'] ?? null]),
                ),

                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => ApiProblem::of(ErrorCode::NotFound),

                $e instanceof MethodNotAllowedHttpException => ApiProblem::of(ErrorCode::MethodNotAllowed),

                $e instanceof AuthorizationException => ApiProblem::of(ErrorCode::Forbidden),

                // A framework HTTP exception with a status we do not map
                // explicitly still leaves as a problem document rather than as
                // an HTML error page.
                $e instanceof HttpExceptionInterface => ApiProblem::of(
                    $e->getStatusCode() >= 500 ? ErrorCode::InternalError : ErrorCode::Forbidden,
                ),

                default => null,
            };

            if ($problem === null) {
                // Unhandled: report it, and return a problem document that
                // discloses nothing about the internals.
                return ApiProblem::of(ErrorCode::InternalError)
                    ->toResponse($correlationId, $instance);
            }

            return $problem->toResponse($correlationId, $instance);
        });
    })
    ->create();
