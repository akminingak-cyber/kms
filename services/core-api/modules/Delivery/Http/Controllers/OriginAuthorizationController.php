<?php

declare(strict_types=1);

namespace Modules\Delivery\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Delivery\Domain\DeliveryToken;
use Modules\Delivery\Domain\DeliveryTokenVerdict;
use Modules\Media\Contracts\OriginAddressing;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * The origin's authorization sub-request.
 *
 * Nginx calls this with `auth_request` before serving any media object; a 204
 * serves it, anything else refuses it
 * (`infrastructure/nginx/origin.conf`).
 *
 * ## No database read, ever
 *
 * This runs once per manifest **and once per segment**, which at any real
 * concurrency is the highest request rate in the platform by a wide margin. A
 * single database read here would put the control-plane database on the media
 * path and make an origin outage a database outage. The delivery token is
 * therefore self-contained: the prefix comes from the request path, the
 * signature is checked against it, and nothing else is consulted.
 *
 * The consequence is stated rather than hidden: **ending a session takes effect
 * within the delivery-token lifetime, not instantly.** A stopped or displaced
 * session keeps playing until its token expires and the client fails to renew
 * it on heartbeat. That bound is a configured number of minutes, it is the
 * reason the token lifetime is short, and it is the correct trade — the
 * alternative buys instant revocation at the price of a per-segment database
 * read.
 */
final class OriginAuthorizationController
{
    public function __construct(
        private readonly Clock $clock,
        private readonly DeliveryToken $token,
    ) {}

    public function authorize(Request $request): Response|JsonResponse
    {
        /*
         * `auth_request` discards the original request line, so nginx passes it
         * back in a header. Falling back to the query string keeps the endpoint
         * directly testable with a normal request.
         */
        $uri = (string) ($request->header('X-Original-URI') ?? $request->query('uri', ''));

        $path = (string) parse_url($uri, PHP_URL_PATH);
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        $prefix = $this->prefixOf($path);
        $token = (string) ($query[DeliveryToken::PARAMETER] ?? '');

        if ($prefix === null || $token === '') {
            return $this->refuse($request, ErrorCode::DeliveryPathNotPermitted, $path, 'unaddressable');
        }

        $verdict = $this->token->verify($prefix, $token, $this->clock->now()->getTimestamp());

        if (! $verdict->isValid()) {
            return $this->refuse(
                $request,
                $verdict->outcome === DeliveryTokenVerdict::EXPIRED
                    ? ErrorCode::DeliveryTokenExpired
                    : ErrorCode::DeliveryTokenInvalid,
                $path,
                $verdict->outcome,
            );
        }

        /*
         * A body would be discarded by `auth_request` anyway. The session is
         * echoed in a header so the origin's access log can be joined to the
         * decision log by session — which is what makes a single viewer
         * complaint traceable from the play button to the segment.
         */
        return response()->noContent(204, [
            'X-KMS-Session' => (string) $verdict->sessionUuid,
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * The publication prefix a path belongs to.
     *
     * Derived by a strict pattern rather than by string splitting: this value
     * is what the signature is checked against, so anything that does not
     * match a publication prefix exactly must be refused rather than coerced
     * into one.
     */
    private function prefixOf(string $path): ?string
    {
        if (preg_match('#^(/(?:live|vod)/[0-9a-f-]{36})(/|$)#i', $path, $matches) !== 1) {
            return null;
        }

        $prefix = strtolower($matches[1]);

        // Round-trips through the addressing rules, so the origin and the
        // planner can never disagree about what a prefix is.
        try {
            return OriginAddressing::fromPrefix($prefix)->covers($path) ? $prefix : null;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function refuse(Request $request, ErrorCode $code, string $path, string $outcome): JsonResponse
    {
        /*
         * Refusals are logged with their reason because the rates mean
         * different things: expiries mean clients are failing to renew on
         * heartbeat, and forgeries mean an attack or a client bug. Both need
         * to be visible, and conflating them hides both.
         */
        Log::warning('delivery.token.refused', [
            'outcome' => $outcome,
            'path' => $path,
            'correlation_id' => $request->attributes->get('kms.correlation_id'),
        ]);

        return ApiProblem::of($code)->toResponse(
            (string) ($request->attributes->get('kms.correlation_id') ?? ''),
            '/'.ltrim($request->path(), '/'),
        );
    }
}
