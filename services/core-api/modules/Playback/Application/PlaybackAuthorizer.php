<?php

declare(strict_types=1);

namespace Modules\Playback\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Delivery\Contracts\DeliveryPlanner;
use Modules\Delivery\Contracts\DeliveryRequest;
use Modules\Delivery\Contracts\DeliveryTarget;
use Modules\Entitlement\Contracts\EntitlementQuery;
use Modules\Playback\Contracts\PlaybackRequest;
use Modules\Playback\Contracts\PlaybackResult;
use Modules\Playback\Domain\TerritoryDetermination;
use Modules\Playback\Infrastructure\Eloquent\Decision;
use Modules\Playback\Infrastructure\Eloquent\PlaybackSession;
use Modules\Profile\Contracts\ParentalPolicy;
use Modules\Rights\Contracts\AvailabilityQuery;
use Modules\Rights\Contracts\AvailabilityRequest;
use Modules\Rights\Contracts\AvailabilityVerdict;
use Modules\Schedule\Contracts\ChannelDirectory;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Throwable;

/**
 * The decision.
 *
 * The highest-criticality path in the platform: if it is wrong, viewers see
 * content they should not, or paying viewers cannot watch.
 *
 *   CAN PLAY = Rights ∧ Entitlement ∧ Parental ∧ Device/Concurrency ∧ Territory
 *
 * Seven checks, cheapest first, and the **first failure wins**, so an abusive
 * client is rejected before expensive work happens. Rights is evaluated
 * **before** entitlement deliberately: if content is not licensed for this
 * territory that is true regardless of what the customer bought, and telling
 * them to upgrade their package would be wrong and would generate a support
 * contact and a refund request.
 */
final readonly class PlaybackAuthorizer
{
    public function __construct(
        private Clock $clock,
        private ChannelDirectory $channels,
        private AvailabilityQuery $rights,
        private EntitlementQuery $entitlements,
        private ParentalPolicy $parental,
        private ConcurrencyLedger $concurrency,
        private DeliveryPlanner $delivery,
    ) {}

    /** @throws ApiProblem */
    public function authorize(PlaybackRequest $request): PlaybackResult
    {
        $now = $this->clock->now();

        // 1. Content exists and is playable.
        $channel = $this->channels->find($request->contentRef);

        if ($channel === null || ! $channel->isPlayable) {
            throw $this->deny($request, ErrorCode::PlaybackContentUnavailable, $now);
        }

        // 2. Territory. The determination *and its method* are recorded,
        //    because a licensor asking how we concluded a viewer was in a given
        //    country must be answered from a record.
        $territory = $request->territory;

        if ($territory === null) {
            throw $this->deny($request, ErrorCode::PlaybackTerritoryUndetermined, $now);
        }

        // 3. Rights. Fail closed: unauthorized playback is a contract breach,
        //    and refusing is recoverable where breaching is not.
        try {
            $verdict = $this->rights->resolve(new AvailabilityRequest(
                subjectType: 'channel',
                subjectRef: $request->contentRef,
                exploitation: $request->mode,
                territory: $territory,
                platform: $request->deviceClass,
                monetization: 'svod',
                at: $now,
            ));
        } catch (Throwable) {
            throw $this->deny($request, ErrorCode::PlaybackTemporarilyUnavailable, $now);
        }

        if (! $verdict->permitted) {
            throw $this->deny($request, match ($verdict->reason) {
                AvailabilityVerdict::DENIED_BLACKOUT => ErrorCode::PlaybackBlackedOut,
                AvailabilityVerdict::DENIED_TERRITORY => ErrorCode::PlaybackNotAvailableInTerritory,
                AvailabilityVerdict::DENIED_PLATFORM => ErrorCode::PlaybackPlatformNotPermitted,
                AvailabilityVerdict::DENIED_WINDOW => ErrorCode::PlaybackOutsideLicenceWindow,
                AvailabilityVerdict::DENIED_MONETIZATION,
                AvailabilityVerdict::DENIED_EXPLOITATION => ErrorCode::PlaybackModeNotPermitted,
                default => ErrorCode::PlaybackOutsideLicenceWindow,
            }, $now, $verdict);
        }

        // 4. Entitlement, read from the materialised snapshot — never from
        //    billing. Billing being down must not stop existing subscribers
        //    watching.
        $snapshot = $this->entitlements->snapshotFor($request->accountUuid);

        if ($snapshot === null || ! $snapshot->includesChannel($request->contentRef)) {
            throw $this->deny($request, ErrorCode::PlaybackNotEntitled, $now, $verdict);
        }

        // 5. Parental. Enforced server-side; the client only renders the outcome.
        if (! $this->parental->permits($request->accountUuid, $request->profileUuid, $channel->ageRating)) {
            throw $this->deny($request, ErrorCode::PlaybackParentalBlocked, $now, $verdict);
        }

        // 6. Concurrency. Two independent sources — the plan (commercial) and
        //    the licensor's usage rules (contractual). The lower always wins,
        //    and they fail differently under infrastructure failure.
        $licensorCap = $verdict->usageRules?->concurrencyCap;
        $planLimit = $snapshot->concurrencyLimit;
        $effectiveLimit = $licensorCap === null ? $planLimit : min($planLimit, $licensorCap);
        $boundBy = ($licensorCap !== null && $licensorCap <= $planLimit) ? 'licensor' : 'plan';

        $sessionUuid = (string) Str::uuid7();
        $slot = $this->concurrency->acquire(
            $request->accountUuid,
            $sessionUuid,
            $effectiveLimit,
            // A licensor cap may never fail open: exceeding it is a breach,
            // exactly like ignoring a territory. A plan limit may.
            failOpenPermitted: $boundBy === 'plan',
        );

        if (! $slot->granted) {
            throw $this->deny(
                $request,
                $slot->reason === 'unavailable'
                    ? ErrorCode::PlaybackTemporarilyUnavailable
                    : ErrorCode::PlaybackConcurrencyExceeded,
                $now,
                $verdict,
                ['limit' => $effectiveLimit, 'bound_by' => $boundBy],
            );
        }

        $restrictions = $verdict->usageRules?->toArray() ?? [];

        /*
         * 7. Somewhere to actually play it from.
         *
         * Planned *before* the session is written, because a channel that
         * nobody has encoded is a denial, not an allow with an empty target
         * list. Handing a player zero targets produces an unexplained client
         * error and no server-side signal at all; a recorded denial with a
         * specific code produces both.
         *
         * The resolution caps are resolved here, where the decision is made and
         * recorded, and passed to Delivery already combined. Delivery addresses
         * media and signs URLs — it does not get the inputs to a decision.
         */
        $qualityCaps = array_values(array_filter([
            $verdict->usageRules?->maxResolution,
            $snapshot->maxResolution,
        ]));

        $targets = $this->delivery->plan(new DeliveryRequest(
            subjectType: 'channel',
            subjectRef: $request->contentRef,
            mode: $request->mode,
            sessionUuid: $sessionUuid,
            deviceClass: $request->deviceClass,
            qualityCaps: $qualityCaps,
            capabilities: $request->capabilities,
        ));

        if ($targets === []) {
            $this->concurrency->release($request->accountUuid, $sessionUuid);

            throw $this->deny($request, ErrorCode::PlaybackNoDeliveryTarget, $now, $verdict);
        }

        try {
            /*
             * The session, the decision record and the slot mirror are written
             * together. If the decision cannot be recorded, playback does not
             * start: an unauditable decision is indefensible to a licensor.
             */
            $decisionUuid = DB::transaction(function () use ($request, $sessionUuid, $now, $verdict, $snapshot, $territory, $restrictions, $boundBy, $qualityCaps): string {
                PlaybackSession::query()->create([
                    'uuid' => $sessionUuid,
                    'account_uuid' => $request->accountUuid,
                    'profile_uuid' => $request->profileUuid,
                    'device_uuid' => $request->deviceUuid,
                    'device_class' => $request->deviceClass,
                    'content_type' => 'channel',
                    'content_ref' => $request->contentRef,
                    'mode' => $request->mode,
                    'started_at' => $now,
                    'last_heartbeat_at' => $now,
                    'revalidated_at' => $now,
                    // What this session was planned with. Re-deriving these on
                    // heartbeat would silently change a live viewer's quality
                    // class when a usage rule or a device profile changed.
                    'capabilities' => $request->capabilities,
                    'quality_caps' => $qualityCaps,
                ]);

                $this->concurrency->mirror($request->accountUuid, $sessionUuid, $now);

                return $this->record(
                    $request,
                    'allow',
                    null,
                    $now,
                    $verdict,
                    $sessionUuid,
                    $territory,
                    $snapshot->grantIds,
                    $restrictions + ['concurrency_bound_by' => $boundBy],
                );
            });
        } catch (Throwable) {
            $this->concurrency->release($request->accountUuid, $sessionUuid);

            throw ApiProblem::of(ErrorCode::PlaybackTemporarilyUnavailable);
        }

        return new PlaybackResult(
            sessionId: $sessionUuid,
            decisionId: $decisionUuid,
            deliveryTargets: array_map(static fn (DeliveryTarget $t): array => $t->toArray(), $targets),
            restrictions: $restrictions,
            heartbeatIntervalSeconds: (int) config('kms.playback.heartbeat_interval_seconds'),
            expiresAt: $targets[0]->expiresAt,
        );
    }

    /**
     * Record the denial, then throw.
     *
     * Denials are recorded as deliberately as grants: a licensor asking why
     * their content was refused, or a support agent asking why a subscriber
     * cannot watch, both need the same record.
     *
     * @param  array<string,mixed>  $meta
     */
    private function deny(
        PlaybackRequest $request,
        ErrorCode $code,
        \DateTimeImmutable $now,
        ?AvailabilityVerdict $verdict = null,
        array $meta = [],
    ): ApiProblem {
        try {
            $this->record($request, 'deny', $code->value, $now, $verdict, null, $request->territory, [], $meta);
        } catch (Throwable) {
            // A denial that cannot be recorded is still a denial: refusing to
            // deny would be strictly worse than an incomplete audit trail.
        }

        return ApiProblem::of($code, $meta);
    }

    /**
     * @param  list<string>  $grantIds
     * @param  array<string,mixed>  $restrictions
     */
    private function record(
        PlaybackRequest $request,
        string $outcome,
        ?string $reasonCode,
        \DateTimeImmutable $now,
        ?AvailabilityVerdict $verdict,
        ?string $sessionUuid,
        ?string $territory,
        array $grantIds,
        array $restrictions,
    ): string {
        $uuid = (string) Str::uuid7();

        Decision::query()->create([
            'uuid' => $uuid,
            'outcome' => $outcome,
            'reason_code' => $reasonCode,
            'account_uuid' => $request->accountUuid,
            'profile_uuid' => $request->profileUuid,
            'device_uuid' => $request->deviceUuid,
            'device_class' => $request->deviceClass,
            'session_uuid' => $sessionUuid,
            'content_type' => 'channel',
            'content_ref' => $request->contentRef,
            'mode' => $request->mode,
            'availability_version' => $verdict?->availabilityVersion,
            'rights_rule_ids' => $verdict?->sourceRuleIds ?? [],
            'entitlement_grant_ids' => $grantIds,
            'territory' => $territory,
            'territory_method' => $request->territoryMethod ?? TerritoryDetermination::UNDETERMINED,
            'applied_restrictions' => $restrictions === [] ? null : $restrictions,
            'concurrency_source' => $restrictions['concurrency_bound_by'] ?? null,
            'correlation_id' => $request->correlationId,
            'client' => $request->client,
            'client_version' => $request->clientVersion,
            'decided_at' => $now,
        ]);

        return $uuid;
    }
}
