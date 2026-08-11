<?php

declare(strict_types=1);

namespace Modules\Device\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Device\Application\ActivationService;
use Modules\Device\Domain\DeviceClass;
use Modules\Identity\Contracts\SessionIssuer;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * Television activation.
 *
 * Proven end to end in Phase 1, before any TV application exists, because it is
 * the hardest authentication interaction in the platform and the one that most
 * affects sign-up conversion. Discovering its problems in Phase 9 would be
 * expensive.
 */
final class ActivationController
{
    public function __construct(
        private readonly ActivationService $activation,
        private readonly SessionIssuer $sessions,
    ) {}

    /** Called by the television. Unauthenticated by nature. */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_class' => ['required', 'string', Rule::in(DeviceClass::pairingValues())],
            'device_name' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $result = $this->activation->start(
            $validated['device_class'],
            $validated['device_name'],
            $request->ip(),
        );

        return new JsonResponse([
            // Shown on the television screen.
            'user_code' => $result['user_code'],
            // The polling secret. As sensitive as a refresh token.
            'device_code' => $result['device_code'],
            'verification_uri' => rtrim((string) config('app.url'), '/').'/activate',
            'expires_at' => $result['expires_at']->format(DATE_RFC3339),
            'interval' => $result['interval'],
        ], 201);
    }

    /**
     * Called by the television, repeatedly, until approved.
     *
     * Returns 428 while pending rather than an error, so a client can tell
     * "keep waiting" apart from "give up".
     */
    public function poll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:255'],
        ]);

        $code = $this->activation->redeem($validated['device_code']);

        if ($code->account_uuid === null || $code->device_uuid === null) {
            throw ApiProblem::of(ErrorCode::ActivationInvalidCode);
        }

        $tokens = $this->sessions->issueForActivatedDevice($code->account_uuid, $code->device_uuid);

        return new JsonResponse([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['expires_in'],
            'refresh_expires_at' => $tokens['refresh_expires_at']->format(DATE_RFC3339),
            'device_id' => $code->device_uuid,
        ]);
    }

    /**
     * Called from an authenticated session on a device that has a keyboard.
     *
     * The response names the device being approved, so the account holder sees
     * what they are authorising rather than approving a blank.
     */
    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_code' => ['required', 'string', 'min:4', 'max:16'],
        ]);

        $accountUuid = (string) $request->attributes->get('kms.account_uuid');
        $code = $this->activation->approve($accountUuid, $validated['user_code']);

        return new JsonResponse([
            'device' => [
                'id' => $code->device_uuid,
                'name' => $code->name,
                'device_class' => $code->device_class,
            ],
        ]);
    }
}
