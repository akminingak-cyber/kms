<?php

declare(strict_types=1);

namespace Modules\Billing\Application;

use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Billing\Domain\SubscriptionStatus;
use Modules\Billing\Infrastructure\Eloquent\Subscription;
use Modules\Billing\Infrastructure\Eloquent\SubscriptionEvent;
use Modules\Billing\Infrastructure\Eloquent\SubscriptionPeriod;
use Modules\Entitlement\Contracts\EntitlementWriter;
use Modules\Product\Contracts\PackageCatalog;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Infrastructure\AccountLock;

/**
 * The subscription lifecycle.
 *
 * **No money moves in this phase.** No payment provider has been selected
 * (OQ-9) and none is integrated, so a subscription is created directly and the
 * provider columns stay null until an adapter exists. What *is* real is the
 * state machine and the entitlement it produces — which is the part a PSP would
 * never have owned anyway (ADR-0009).
 *
 * Note the direction of the dependency: Billing calls Entitlement. Entitlement
 * never reads billing, so the playback hot path cannot inherit a billing
 * dependency through the back door.
 */
final readonly class SubscriptionService
{
    public function __construct(
        private Clock $clock,
        private PackageCatalog $packages,
        private EntitlementWriter $entitlements,
        private AuditRecorder $audit,
    ) {}

    /** @throws ApiProblem */
    public function subscribe(string $accountUuid, string $planUuid): Subscription
    {
        if (! $this->packages->planIsActive($planUuid)) {
            // A retired plan may still be referenced by existing subscribers;
            // it just cannot be newly sold.
            throw ApiProblem::of(
                $this->packages->planLimits($planUuid) === null
                    ? ErrorCode::PlanNotFound
                    : ErrorCode::PlanRetired,
            );
        }

        $packageUuid = $this->packages->packageForPlan($planUuid);
        $limits = $this->packages->planLimits($planUuid);

        if ($packageUuid === null || $limits === null) {
            throw ApiProblem::of(ErrorCode::PlanNotFound);
        }

        return DB::transaction(function () use ($accountUuid, $planUuid, $packageUuid, $limits): Subscription {
            AccountLock::acquire($accountUuid, 'subscription');

            $now = $this->clock->now();

            if ($this->liveSubscription($accountUuid) !== null) {
                throw ApiProblem::of(ErrorCode::SubscriptionAlreadyActive);
            }

            $trialDays = $limits['trial_days'];
            $status = $trialDays > 0 ? SubscriptionStatus::Trialing : SubscriptionStatus::Active;
            $periodEnd = $trialDays > 0
                ? $now->modify("+{$trialDays} days")
                : $now->modify('+1 month');

            try {
                $subscription = Subscription::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'account_uuid' => $accountUuid,
                    'plan_uuid' => $planUuid,
                    'status' => $status->value,
                    'started_at' => $now,
                    'current_period_start' => $now,
                    'current_period_end' => $periodEnd,
                    'trial_ends_at' => $trialDays > 0 ? $periodEnd : null,
                ]);
            } catch (QueryException $e) {
                // The partial unique index is the authority on "one live
                // subscription per account", not the check above, which could
                // still race.
                if (($e->errorInfo[0] ?? null) === '23505') {
                    throw ApiProblem::of(ErrorCode::SubscriptionAlreadyActive);
                }

                throw $e;
            }

            $this->recordPeriod($subscription, $now, $periodEnd, 'initial');
            $this->recordEvent($subscription, null, $status, 'subscribed');

            // Entitlement is granted for exactly the period that has been
            // subscribed. A grant with no end date would outlive the reason it
            // exists.
            $this->entitlements->grantFromSubscription(
                $accountUuid,
                $subscription->uuid,
                $packageUuid,
                $now,
                $periodEnd,
                $limits['concurrency_limit'],
            );

            $this->audit->record(AuditEvent::byAccount(
                $accountUuid,
                'billing.subscription.started',
                'subscription',
                $subscription->uuid,
                ['plan_uuid' => $planUuid, 'status' => $status->value],
            ));

            return $subscription;
        });
    }

    /**
     * Cancel at the end of the paid period.
     *
     * Cancellation is a decision, not an immediate end: the subscriber keeps
     * access until the period they already paid for runs out. Revoking
     * immediately would be a refund question nobody asked.
     *
     * @throws ApiProblem
     */
    public function cancel(string $accountUuid, string $subscriptionUuid): Subscription
    {
        return DB::transaction(function () use ($accountUuid, $subscriptionUuid): Subscription {
            $subscription = $this->ownedSubscription($accountUuid, $subscriptionUuid);

            if (! SubscriptionStatus::from($subscription->status)->isLive()) {
                throw ApiProblem::of(ErrorCode::SubscriptionNotChangeable);
            }

            $subscription->forceFill([
                'cancel_at_period_end' => true,
                'cancelled_at' => $this->clock->now(),
            ])->save();

            $this->recordEvent(
                $subscription,
                SubscriptionStatus::from($subscription->status),
                SubscriptionStatus::from($subscription->status),
                'cancellation_scheduled',
            );

            $this->audit->record(AuditEvent::byAccount(
                $accountUuid,
                'billing.subscription.cancellation_scheduled',
                'subscription',
                $subscription->uuid,
                ['effective_at' => $subscription->current_period_end?->toIso8601String()],
            ));

            return $subscription;
        });
    }

    /** @throws ApiProblem */
    public function resume(string $accountUuid, string $subscriptionUuid): Subscription
    {
        return DB::transaction(function () use ($accountUuid, $subscriptionUuid): Subscription {
            $subscription = $this->ownedSubscription($accountUuid, $subscriptionUuid);

            if (! $subscription->cancel_at_period_end || ! SubscriptionStatus::from($subscription->status)->isLive()) {
                throw ApiProblem::of(ErrorCode::SubscriptionNotChangeable);
            }

            $subscription->forceFill(['cancel_at_period_end' => false, 'cancelled_at' => null])->save();

            $this->recordEvent(
                $subscription,
                SubscriptionStatus::from($subscription->status),
                SubscriptionStatus::from($subscription->status),
                'cancellation_revoked',
            );

            return $subscription;
        });
    }

    /**
     * Advance subscriptions whose period has ended.
     *
     * Renewal without a payment provider means extending the period; when a PSP
     * exists this is where a charge is attempted and a failure moves the
     * subscription to `past_due` rather than ending it.
     *
     * @return array{renewed:int, expired:int}
     */
    public function processDueRenewals(): array
    {
        $now = $this->clock->now();
        $renewed = 0;
        $expired = 0;

        $due = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Trialing->value, SubscriptionStatus::Active->value])
            ->where('current_period_end', '<=', $now)
            ->get();

        foreach ($due as $subscription) {
            DB::transaction(function () use ($subscription, $now, &$renewed, &$expired): void {
                $from = SubscriptionStatus::from($subscription->status);

                if ($subscription->cancel_at_period_end) {
                    $subscription->forceFill([
                        'status' => SubscriptionStatus::Expired->value,
                        'ended_at' => $now,
                    ])->save();

                    $this->recordEvent($subscription, $from, SubscriptionStatus::Expired, 'period_ended_after_cancellation');
                    $this->entitlements->revokeForSubscription($subscription->uuid, 'subscription_expired');
                    $expired++;

                    return;
                }

                $periodEnd = $now->modify('+1 month');

                $subscription->forceFill([
                    'status' => SubscriptionStatus::Active->value,
                    'current_period_start' => $now,
                    'current_period_end' => $periodEnd,
                    'trial_ends_at' => null,
                ])->save();

                $this->recordPeriod($subscription, $now, $periodEnd, 'renewal');
                $this->recordEvent($subscription, $from, SubscriptionStatus::Active, 'renewed');

                $packageUuid = $this->packages->packageForPlan($subscription->plan_uuid);
                $limits = $this->packages->planLimits($subscription->plan_uuid);

                if ($packageUuid !== null && $limits !== null) {
                    // Supersede rather than extend: the old grant's window is
                    // the record of what was true for that period.
                    $this->entitlements->revokeForSubscription($subscription->uuid, 'renewed');
                    $this->entitlements->grantFromSubscription(
                        $subscription->account_uuid,
                        $subscription->uuid,
                        $packageUuid,
                        $now,
                        $periodEnd,
                        $limits['concurrency_limit'],
                    );
                }

                $renewed++;
            });
        }

        return ['renewed' => $renewed, 'expired' => $expired];
    }

    public function liveSubscription(string $accountUuid): ?Subscription
    {
        return Subscription::query()
            ->where('account_uuid', $accountUuid)
            ->whereIn('status', SubscriptionStatus::liveValues())
            ->first();
    }

    /** @throws ApiProblem */
    private function ownedSubscription(string $accountUuid, string $subscriptionUuid): Subscription
    {
        // Scoped to the account, so another account's subscription is
        // indistinguishable from one that does not exist.
        $subscription = Subscription::query()
            ->where('account_uuid', $accountUuid)
            ->where('uuid', $subscriptionUuid)
            ->first();

        if ($subscription === null) {
            throw ApiProblem::of(ErrorCode::SubscriptionNotFound);
        }

        return $subscription;
    }

    private function recordPeriod(Subscription $subscription, DateTimeImmutable $start, DateTimeImmutable $end, string $reason): void
    {
        SubscriptionPeriod::query()->create([
            'subscription_id' => $subscription->id,
            'period_start' => $start,
            'period_end' => $end,
            'reason' => $reason,
            'recorded_at' => $this->clock->now(),
        ]);
    }

    private function recordEvent(
        Subscription $subscription,
        ?SubscriptionStatus $from,
        SubscriptionStatus $to,
        string $reason,
    ): void {
        SubscriptionEvent::query()->create([
            'uuid' => (string) Str::uuid7(),
            'subscription_id' => $subscription->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'reason' => $reason,
            'occurred_at' => $this->clock->now(),
        ]);
    }
}
