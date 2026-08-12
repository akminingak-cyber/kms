<?php

declare(strict_types=1);

namespace Modules\Rights\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Rights\Application\AvailabilityResolver;
use Modules\Rights\Application\RightsProjector;
use Modules\Rights\Contracts\AvailabilityRequest;
use Modules\Rights\Domain\Exploitation;
use Modules\Rights\Domain\PlatformMask;
use Modules\Rights\Domain\TerritoryRules;
use Modules\Rights\Infrastructure\Eloquent\Agreement;
use Modules\Rights\Infrastructure\Eloquent\Blackout;
use Modules\Rights\Infrastructure\Eloquent\RightGrant;
use Modules\Rights\Infrastructure\Eloquent\TerritoryRuleSet;
use Modules\Rights\Infrastructure\Eloquent\UsageRuleSet;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\CursorList;
use Modules\Shared\Http\ErrorCode;

final class AdminRightsController
{
    public function __construct(
        private readonly Clock $clock,
        private readonly RightsProjector $projector,
        private readonly AvailabilityResolver $resolver,
        private readonly AuditRecorder $audit,
    ) {}

    public function agreements(Request $request): JsonResponse
    {
        $query = Agreement::query()->orderByDesc('id');

        return new JsonResponse(CursorList::respond($request, $query, static fn (Agreement $a): array => [
            'id' => $a->uuid,
            'counterparty' => $a->counterparty,
            'reference' => $a->reference,
            'term_start' => $a->term_start?->format(DATE_RFC3339),
            'term_end' => $a->term_end?->format(DATE_RFC3339),
            'reporting_obligation' => $a->reporting_obligation,
        ]));
    }

    /**
     * The rights themselves, with their rule sets resolved.
     *
     * Superseded rights are included by default and marked as such. A rights
     * manager investigating "why was this denied on the 3rd?" needs the rule
     * that applied then, not only the one that applies now — and rights are
     * superseded rather than edited precisely so that question stays
     * answerable.
     */
    public function rights(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => ['sometimes', 'uuid'],
            'agreement_id' => ['sometimes', 'uuid'],
            'exploitation' => ['sometimes', 'string', 'max:20'],
            'active_only' => ['sometimes', 'boolean'],
        ]);

        $query = RightGrant::query()->orderByDesc('id');

        if ($request->filled('subject_id')) {
            $query->where('subject_ref', $request->string('subject_id')->toString());
        }

        if ($request->filled('exploitation')) {
            $query->where('exploitation', $request->string('exploitation')->toString());
        }

        if ($request->filled('agreement_id')) {
            $agreement = Agreement::query()->where('uuid', $request->string('agreement_id')->toString())->first();
            // An unknown agreement filters to nothing rather than to
            // everything: a filter that silently stops filtering is how an
            // operator concludes a right exists when it does not.
            $query->where('agreement_id', $agreement?->id ?? -1);
        }

        if ($request->boolean('active_only')) {
            $query->whereNull('superseded_at');
        }

        return new JsonResponse(CursorList::respond($request, $query, $this->presentRight(...)));
    }

    public function blackouts(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => ['sometimes', 'uuid'],
            'active_only' => ['sometimes', 'boolean'],
        ]);

        $query = Blackout::query()->orderByDesc('starts_at')->orderByDesc('id');

        if ($request->filled('subject_id')) {
            $query->where('subject_ref', $request->string('subject_id')->toString());
        }

        if ($request->boolean('active_only')) {
            $now = $this->clock->now();
            $query->whereNull('lifted_at')->where('starts_at', '<=', $now)->where('ends_at', '>', $now);
        }

        return new JsonResponse(CursorList::respond($request, $query, static fn (Blackout $b): array => [
            'id' => $b->uuid,
            'subject_type' => $b->subject_type,
            'subject_id' => $b->subject_ref,
            'starts_at' => $b->starts_at?->format(DATE_RFC3339),
            'ends_at' => $b->ends_at?->format(DATE_RFC3339),
            'reason' => $b->reason,
            'lifted_at' => $b->lifted_at?->format(DATE_RFC3339),
        ]));
    }

    /** @return array<string,mixed> */
    private function presentRight(RightGrant $right): array
    {
        $agreement = Agreement::query()->find($right->agreement_id);
        $territory = $right->territory_rule_set_id === null
            ? null
            : TerritoryRuleSet::query()->find($right->territory_rule_set_id);
        $usage = $right->usage_rule_set_id === null
            ? null
            : UsageRuleSet::query()->find($right->usage_rule_set_id);

        return [
            'id' => $right->uuid,
            'agreement' => $agreement === null ? null : [
                'id' => $agreement->uuid,
                'counterparty' => $agreement->counterparty,
                'reference' => $agreement->reference,
            ],
            'subject_type' => $right->subject_type,
            'subject_id' => $right->subject_ref,
            'exploitation' => $right->exploitation,
            'window_start' => $right->window_start?->format(DATE_RFC3339),
            // Null is an open-ended window, not a missing value.
            'window_end' => $right->window_end?->format(DATE_RFC3339),
            // Decoded from the stored bitmask, because "platform_mask: 41" is
            // not something anyone should have to decode by hand while a
            // licensor is on the phone.
            'platforms' => PlatformMask::platformsFrom((int) $right->platform_mask),
            'monetization' => PlatformMask::monetizationFrom((int) $right->monetization_mask),
            'territory_rules' => $territory?->rules,
            'usage_rules' => $usage === null ? null : array_filter([
                'max_resolution' => $usage->max_resolution,
                'hdcp' => $usage->hdcp,
                'security_level' => $usage->security_level,
                'concurrency_cap' => $usage->concurrency_cap === null ? null : (int) $usage->concurrency_cap,
            ], static fn ($v): bool => $v !== null),
            'superseded_at' => $right->superseded_at?->format(DATE_RFC3339),
        ];
    }

    public function storeAgreement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'counterparty' => ['required', 'string', 'max:200'],
            'reference' => ['required', 'string', 'max:120'],
            'reporting_obligation' => ['sometimes', 'nullable', 'string', 'max:200'],
        ]);

        $agreement = Agreement::query()->create([
            'uuid' => (string) Str::uuid7(),
            'counterparty' => $validated['counterparty'],
            'reference' => $validated['reference'],
            'reporting_obligation' => $validated['reporting_obligation'] ?? null,
        ]);

        return new JsonResponse(['data' => ['id' => $agreement->uuid]], 201);
    }

    public function storeRight(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agreement_id' => ['required', 'uuid'],
            'subject_type' => ['required', 'in:channel,programme,title,collection'],
            'subject_id' => ['required', 'uuid'],
            'exploitation' => ['required', Rule::in(Exploitation::values())],
            'window_start' => ['required', 'date'],
            'window_end' => ['sometimes', 'nullable', 'date'],
            'platforms' => ['sometimes', 'array'],
            'platforms.*' => [Rule::in(PlatformMask::platformNames())],
            'monetization' => ['sometimes', 'array'],
            'monetization.*' => [Rule::in(PlatformMask::monetizationNames())],
            'territory_rules' => ['sometimes', 'array'],
            'territory_rules.*.effect' => ['required_with:territory_rules', 'in:include,exclude'],
            'territory_rules.*.territories' => ['required_with:territory_rules', 'array'],
            'usage' => ['sometimes', 'array'],
            'usage.max_resolution' => ['sometimes', 'nullable', 'string', 'max:20'],
            'usage.hdcp' => ['sometimes', 'nullable', 'string', 'max:20'],
            'usage.concurrency_cap' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $agreement = Agreement::query()->where('uuid', $validated['agreement_id'])->first();

        if ($agreement === null) {
            throw ApiProblem::of(ErrorCode::NotFound);
        }

        $right = $this->projector->grant($agreement, [
            'subject_type' => $validated['subject_type'],
            'subject_ref' => $validated['subject_id'],
            'exploitation' => $validated['exploitation'],
            'platforms' => $validated['platforms'] ?? PlatformMask::platformNames(),
            'monetization' => $validated['monetization'] ?? PlatformMask::monetizationNames(),
            'window_start' => new DateTimeImmutable($validated['window_start']),
            'window_end' => isset($validated['window_end']) ? new DateTimeImmutable($validated['window_end']) : null,
            'territory_rules' => $validated['territory_rules'] ?? null,
            'usage' => $validated['usage'] ?? [],
        ], (string) $request->attributes->get('kms.staff_uuid'));

        return new JsonResponse(['data' => ['id' => $right->uuid]], 201);
    }

    public function storeBlackout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', 'in:channel,programme'],
            'subject_id' => ['required', 'uuid'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:200'],
            'territories' => ['sometimes', 'array'],
            'territories.*' => ['string', 'size:2'],
        ]);

        $ruleSetId = null;

        if (! empty($validated['territories'])) {
            $rules = TerritoryRules::fromArray([
                ['effect' => 'include', 'territories' => $validated['territories']],
            ]);

            $ruleSetId = TerritoryRuleSet::query()->where('hash', $rules->hash())->value('id');

            if ($ruleSetId === null) {
                $ruleSetId = TerritoryRuleSet::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'hash' => $rules->hash(),
                    'rules' => $rules->toArray(),
                    'created_at' => $this->clock->now(),
                ])->id;
            }
        }

        $blackout = Blackout::query()->create([
            'uuid' => (string) Str::uuid7(),
            'subject_type' => $validated['subject_type'],
            'subject_ref' => $validated['subject_id'],
            'territory_rule_set_id' => $ruleSetId,
            'starts_at' => new DateTimeImmutable($validated['starts_at']),
            'ends_at' => new DateTimeImmutable($validated['ends_at']),
            'reason' => $validated['reason'],
        ]);

        // Blackouts are never projected — they take effect immediately, which
        // is the whole reason they are read live at decision time.
        $this->audit->record(AuditEvent::byStaff(
            (string) $request->attributes->get('kms.staff_uuid'),
            'rights.blackout.created',
            $validated['subject_type'],
            $validated['subject_id'],
            ['reason' => $validated['reason']],
        ));

        return new JsonResponse(['data' => ['id' => $blackout->uuid]], 201);
    }

    /**
     * Preview availability before committing.
     *
     * A correctness feature rather than a convenience: rights mistakes are
     * contract breaches, so a rights manager must be able to see the computed
     * effect of a configuration without discovering it from a viewer.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'uuid'],
            'exploitation' => ['required', Rule::in(Exploitation::values())],
            'territory' => ['required', 'string', 'size:2'],
            'platform' => ['required', Rule::in(PlatformMask::platformNames())],
            'at' => ['sometimes', 'date'],
        ]);

        $verdict = $this->resolver->resolve(new AvailabilityRequest(
            subjectType: 'channel',
            subjectRef: $validated['subject_id'],
            exploitation: $validated['exploitation'],
            territory: strtoupper($validated['territory']),
            platform: $validated['platform'],
            monetization: 'svod',
            at: isset($validated['at']) ? new DateTimeImmutable($validated['at']) : $this->clock->now(),
        ));

        return new JsonResponse([
            'data' => [
                'permitted' => $verdict->permitted,
                'reason' => $verdict->reason,
                'availability_version' => $verdict->availabilityVersion,
                'source_rule_ids' => $verdict->sourceRuleIds,
                'usage_rules' => $verdict->usageRules?->toArray(),
            ],
        ]);
    }
}
