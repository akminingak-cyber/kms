<?php

declare(strict_types=1);

namespace Modules\Rights\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Rights\Contracts\UsageRules;
use Modules\Rights\Domain\PlatformMask;
use Modules\Rights\Domain\TerritoryRules;
use Modules\Rights\Infrastructure\Eloquent\Agreement;
use Modules\Rights\Infrastructure\Eloquent\Availability;
use Modules\Rights\Infrastructure\Eloquent\AvailabilityVersion;
use Modules\Rights\Infrastructure\Eloquent\RightGrant;
use Modules\Rights\Infrastructure\Eloquent\TerritoryRuleSet;
use Modules\Rights\Infrastructure\Eloquent\UsageRuleSet;
use Modules\Shared\Domain\Clock;

/**
 * Records rights and maintains the availability projection.
 *
 * Interning is the load-bearing part. Identical rule sets are stored once and
 * shared, so an agreement-wide correction — "remove Germany from everything
 * this licensor gave us" — rewrites one row rather than every projection row
 * that references it. Recompute becomes proportional to what changed rather
 * than to catalog size, which is what makes a rights correction land in seconds
 * instead of hours (ADR-0011).
 */
final readonly class RightsProjector
{
    public function __construct(
        private Clock $clock,
        private AuditRecorder $audit,
    ) {}

    /**
     * Grant a right and project it.
     *
     * @param  array{
     *     subject_type:string, subject_ref:string, exploitation:string,
     *     platforms?:list<string>, monetization?:list<string>,
     *     window_start:\DateTimeImmutable, window_end?:?\DateTimeImmutable,
     *     territory_rules?:list<array{effect:string,territories:list<string>}>,
     *     usage?:array{max_resolution?:?string,hdcp?:?string,security_level?:?string,concurrency_cap?:?int}
     * }  $input
     */
    public function grant(Agreement $agreement, array $input, ?string $actorUuid = null): RightGrant
    {
        return DB::transaction(function () use ($agreement, $input, $actorUuid): RightGrant {
            $territory = TerritoryRules::fromArray(
                $input['territory_rules'] ?? TerritoryRules::worldwide()->toArray()
            );

            $usage = new UsageRules(
                maxResolution: $input['usage']['max_resolution'] ?? null,
                hdcp: $input['usage']['hdcp'] ?? null,
                securityLevel: $input['usage']['security_level'] ?? null,
                concurrencyCap: $input['usage']['concurrency_cap'] ?? null,
            );

            $right = RightGrant::query()->create([
                'uuid' => (string) Str::uuid7(),
                'agreement_id' => $agreement->id,
                'subject_type' => $input['subject_type'],
                'subject_ref' => $input['subject_ref'],
                'exploitation' => $input['exploitation'],
                'monetization_mask' => PlatformMask::forMonetization(
                    $input['monetization'] ?? PlatformMask::monetizationNames()
                ),
                'platform_mask' => PlatformMask::forPlatforms(
                    $input['platforms'] ?? PlatformMask::platformNames()
                ),
                'window_start' => $input['window_start'],
                'window_end' => $input['window_end'] ?? null,
                'territory_rule_set_id' => $this->internTerritory($territory),
                'usage_rule_set_id' => $this->internUsage($usage),
            ]);

            $this->project($input['subject_ref'], $input['exploitation'], 'right_granted');

            $this->audit->record(AuditEvent::byStaff(
                $actorUuid ?? 'system',
                'rights.right_granted',
                $input['subject_type'],
                $input['subject_ref'],
                ['exploitation' => $input['exploitation'], 'right_uuid' => $right->uuid],
            ));

            return $right;
        });
    }

    /**
     * Supersede a right and reproject.
     *
     * Rights are append-only and effective-dated: a correction supersedes
     * rather than overwrites, because licensor disputes are about what was true
     * on a date, not what is true now.
     */
    public function supersede(string $rightUuid, ?string $actorUuid = null): void
    {
        DB::transaction(function () use ($rightUuid, $actorUuid): void {
            $right = RightGrant::query()->where('uuid', $rightUuid)->whereNull('superseded_at')->first();

            if ($right === null) {
                return;
            }

            $right->forceFill(['superseded_at' => $this->clock->now()])->save();
            $this->project($right->subject_ref, $right->exploitation, 'right_superseded');

            $this->audit->record(AuditEvent::byStaff(
                $actorUuid ?? 'system',
                'rights.right_superseded',
                $right->subject_type,
                $right->subject_ref,
                ['right_uuid' => $rightUuid],
            ));
        });
    }

    /**
     * Recompute the projection for one subject and exploitation.
     *
     * Incremental by design. A full rebuild exists as a recovery tool and is
     * expected to be slow; routine changes must never trigger one, or
     * projection lag becomes worst exactly when a correction most needs to land.
     */
    public function project(string $subjectRef, string $exploitation, string $reason): int
    {
        $now = $this->clock->now();

        $version = AvailabilityVersion::query()->create([
            'reason' => $reason,
            'subjects_recomputed' => 1,
            'computed_at' => $now,
        ]);

        Availability::query()
            ->where('subject_ref', $subjectRef)
            ->where('exploitation', $exploitation)
            ->delete();

        $rights = RightGrant::query()
            ->where('subject_ref', $subjectRef)
            ->where('exploitation', $exploitation)
            ->whereNull('superseded_at')
            ->get();

        foreach ($rights as $right) {
            Availability::query()->create([
                'subject_type' => $right->subject_type,
                'subject_ref' => $right->subject_ref,
                'exploitation' => $right->exploitation,
                'interval_start' => $right->window_start,
                'interval_end' => $right->window_end,
                'territory_rule_set_id' => $right->territory_rule_set_id,
                'usage_rule_set_id' => $right->usage_rule_set_id,
                'platform_mask' => $right->platform_mask,
                'monetization_mask' => $right->monetization_mask,
                'availability_version' => $version->id,
                'source_rule_ids' => [$right->uuid],
                'computed_at' => $now,
            ]);
        }

        return (int) $version->id;
    }

    /** Content-addressed, so identical rulesets are stored once and shared. */
    private function internTerritory(TerritoryRules $rules): int
    {
        $hash = $rules->hash();
        $existing = TerritoryRuleSet::query()->where('hash', $hash)->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) TerritoryRuleSet::query()->create([
            'uuid' => (string) Str::uuid7(),
            'hash' => $hash,
            'rules' => $rules->toArray(),
            'created_at' => $this->clock->now(),
        ])->id;
    }

    private function internUsage(UsageRules $usage): int
    {
        $payload = $usage->toArray();
        ksort($payload);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $existing = UsageRuleSet::query()->where('hash', $hash)->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) UsageRuleSet::query()->create([
            'uuid' => (string) Str::uuid7(),
            'hash' => $hash,
            'max_resolution' => $usage->maxResolution,
            'hdcp' => $usage->hdcp,
            'security_level' => $usage->securityLevel,
            'concurrency_cap' => $usage->concurrencyCap,
            'created_at' => $this->clock->now(),
        ])->id;
    }
}
