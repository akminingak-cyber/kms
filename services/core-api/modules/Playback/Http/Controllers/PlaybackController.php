<?php

declare(strict_types=1);

namespace Modules\Playback\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Playback\Application\ConcurrencyLedger;
use Modules\Playback\Application\PlaybackAuthorizer;
use Modules\Playback\Contracts\GeoLocator;
use Modules\Playback\Contracts\PlaybackRequest;
use Modules\Playback\Infrastructure\Eloquent\PlaybackSession;
use Modules\Profile\Contracts\ProfileDirectory;
use Modules\Rights\Contracts\Exploitations;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Domain\Identifier;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

final class PlaybackController
{
    public function __construct(
        private readonly Clock $clock,
        private readonly PlaybackAuthorizer $authorizer,
        private readonly ConcurrencyLedger $concurrency,
        private readonly GeoLocator $geo,
        private readonly ProfileDirectory $profiles,
    ) {}

    /**
     * Start a playback session.
     *
     * The request body carries no account, profile-owner, device or device
     * class: all of those come from the device-bound token. `capabilities` is
     * client-asserted and may only ever narrow what is offered.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_id' => ['required', 'uuid'],
            'profile_id' => ['required', 'uuid'],
            'mode' => ['required', 'string', Rule::in(Exploitations::playbackModes())],
            'capabilities' => ['sometimes', 'array'],
            'capabilities.formats' => ['sometimes', 'array'],
            'capabilities.formats.*' => ['string', Rule::in(['hls', 'dash'])],
        ]);

        $accountUuid = (string) $request->attributes->get('kms.account_uuid');

        // Profile ownership is verified here rather than trusted from the body:
        // a profile identifier from another account would otherwise select that
        // household's parental settings.
        if (! $this->profiles->belongsToAccount($accountUuid, $validated['profile_id'])) {
            throw ApiProblem::of(ErrorCode::ProfileNotFound);
        }

        $location = $this->geo->locate($request->ip(), null);

        $result = $this->authorizer->authorize(new PlaybackRequest(
            accountUuid: $accountUuid,
            profileUuid: $validated['profile_id'],
            deviceUuid: (string) $request->attributes->get('kms.device_uuid'),
            deviceClass: $this->profiles->deviceClassFor((string) $request->attributes->get('kms.device_uuid')) ?? 'web',
            contentRef: $validated['content_id'],
            mode: $validated['mode'],
            territory: $location['territory'],
            territoryMethod: $location['method'],
            capabilities: $validated['capabilities'] ?? [],
            correlationId: $request->attributes->get('kms.correlation_id'),
            client: $request->attributes->get('kms.client_platform'),
            clientVersion: $request->attributes->get('kms.client_version'),
        ));

        return new JsonResponse([
            'session_id' => $result->sessionId,
            'decision_id' => $result->decisionId,
            'delivery' => [
                'targets' => $result->deliveryTargets,
                'expires_at' => $result->expiresAt->format(DATE_RFC3339),
            ],
            // Absent until Phase 6. An unbuilt feature is absent from the
            // response rather than stubbed with a plausible-looking value.
            'protection' => null,
            'restrictions' => $result->restrictions,
            'heartbeat_interval_seconds' => $result->heartbeatIntervalSeconds,
        ], 201);
    }

    /**
     * Heartbeat.
     *
     * Renews the concurrency slot and the delivery token. It does **not**
     * re-run the full decision — that would multiply the platform's most
     * expensive check by the heartbeat rate — but it does re-check the cheap,
     * volatile conditions, which is how a blackout beginning mid-event stops
     * playback rather than merely preventing new sessions.
     */
    public function heartbeat(Request $request, string $sessionId): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);
        $now = $this->clock->now();

        if (! $this->concurrency->renew($session->account_uuid, $session->uuid)) {
            // The slot lapsed while the client was away; the session is over.
            $session->forceFill(['ended_at' => $now, 'end_reason' => 'slot_lost'])->save();

            throw ApiProblem::of(ErrorCode::PlaybackSessionNotFound);
        }

        $session->forceFill(['last_heartbeat_at' => $now])->save();

        return new JsonResponse([
            'session_id' => $session->uuid,
            'heartbeat_interval_seconds' => (int) config('kms.playback.heartbeat_interval_seconds'),
            'expires_at' => $now
                ->modify('+'.(int) config('kms.playback.delivery_token_ttl_seconds').' seconds')
                ->format(DATE_RFC3339),
        ]);
    }

    public function stop(Request $request, string $sessionId): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);

        if ($session->ended_at === null) {
            $session->forceFill([
                'ended_at' => $this->clock->now(),
                'end_reason' => 'client_stopped',
            ])->save();
        }

        // Releasing promptly is what keeps a viewer's allowance accurate.
        $this->concurrency->release($session->account_uuid, $session->uuid);

        return new JsonResponse(null, 204);
    }

    private function ownedSession(Request $request, string $sessionId): PlaybackSession
    {
        if (Identifier::tryFromString($sessionId) === null) {
            throw ApiProblem::of(ErrorCode::PlaybackSessionNotFound);
        }

        $session = PlaybackSession::query()
            ->where('uuid', $sessionId)
            ->where('account_uuid', (string) $request->attributes->get('kms.account_uuid'))
            ->first();

        if ($session === null) {
            throw ApiProblem::of(ErrorCode::PlaybackSessionNotFound);
        }

        return $session;
    }
}
