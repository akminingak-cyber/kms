<?php

declare(strict_types=1);

namespace Modules\Rights\Application;

use Modules\Rights\Contracts\AvailabilityQuery;
use Modules\Rights\Contracts\AvailabilityRequest;
use Modules\Rights\Contracts\AvailabilityVerdict;
use Modules\Rights\Contracts\UsageRules;
use Modules\Rights\Domain\PlatformMask;
use Modules\Rights\Domain\TerritoryRules;
use Modules\Rights\Infrastructure\Eloquent\Availability;
use Modules\Rights\Infrastructure\Eloquent\Blackout;
use Modules\Rights\Infrastructure\Eloquent\TerritoryRuleSet;
use Modules\Rights\Infrastructure\Eloquent\UsageRuleSet;
use Modules\Shared\Domain\Clock;

/**
 * Reads the factorised projection and decides (ADR-0011).
 *
 * One indexed read for the interval, two mask tests, and a cached rule-set
 * resolution. The interned rule sets are few hundreds of rows in total, so they
 * stay resident in cache and the hot path rarely touches disk for them.
 */
final class AvailabilityResolver implements AvailabilityQuery
{
    /** @var array<int, TerritoryRules> */
    private array $territoryCache = [];

    /** @var array<int, UsageRules> */
    private array $usageCache = [];

    public function __construct(private readonly Clock $clock) {}

    public function resolve(AvailabilityRequest $request): AvailabilityVerdict
    {
        /*
         * Blackouts first, and read live rather than from the projection.
         *
         * They are few, urgent, and frequently applied minutes before they take
         * effect — pushing one through a projection pipeline is exactly the
         * wrong tool. A blackout overrides an otherwise valid right.
         */
        $blackout = $this->activeBlackout($request);

        if ($blackout !== null) {
            return AvailabilityVerdict::deny(AvailabilityVerdict::DENIED_BLACKOUT, null, [$blackout]);
        }

        /** @var list<Availability> $intervals */
        $intervals = Availability::query()
            ->where('subject_ref', $request->subjectRef)
            ->where('exploitation', $request->exploitation)
            ->where('interval_start', '<=', $request->at)
            ->where(function ($q) use ($request): void {
                $q->whereNull('interval_end')->orWhere('interval_end', '>', $request->at);
            })
            ->get()
            ->all();

        if ($intervals === []) {
            /*
             * No row means prohibited. Default deny is a database-level
             * invariant here, not application logic: content ingested before
             * its rights are entered is invisible and unplayable until somebody
             * decides otherwise.
             *
             * The reason distinguishes "we hold no right at all" from "the
             * window has not opened", because the two produce different client
             * messages and different operational follow-up.
             */
            return AvailabilityVerdict::deny(
                $this->hasAnyRight($request) ? AvailabilityVerdict::DENIED_WINDOW : AvailabilityVerdict::DENIED_NO_RIGHT,
            );
        }

        $matched = [];
        $usage = null;
        $version = 0;
        $sawTerritoryMatch = false;
        $sawPlatformMatch = false;

        foreach ($intervals as $interval) {
            $territory = $this->territoryRules((int) $interval->territory_rule_set_id);

            if (! $territory->permits($request->territory)) {
                continue;
            }

            $sawTerritoryMatch = true;

            if (! PlatformMask::permitsPlatform((int) $interval->platform_mask, $request->platform)) {
                continue;
            }

            $sawPlatformMatch = true;

            if (! PlatformMask::permitsMonetization((int) $interval->monetization_mask, $request->monetization)) {
                continue;
            }

            $matched[] = $interval;
            $version = max($version, (int) $interval->availability_version);

            $rules = $this->usageRules((int) $interval->usage_rule_set_id);
            // Restrictive merge: the strictest applicable right wins.
            $usage = $usage === null ? $rules : $usage->mergeRestrictive($rules);
        }

        if ($matched === []) {
            // Report the first condition that failed, in precedence order, so
            // the client sees the most specific true statement.
            return AvailabilityVerdict::deny(match (true) {
                ! $sawTerritoryMatch => AvailabilityVerdict::DENIED_TERRITORY,
                ! $sawPlatformMatch => AvailabilityVerdict::DENIED_PLATFORM,
                default => AvailabilityVerdict::DENIED_MONETIZATION,
            });
        }

        $ruleIds = [];

        foreach ($matched as $interval) {
            foreach ((array) $interval->source_rule_ids as $ruleId) {
                $ruleIds[] = (string) $ruleId;
            }
        }

        return AvailabilityVerdict::allow($version, array_values(array_unique($ruleIds)), $usage ?? new UsageRules);
    }

    /** @param list<string> $subjectRefs @return list<string> */
    public function filterVisible(array $subjectRefs, string $exploitation, string $territory, string $platform): array
    {
        if ($subjectRefs === []) {
            return [];
        }

        $now = $this->clock->now();

        $rows = Availability::query()
            ->whereIn('subject_ref', $subjectRefs)
            ->where('exploitation', $exploitation)
            ->where('interval_start', '<=', $now)
            ->where(function ($q) use ($now): void {
                $q->whereNull('interval_end')->orWhere('interval_end', '>', $now);
            })
            ->get();

        $visible = [];

        foreach ($rows as $row) {
            if (! $this->territoryRules((int) $row->territory_rule_set_id)->permits($territory)) {
                continue;
            }

            if (! PlatformMask::permitsPlatform((int) $row->platform_mask, $platform)) {
                continue;
            }

            $visible[$row->subject_ref] = true;
        }

        // Blackouts hide nothing from a listing by default: content that is
        // blacked out right now is still in the schedule, and hiding it would
        // make the EPG wrong. Playback is where the blackout bites.
        return array_values(array_intersect($subjectRefs, array_keys($visible)));
    }

    private function activeBlackout(AvailabilityRequest $request): ?string
    {
        $blackouts = Blackout::query()
            ->where('subject_ref', $request->subjectRef)
            ->whereNull('lifted_at')
            ->where('starts_at', '<=', $request->at)
            ->where('ends_at', '>', $request->at)
            ->get();

        foreach ($blackouts as $blackout) {
            $ruleSetId = $blackout->territory_rule_set_id;

            // A blackout with no territory ruleset is global.
            if ($ruleSetId === null) {
                return (string) $blackout->uuid;
            }

            if ($this->territoryRules((int) $ruleSetId)->permits($request->territory)) {
                return (string) $blackout->uuid;
            }
        }

        return null;
    }

    /** Distinguishes "window closed" from "no right at all". */
    private function hasAnyRight(AvailabilityRequest $request): bool
    {
        return Availability::query()
            ->where('subject_ref', $request->subjectRef)
            ->where('exploitation', $request->exploitation)
            ->exists();
    }

    private function territoryRules(int $id): TerritoryRules
    {
        return $this->territoryCache[$id] ??= TerritoryRules::fromArray(
            (array) (TerritoryRuleSet::query()->find($id)?->rules ?? []),
        );
    }

    private function usageRules(int $id): UsageRules
    {
        if (isset($this->usageCache[$id])) {
            return $this->usageCache[$id];
        }

        $row = UsageRuleSet::query()->find($id);

        return $this->usageCache[$id] = new UsageRules(
            maxResolution: $row?->max_resolution,
            hdcp: $row?->hdcp,
            securityLevel: $row?->security_level,
            concurrencyCap: $row?->concurrency_cap !== null ? (int) $row->concurrency_cap : null,
        );
    }
}
