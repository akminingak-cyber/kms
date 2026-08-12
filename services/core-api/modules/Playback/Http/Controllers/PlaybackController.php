<?php

declare(strict_types=1);

namespace Modules\Playback\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Delivery\Contracts\DeliveryPlanner;
use Modules\Delivery\Contracts\DeliveryRequest;
use Modules\Delivery\Contracts\DeliveryTarget;
use Modules\Playback\Application\ConcurrencyLedger;
use Modules\Playback\Application\PlaybackAuthorizer;
use Modules\Playback\Application\SessionRevalidator;
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
        private readonly SessionRevalidator $revalidator,
        private readonly DeliveryPlanner $delivery,
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
     * Three things happen here, and the difference between them is the whole
     * design:
     *
     *  - the **concurrency slot** is renewed, every time — cheap, and what
     *    stops a crashed client holding a viewer's allowance;
     *  - the **delivery token** is reissued, every time, so the client has a
     *    fresh one before the old one expires. Returning only an expiry, with
     *    no new URLs, would leave a client watching a stream it cannot renew;
     *  - the **decision** is re-run, but only at the configured interval. It is
     *    the most expensive check in the platform and running it at heartbeat
     *    rate would multiply that cost by the heartbeat frequency. This is what
     *    makes a blackout starting mid-event stop playback rather than merely
     *    prevent new sessions — within a bounded lag that is configuration.
     */
    public function heartbeat(Request $request, string $sessionId): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);
        $now = $this->clock->now();

        if ($session->ended_at !== null) {
            throw ApiProblem::of(ErrorCode::PlaybackSessionEnded, ['reason' => $session->end_reason]);
        }

        if (! $this->concurrency->renew($session->account_uuid, $session->uuid)) {
            // The slot lapsed while the client was away; the session is over.
            $session->forceFill(['ended_at' => $now, 'end_reason' => 'slot_lost'])->save();

            throw ApiProblem::of(ErrorCode::PlaybackSessionNotFound);
        }

        if ($this->revalidator->isDue($session, $now)) {
            $location = $this->geo->locate($request->ip(), null);
            $denial = $this->revalidator->revalidate($session, $location['territory'], $location['method'], $now);

            if ($denial !== null) {
                $session->forceFill(['ended_at' => $now, 'end_reason' => $denial->value])->save();
                $this->concurrency->release($session->account_uuid, $session->uuid);

                // The specific reason, not a generic failure: a viewer told
                // "temporarily blacked out" does not contact support, and a
                // viewer told "something went wrong" does.
                throw ApiProblem::of($denial);
            }

            $session->forceFill(['revalidated_at' => $now])->save();
        }

        $session->forceFill(['last_heartbeat_at' => $now])->save();

        /*
         * Re-planned from what the session was *started* with, never from
         * today's device profile or usage rules. Re-deriving them would move a
         * live viewer between quality classes mid-programme, with nothing in
         * the record explaining why.
         */
        $targets = $this->delivery->plan(new DeliveryRequest(
            subjectType: (string) $session->content_type,
            subjectRef: (string) $session->content_ref,
            mode: (string) $session->mode,
            sessionUuid: (string) $session->uuid,
            deviceClass: (string) $session->device_class,
            qualityCaps: array_values((array) ($session->quality_caps ?? [])),
            capabilities: (array) ($session->capabilities ?? []),
        ));

        if ($targets === []) {
            // The publication was retired while this session was playing.
            $session->forceFill(['ended_at' => $now, 'end_reason' => ErrorCode::PlaybackNoDeliveryTarget->value])->save();
            $this->concurrency->release($session->account_uuid, $session->uuid);

            throw ApiProblem::of(ErrorCode::PlaybackNoDeliveryTarget);
        }

        return new JsonResponse([
            'session_id' => $session->uuid,
            'heartbeat_interval_seconds' => (int) config('kms.playback.heartbeat_interval_seconds'),
            'delivery' => [
                'targets' => array_map(static fn (DeliveryTarget $t): array => $t->toArray(), $targets),
                'expires_at' => $targets[0]->expiresAt->format(DATE_RFC3339),
            ],
        ]);
    }

    /**
     * The viewer's own live sessions.
     *
     * "You are watching on too many devices" is unactionable without being able
     * to see which devices, so this is what makes the concurrency denial
     * something a viewer can resolve rather than only be blocked by.
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = PlaybackSession::query()
            ->where('account_uuid', (string) $request->attributes->get('kms.account_uuid'))
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();

        return new JsonResponse([
            'data' => $sessions->map(static fn (PlaybackSession $session): array => [
                'session_id' => $session->uuid,
                'content_id' => $session->content_ref,
                'mode' => $session->mode,
                'device_id' => $session->device_uuid,
                'device_class' => $session->device_class,
                'started_at' => $session->started_at->format(DATE_RFC3339),
                'last_heartbeat_at' => $session->last_heartbeat_at->format(DATE_RFC3339),
            ])->all(),
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
