<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Identity\Application\AuthenticateAccount;
use Modules\Identity\Application\EmailVerificationService;
use Modules\Identity\Application\PasswordResetService;
use Modules\Identity\Application\RegisterAccount;
use Modules\Identity\Application\SessionService;
use Modules\Identity\Domain\EmailAddress;
use Modules\Identity\Http\Requests\LoginRequest;
use Modules\Identity\Http\Requests\RegisterRequest;
use Modules\Identity\Http\Requests\ResetPasswordRequest;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Infrastructure\Token\AccessTokenService;

/**
 * Delivery mechanism only: validate, delegate, present.
 * There is no business logic in this class by design.
 */
final class AuthController
{
    public function __construct(
        private readonly RegisterAccount $register,
        private readonly AuthenticateAccount $authenticate,
        private readonly SessionService $sessions,
        private readonly EmailVerificationService $verification,
        private readonly PasswordResetService $passwordReset,
        private readonly AccessTokenService $accessTokens,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->register->handle(
            EmailAddress::fromString($request->string('email')->toString()),
            $request->string('password')->toString(),
            $request->string('profile_name')->toString(),
            $request->string('locale', 'en')->toString(),
        );

        /*
         * The verification token is delivered by email. It is logged here only
         * because mail delivery is a Phase 2 concern and the local stack has no
         * outbound mail — the log channel is the local mail capture. It is
         * never returned in the response, and never logged outside `local`.
         */
        if (app()->environment('local', 'testing')) {
            Log::debug('identity.email_verification.issued', [
                'account_uuid' => $result['account']->uuid,
                'token' => $result['verification_token'],
            ]);
        }

        return new JsonResponse([
            'account' => [
                'id' => $result['account']->uuid,
                'email' => $result['account']->email,
                'email_verified' => false,
            ],
            'primary_profile_id' => $result['profile_uuid'],
        ], 201);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->string('token')->toString();

        if ($token === '') {
            throw ApiProblem::of(ErrorCode::AccountVerificationInvalid);
        }

        $account = $this->verification->verify($token);

        return new JsonResponse([
            'account' => ['id' => $account->uuid, 'email_verified' => true],
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authenticate->handle(
            EmailAddress::fromString($request->string('email')->toString()),
            $request->string('password')->toString(),
            $request->string('device_class')->toString(),
            $request->string('device_name')->toString(),
            $request->input('device_fingerprint'),
            $request->ip(),
            $request->userAgent(),
        );

        return new JsonResponse($this->sessionPayload($result['tokens'], $result['device_uuid']));
    }

    public function refresh(Request $request): JsonResponse
    {
        $presented = $request->string('refresh_token')->toString();

        if ($presented === '') {
            throw ApiProblem::of(ErrorCode::AuthTokenInvalid);
        }

        $tokens = $this->sessions->rotate($presented);

        return new JsonResponse($this->sessionPayload($tokens, null));
    }

    public function logout(Request $request): JsonResponse
    {
        $presented = $request->string('refresh_token')->toString();

        if ($presented !== '') {
            $this->sessions->revokeByToken($presented);
        }

        // The access token is short-lived but still valid until it expires, so
        // its id goes into the revocation set as well. Bounded lifetime, so the
        // set cannot grow without limit.
        $tokenId = $request->attributes->get('kms.token_id');

        if (is_string($tokenId)) {
            $this->accessTokens->revoke($tokenId);
        }

        return new JsonResponse(null, 204);
    }

    public function requestPasswordReset(Request $request): JsonResponse
    {
        $email = $request->string('email')->toString();

        try {
            $token = $this->passwordReset->request(EmailAddress::fromString($email));
        } catch (\InvalidArgumentException) {
            $token = null;
        }

        if ($token !== null && app()->environment('local', 'testing')) {
            Log::debug('identity.password_reset.issued', ['email' => $email, 'token' => $token]);
        }

        /*
         * Always 202, whether or not the address is registered.
         *
         * This endpoint is unauthenticated; distinguishing the two cases would
         * turn it into an account-enumeration oracle.
         */
        return new JsonResponse(null, 202);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordReset->reset(
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        return new JsonResponse(null, 204);
    }

    /**
     * @param  array<string,mixed>  $tokens
     * @return array<string,mixed>
     */
    private function sessionPayload(array $tokens, ?string $deviceUuid): array
    {
        $payload = [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['expires_in'],
            'refresh_expires_at' => $tokens['refresh_expires_at']->format(DATE_RFC3339),
        ];

        if ($deviceUuid !== null) {
            $payload['device_id'] = $deviceUuid;
        }

        return $payload;
    }
}
