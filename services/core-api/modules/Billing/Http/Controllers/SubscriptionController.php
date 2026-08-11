<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Application\SubscriptionService;
use Modules\Billing\Infrastructure\Eloquent\Subscription;
use Modules\Shared\Domain\Identifier;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

final class SubscriptionController
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function show(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->liveSubscription(
            (string) $request->attributes->get('kms.account_uuid'),
        );

        return new JsonResponse([
            'data' => $subscription === null ? null : $this->present($subscription),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['plan_id' => ['required', 'uuid']]);

        $subscription = $this->subscriptions->subscribe(
            (string) $request->attributes->get('kms.account_uuid'),
            $validated['plan_id'],
        );

        return new JsonResponse(['data' => $this->present($subscription)], 201);
    }

    public function cancel(Request $request, string $subscriptionId): JsonResponse
    {
        $this->assertIdentifier($subscriptionId);

        $subscription = $this->subscriptions->cancel(
            (string) $request->attributes->get('kms.account_uuid'),
            $subscriptionId,
        );

        return new JsonResponse(['data' => $this->present($subscription)]);
    }

    public function resume(Request $request, string $subscriptionId): JsonResponse
    {
        $this->assertIdentifier($subscriptionId);

        $subscription = $this->subscriptions->resume(
            (string) $request->attributes->get('kms.account_uuid'),
            $subscriptionId,
        );

        return new JsonResponse(['data' => $this->present($subscription)]);
    }

    /** @return array<string,mixed> */
    private function present(Subscription $subscription): array
    {
        return [
            'id' => $subscription->uuid,
            'plan_id' => $subscription->plan_uuid,
            'status' => $subscription->status,
            'started_at' => $subscription->started_at?->format(DATE_RFC3339),
            'current_period_end' => $subscription->current_period_end?->format(DATE_RFC3339),
            'trial_ends_at' => $subscription->trial_ends_at?->format(DATE_RFC3339),
            'cancel_at_period_end' => (bool) $subscription->cancel_at_period_end,
        ];
    }

    private function assertIdentifier(string $value): void
    {
        if (Identifier::tryFromString($value) === null) {
            throw ApiProblem::of(ErrorCode::SubscriptionNotFound);
        }
    }
}
