<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Device\Contracts\DeviceRegistry;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Infrastructure\Token\AccessTokenService;
use Modules\Shared\Infrastructure\Token\InvalidAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a viewer request from its access token alone.
 *
 * No read of the `identity` schema happens here. Validation is a signature
 * check plus a bounded revocation lookup, which keeps the identity store off
 * the path of every authenticated request — and, from Phase 4, off the playback
 * hot path, whose database role has no access to it at all
 * (docs/security/playback-authorization.md §7).
 *
 * The device *is* checked, because a device can be removed by its owner and
 * that removal must take effect immediately rather than at token expiry.
 */
final class AuthenticateClient
{
    public function __construct(
        private readonly AccessTokenService $tokens,
        private readonly DeviceRegistry $devices,
    ) {}

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

        // A staff token must never be usable on the client surface. Different
        // audience, different privileges, different threat model.
        if (($claims['claims']['typ'] ?? null) === 'staff') {
            throw ApiProblem::of(ErrorCode::AuthTokenInvalid);
        }

        if (! $this->devices->isActive($claims['sub'], $claims['dev'])) {
            throw ApiProblem::of(ErrorCode::DeviceNotRegistered);
        }

        /*
         * Identity comes from the token, never from the request body.
         *
         * A client-asserted account or device identifier would let a caller
         * present another device's identity and inherit its class — and device
         * class is an input to the rights and DRM decision from Phase 4, so
         * that is privilege escalation rather than a cosmetic problem.
         */
        $request->attributes->set('kms.account_uuid', $claims['sub']);
        $request->attributes->set('kms.device_uuid', $claims['dev']);
        $request->attributes->set('kms.token_id', $claims['jti']);

        return $next($request);
    }
}
