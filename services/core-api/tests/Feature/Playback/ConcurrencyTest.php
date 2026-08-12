<?php

declare(strict_types=1);

namespace Tests\Feature\Playback;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ConcurrencyTest extends TestCase
{
    #[Test]
    public function it_enforces_the_plan_concurrency_limit(): void
    {
        $world = $this->playableWorld(['concurrency_limit' => 2]);

        $this->startPlayback($world)->assertCreated();
        $this->startPlayback($world)->assertCreated();

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_CONCURRENCY_EXCEEDED', 409);
    }

    #[Test]
    public function a_licensor_cap_lower_than_the_plan_limit_wins(): void
    {
        // The lower of the two always wins. A licensor's cap is not negotiable
        // by selling a bigger plan.
        $world = $this->playableWorld([
            'concurrency_limit' => 5,
            'rights' => ['window_start' => '2020-01-01T00:00:00Z', 'usage' => ['concurrency_cap' => 1]],
        ]);

        $this->startPlayback($world)->assertCreated();
        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_CONCURRENCY_EXCEEDED', 409);
    }

    #[Test]
    public function the_decision_records_which_limit_bound_the_request(): void
    {
        $world = $this->playableWorld([
            'concurrency_limit' => 5,
            'rights' => ['window_start' => '2020-01-01T00:00:00Z', 'usage' => ['concurrency_cap' => 1]],
        ]);

        $response = $this->startPlayback($world)->assertCreated();

        // The two sources fail differently under infrastructure failure, so the
        // decision has to know which one it was enforcing.
        $decision = DB::table('playback.decisions')->where('uuid', $response->json('decision_id'))->first();
        $this->assertSame('licensor', $decision->concurrency_source);
    }

    #[Test]
    public function stopping_a_session_releases_the_slot(): void
    {
        $world = $this->playableWorld(['concurrency_limit' => 1]);

        $first = $this->startPlayback($world)->assertCreated();
        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_CONCURRENCY_EXCEEDED', 409);

        $this->postJson('/api/client/v1/playback/sessions/'.$first->json('session_id').'/stop', [],
            $this->bearer($world['access_token']))->assertNoContent();

        // Releasing promptly is what keeps a viewer's allowance accurate.
        $this->startPlayback($world)->assertCreated();
    }

    #[Test]
    public function a_lapsed_heartbeat_frees_the_slot(): void
    {
        $world = $this->playableWorld(['concurrency_limit' => 1]);
        $this->startPlayback($world)->assertCreated();

        // A crashed client must not consume a viewer's allowance forever.
        $this->freezeTimeAt('2026-08-11T12:10:00+00:00');

        $this->startPlayback($world)->assertCreated();
    }

    #[Test]
    public function a_heartbeat_renews_the_session(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/heartbeat", [],
            $this->bearer($world['access_token']))
            ->assertOk()
            ->assertJsonPath('session_id', $sessionId)
            // A heartbeat hands back fresh targets, not just an expiry: a
            // client given an expiry and no new URLs cannot renew.
            ->assertJsonStructure([
                'heartbeat_interval_seconds',
                'delivery' => ['targets' => [['format', 'url', 'expires_at']], 'expires_at'],
            ]);
    }

    #[Test]
    public function a_heartbeat_after_the_slot_lapsed_ends_the_session(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $this->freezeTimeAt('2026-08-11T12:10:00+00:00');

        $this->assertProblem(
            $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/heartbeat", [],
                $this->bearer($world['access_token'])),
            'PLAYBACK_SESSION_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function one_account_cannot_touch_another_accounts_session(): void
    {
        $mine = $this->playableWorld(['email' => 'mine@example.test']);
        $sessionId = $this->startPlayback($mine)->assertCreated()->json('session_id');

        $theirs = $this->signedInAccount('theirs@example.test');

        $this->assertProblem(
            $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/heartbeat", [],
                $this->bearer($theirs['access_token'])),
            'PLAYBACK_SESSION_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function an_unknown_session_is_not_found(): void
    {
        $world = $this->playableWorld();

        $this->assertProblem(
            $this->postJson('/api/client/v1/playback/sessions/'.Str::uuid7().'/stop', [],
                $this->bearer($world['access_token'])),
            'PLAYBACK_SESSION_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function the_durable_slot_mirror_is_written_and_released(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        // Redis is authoritative for enforcement; this mirror is what lets the
        // counter be rebuilt after a Redis loss.
        $this->assertDatabaseHas('playback.concurrency_slots', [
            'session_uuid' => $sessionId, 'released_at' => null,
        ]);

        $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/stop", [],
            $this->bearer($world['access_token']))->assertNoContent();

        $this->assertNotNull(
            DB::table('playback.concurrency_slots')->where('session_uuid', $sessionId)->value('released_at'),
        );
    }
}
