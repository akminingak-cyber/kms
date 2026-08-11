<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Billing\Application\SubscriptionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SubscriptionTest extends TestCase
{
    #[Test]
    public function subscribing_grants_entitlement_and_makes_the_channel_playable(): void
    {
        $world = $this->playableWorld(['subscribe' => false]);

        // Before subscribing, the channel is visible but not playable — which
        // is what makes upsell possible.
        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_NOT_ENTITLED', 403);
        Cache::flush();

        $this->postJson('/api/client/v1/subscription', ['plan_id' => $world['plan_id']],
            $this->bearer($world['access_token']))->assertCreated();

        Cache::flush();

        $this->startPlayback($world)->assertCreated();
    }

    #[Test]
    public function every_grant_records_why_it_exists(): void
    {
        $world = $this->playableWorld();

        $grant = DB::table('entitlement.grants')->first();

        // Six months later, "why can this account watch this?" must be
        // answerable from a row.
        $this->assertSame('subscription', $grant->source);
        $this->assertNotNull($grant->source_ref);
        $this->assertSame('package', $grant->scope_type);
        $this->assertSame($world['package_id'], $grant->scope_ref);
    }

    #[Test]
    public function an_account_may_hold_only_one_live_subscription(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        // Two live subscriptions would produce overlapping grants and an
        // unanswerable billing question, so the database refuses it.
        $this->assertProblem(
            $this->postJson('/api/client/v1/subscription', ['plan_id' => $world['plan_id']],
                $this->bearer($world['access_token'])),
            'SUBSCRIPTION_ALREADY_ACTIVE',
            409,
        );
    }

    #[Test]
    public function cancelling_keeps_access_until_the_period_ends(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        $subscriptionId = $this->getJson('/api/client/v1/subscription',
            $this->bearer($world['access_token']))->json('data.id');

        $this->postJson("/api/client/v1/subscription/{$subscriptionId}/cancel", [],
            $this->bearer($world['access_token']))
            ->assertOk()
            ->assertJsonPath('data.cancel_at_period_end', true);

        Cache::flush();

        // Cancellation is a decision, not an immediate end: revoking now would
        // be a refund question nobody asked.
        $this->startPlayback($world)->assertCreated();
    }

    #[Test]
    public function a_cancelled_subscription_expires_at_the_period_end_and_revokes_access(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        $subscriptionId = $this->getJson('/api/client/v1/subscription',
            $this->bearer($world['access_token']))->json('data.id');

        $this->postJson("/api/client/v1/subscription/{$subscriptionId}/cancel", [],
            $this->bearer($world['access_token']))->assertOk();

        // Past the paid period.
        $this->freezeTimeAt('2026-09-12T12:00:00+00:00');

        $result = $this->app->make(SubscriptionService::class)->processDueRenewals();
        $this->assertSame(1, $result['expired']);

        $world = $this->reauthenticated($world);

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_NOT_ENTITLED', 403);
    }

    #[Test]
    public function an_uncancelled_subscription_renews_and_keeps_access(): void
    {
        $world = $this->playableWorld();

        $this->freezeTimeAt('2026-09-12T12:00:00+00:00');

        $result = $this->app->make(SubscriptionService::class)->processDueRenewals();
        $this->assertSame(1, $result['renewed']);

        $world = $this->reauthenticated($world);
        $this->startPlayback($world)->assertCreated();

        // Every transition is recorded: the subscription's status answers
        // "what now", the events answer "how did it get here".
        $this->assertDatabaseHas('billing.subscription_events', ['reason' => 'renewed']);
    }

    #[Test]
    public function resuming_a_cancelled_subscription_clears_the_schedule(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        $subscriptionId = $this->getJson('/api/client/v1/subscription',
            $this->bearer($world['access_token']))->json('data.id');

        $this->postJson("/api/client/v1/subscription/{$subscriptionId}/cancel", [],
            $this->bearer($world['access_token']))->assertOk();

        $this->postJson("/api/client/v1/subscription/{$subscriptionId}/resume", [],
            $this->bearer($world['access_token']))
            ->assertOk()
            ->assertJsonPath('data.cancel_at_period_end', false);
    }

    #[Test]
    public function it_refuses_an_unknown_plan(): void
    {
        $world = $this->playableWorld(['subscribe' => false]);
        Cache::flush();

        $this->assertProblem(
            $this->postJson('/api/client/v1/subscription', ['plan_id' => (string) Str::uuid7()],
                $this->bearer($world['access_token'])),
            'PLAN_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function one_account_cannot_cancel_another_accounts_subscription(): void
    {
        $mine = $this->playableWorld(['email' => 'mine@example.test']);
        Cache::flush();

        $subscriptionId = $this->getJson('/api/client/v1/subscription',
            $this->bearer($mine['access_token']))->json('data.id');

        $theirs = $this->signedInAccount('theirs@example.test');
        Cache::flush();

        $this->assertProblem(
            $this->postJson("/api/client/v1/subscription/{$subscriptionId}/cancel", [],
                $this->bearer($theirs['access_token'])),
            'SUBSCRIPTION_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function subscription_endpoints_require_authentication(): void
    {
        $this->assertProblem($this->getJson('/api/client/v1/subscription'), 'AUTH_REQUIRED', 401);
    }

    #[Test]
    public function plans_expose_money_as_minor_units_with_a_currency(): void
    {
        $this->playableWorld();
        Cache::flush();

        $price = $this->getJson('/api/client/v1/plans')->assertOk()->json('data.0.prices.0');

        // Never a bare number: that invites float arithmetic on the client and
        // rounding disputes at the till.
        $this->assertSame(999, $price['amount_minor']);
        $this->assertSame('EUR', $price['currency']);
    }
}
