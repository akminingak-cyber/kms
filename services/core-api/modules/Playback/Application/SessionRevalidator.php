<?php

declare(strict_types=1);

namespace Modules\Playback\Application;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Entitlement\Contracts\EntitlementQuery;
use Modules\Playback\Domain\TerritoryDetermination;
use Modules\Playback\Infrastructure\Eloquent\Decision;
use Modules\Playback\Infrastructure\Eloquent\PlaybackSession;
use Modules\Rights\Contracts\AvailabilityQuery;
use Modules\Rights\Contracts\AvailabilityRequest;
use Modules\Rights\Contracts\AvailabilityVerdict;
use Modules\Schedule\Contracts\ChannelDirectory;
use Modules\Shared\Http\ErrorCode;
use Throwable;

/**
 * Re-checking a session that is already playing.
 *
 * A decision made when playback started is not a decision that stays true. A
 * blackout begins mid-event; a subscription is cancelled; a licence window
 * closes at midnight. Enforcing those only at session start means enforcing
 * them only on *new* viewers, which is precisely the case a licensor is not
 * asking about.
 *
 * ## Why this is interval-based rather than per-heartbeat
 *
 * The full decision is the most expensive check in the platform. Running it on
 * every heartbeat would multiply that cost by the heartbeat rate, which at
 * scale is the largest write and read stream the control plane carries. So a
 * session is re-checked at most once per configured interval, and **that
 * interval is the maximum enforcement lag** for a mid-session change. It is a
 * number to tune against measured cost, not a detail — which is why it is
 * configuration with that consequence written next to it.
 *
 * Parental settings and concurrency are deliberately *not* re-checked here.
 * Concurrency is enforced by the slot the heartbeat renews, and re-running the
 * parental check would end a session because a parent adjusted a setting for
 * a different profile, which is not what anyone means by it.
 */
final readonly class SessionRevalidator
{
    public function __construct(
        private ChannelDirectory $channels,
        private AvailabilityQuery $rights,
        private EntitlementQuery $entitlements,
    ) {}

    public function isDue(PlaybackSession $session, DateTimeImmutable $now): bool
    {
        $last = $session->revalidated_at?->toDateTimeImmutable() ?? $session->started_at->toDateTimeImmutable();
        $interval = (int) config('kms.playback.revalidation_interval_seconds');

        return $now->getTimestamp() - $last->getTimestamp() >= $interval;
    }

    /**
     * Re-runs the volatile checks.
     *
     * Returns the reason the session must end, or null if it may continue.
     * Fails closed on an unavailable dependency exactly as the initial decision
     * does: a session that cannot be re-checked is a session whose licence
     * status is unknown.
     */
    public function revalidate(
        PlaybackSession $session,
        ?string $territory,
        ?string $territoryMethod,
        DateTimeImmutable $now,
    ): ?ErrorCode {
        $channel = $this->channels->find((string) $session->content_ref);

        if ($channel === null || ! $channel->isPlayable) {
            return $this->record($session, ErrorCode::PlaybackContentUnavailable, $now, null, $territory, $territoryMethod);
        }

        if ($territory === null) {
            return $this->record($session, ErrorCode::PlaybackTerritoryUndetermined, $now, null, $territory, $territoryMethod);
        }

        try {
            $verdict = $this->rights->resolve(new AvailabilityRequest(
                subjectType: 'channel',
                subjectRef: (string) $session->content_ref,
                exploitation: (string) $session->mode,
                territory: $territory,
                platform: (string) $session->device_class,
                monetization: 'svod',
                at: $now,
            ));
        } catch (Throwable) {
            return $this->record($session, ErrorCode::PlaybackTemporarilyUnavailable, $now, null, $territory, $territoryMethod);
        }

        if (! $verdict->permitted) {
            return $this->record($session, match ($verdict->reason) {
                AvailabilityVerdict::DENIED_BLACKOUT => ErrorCode::PlaybackBlackedOut,
                AvailabilityVerdict::DENIED_TERRITORY => ErrorCode::PlaybackNotAvailableInTerritory,
                AvailabilityVerdict::DENIED_PLATFORM => ErrorCode::PlaybackPlatformNotPermitted,
                AvailabilityVerdict::DENIED_MONETIZATION,
                AvailabilityVerdict::DENIED_EXPLOITATION => ErrorCode::PlaybackModeNotPermitted,
                default => ErrorCode::PlaybackOutsideLicenceWindow,
            }, $now, $verdict, $territory, $territoryMethod);
        }

        $snapshot = $this->entitlements->snapshotFor((string) $session->account_uuid);

        if ($snapshot === null || ! $snapshot->includesChannel((string) $session->content_ref)) {
            return $this->record($session, ErrorCode::PlaybackNotEntitled, $now, $verdict, $territory, $territoryMethod);
        }

        return null;
    }

    /**
     * A mid-session denial is recorded exactly like an initial one.
     *
     * Two decisions for one session is the point: the record shows a viewer was
     * permitted at one instant and refused at another, which is the shape of
     * the answer a licensor's blackout query actually needs.
     */
    private function record(
        PlaybackSession $session,
        ErrorCode $code,
        DateTimeImmutable $now,
        ?AvailabilityVerdict $verdict,
        ?string $territory,
        ?string $territoryMethod,
    ): ErrorCode {
        try {
            Decision::query()->create([
                'uuid' => (string) Str::uuid7(),
                'outcome' => 'deny',
                'reason_code' => $code->value,
                'account_uuid' => $session->account_uuid,
                'profile_uuid' => $session->profile_uuid,
                'device_uuid' => $session->device_uuid,
                'device_class' => $session->device_class,
                'session_uuid' => $session->uuid,
                'content_type' => $session->content_type,
                'content_ref' => $session->content_ref,
                'mode' => $session->mode,
                'availability_version' => $verdict?->availabilityVersion,
                'rights_rule_ids' => $verdict?->sourceRuleIds ?? [],
                'entitlement_grant_ids' => [],
                'territory' => $territory,
                'territory_method' => $territoryMethod ?? TerritoryDetermination::UNDETERMINED,
                // Marks the decision as a mid-session re-check rather than a
                // start, so the two are distinguishable in the record.
                'applied_restrictions' => ['phase' => 'revalidation'],
                'decided_at' => $now,
            ]);
        } catch (Throwable) {
            // A denial that cannot be recorded is still a denial. Refusing to
            // end the session would be strictly worse than an incomplete
            // audit trail.
        }

        return $code;
    }
}
