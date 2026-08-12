<?php

declare(strict_types=1);

namespace Tests\Feature\Playback;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Entitlement\Contracts\EntitlementWriter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A streaming session over its whole life.
 *
 * A decision made when playback started is not a decision that stays true. The
 * cases here are the ones a licensor actually asks about — a blackout that
 * begins mid-event, an entitlement withdrawn while someone is watching — and
 * they are exactly the cases a start-only check does not cover.
 *
 * Note the absence of `Cache::flush()` after a session starts. The concurrency
 * ledger is authoritative in Redis, so flushing between a start and a heartbeat
 * would destroy the slot and the test would prove nothing about revalidation.
 */
final class SessionLifecycleTest extends TestCase
{
    #[Test]
    public function a_heartbeat_returns_a_fresh_delivery_token(): void
    {
        $world = $this->playableWorld();
        $start = $this->startPlayback($world)->assertCreated();
        $sessionId = $start->json('session_id');
        $original = $start->json('delivery.targets.0.url');

        // Far enough that the original token is nearly spent, but inside the
        // revalidation interval.
        $response = $this->beatUntil($world, $sessionId, '2026-08-11T12:03:00+00:00')->assertOk();

        /*
         * The point of a heartbeat is not only to say "still here". A response
         * carrying an expiry but no new URLs would leave a client watching a
         * stream whose token it cannot renew — playback would simply stop when
         * the token lapsed, with no error a viewer or a metric could explain.
         */
        $renewed = $response->json('delivery.targets.0.url');
        $this->assertNotSame($original, $renewed);
        $this->assertSame(
            parse_url((string) $original, PHP_URL_PATH),
            parse_url((string) $renewed, PHP_URL_PATH),
            'renewal must not move the viewer to a different object',
        );
    }

    #[Test]
    public function a_blackout_starting_mid_session_ends_playback_at_the_next_revalidation(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $this->postJson('/api/admin/v1/rights/blackouts', [
            'subject_type' => 'channel',
            'subject_id' => $world['channel_id'],
            'starts_at' => '2026-08-11T12:01:00Z',
            'ends_at' => '2026-08-11T14:00:00Z',
            'reason' => 'Regional sports blackout',
        ], $this->bearer($world['staff_token']))->assertCreated();

        // Inside the revalidation interval: the session continues, which is the
        // deliberate cost trade rather than an oversight.
        $this->beatUntil($world, $sessionId, '2026-08-11T12:02:00+00:00')->assertOk();

        // Past it: enforcement lands.
        $this->assertProblem(
            $this->beatUntil($world, $sessionId, '2026-08-11T12:06:00+00:00'),
            'PLAYBACK_BLACKED_OUT',
            403,
        );

        $session = DB::table('playback.sessions')->where('uuid', $sessionId)->first();
        $this->assertNotNull($session->ended_at);
        $this->assertSame('PLAYBACK_BLACKED_OUT', $session->end_reason);

        // The slot is released, so the viewer can immediately watch something
        // else rather than being told they have too many streams.
        $this->assertNotNull(
            DB::table('playback.concurrency_slots')->where('session_uuid', $sessionId)->value('released_at'),
        );
    }

    #[Test]
    public function a_mid_session_denial_is_recorded_alongside_the_original_grant(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $this->postJson('/api/admin/v1/rights/blackouts', [
            'subject_type' => 'channel', 'subject_id' => $world['channel_id'],
            'starts_at' => '2026-08-11T12:01:00Z', 'ends_at' => '2026-08-11T14:00:00Z',
            'reason' => 'Blackout',
        ], $this->bearer($world['staff_token']))->assertCreated();

        $this->beatUntil($world, $sessionId, '2026-08-11T12:06:00+00:00')->assertForbidden();

        $decisions = DB::table('playback.decisions')
            ->where('session_uuid', $sessionId)
            ->orderBy('decided_at')
            ->get();

        /*
         * Two decisions for one session is the shape a blackout query actually
         * needs: this viewer was permitted at one instant and refused at
         * another, and the record says when and why.
         */
        $this->assertCount(2, $decisions);
        $this->assertSame('allow', $decisions[0]->outcome);
        $this->assertSame('deny', $decisions[1]->outcome);
        $this->assertSame('PLAYBACK_BLACKED_OUT', $decisions[1]->reason_code);
        $this->assertStringContainsString('revalidation', (string) $decisions[1]->applied_restrictions);
    }

    #[Test]
    public function withdrawing_an_entitlement_ends_playback_at_the_next_revalidation(): void
    {
        $world = $this->playableWorld();
        $subscriptionId = $this->getJson('/api/client/v1/subscription', $this->bearer($world['access_token']))
            ->json('data.id');
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        /*
         * Through the real entitlement writer — the same path a failed payment
         * or a support-initiated removal takes.
         *
         * Deliberately not a cancellation: cancelling takes effect at the end
         * of a paid period, so a viewer mid-programme keeps watching. That is
         * correct, and it is why this exercises withdrawal instead.
         */
        $this->app->make(EntitlementWriter::class)->revokeForSubscription($subscriptionId, 'test-withdrawal');

        $this->assertProblem(
            $this->beatUntil($world, $sessionId, '2026-08-11T12:06:00+00:00'),
            'PLAYBACK_NOT_ENTITLED',
            403,
        );
    }

    #[Test]
    public function retiring_a_publication_ends_the_sessions_watching_it(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $this->postJson("/api/admin/v1/media/publications/{$world['publication_id']}/retire", [],
            $this->bearer($world['staff_token']))->assertNoContent();

        // Not a revalidation case: there is simply nowhere left to fetch from,
        // so the heartbeat cannot hand back a target and says so specifically.
        $this->assertProblem(
            $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/heartbeat", [],
                $this->bearer($world['access_token'])),
            'PLAYBACK_NO_DELIVERY_TARGET',
            503,
        );
    }

    #[Test]
    public function a_viewer_can_see_their_own_live_sessions(): void
    {
        $world = $this->playableWorld();
        $first = $this->startPlayback($world)->assertCreated()->json('session_id');
        $this->startPlayback($world)->assertCreated();

        $sessions = $this->getJson('/api/client/v1/playback/sessions', $this->bearer($world['access_token']))
            ->assertOk()
            ->json('data');

        // "Too many simultaneous streams" is unactionable without being able to
        // see which devices, so this is what turns the concurrency denial into
        // something a viewer can resolve rather than only be blocked by.
        $this->assertCount(2, $sessions);
        $this->assertContains($first, array_column($sessions, 'session_id'));
    }

    #[Test]
    public function a_stopped_session_cannot_be_resumed_by_heartbeat(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/stop", [],
            $this->bearer($world['access_token']))->assertNoContent();

        $this->assertProblem(
            $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/heartbeat", [],
                $this->bearer($world['access_token'])),
            'PLAYBACK_SESSION_ENDED',
            403,
        );
    }

    #[Test]
    public function the_reaper_closes_sessions_whose_client_stopped_heartbeating(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        // A crashed client sends no stop. Without the sweep the durable record
        // would show this viewer watching indefinitely, which makes every
        // concurrency question unanswerable and every usage report wrong.
        $this->freezeTimeAt('2026-08-11T12:10:00+00:00');

        $this->artisan('kms:playback:reap-sessions')->assertSuccessful();

        $session = DB::table('playback.sessions')->where('uuid', $sessionId)->first();
        $this->assertSame('heartbeat_expired', $session->end_reason);
        $this->assertNotNull($session->ended_at);

        // The durable slot mirror is released too: it is what a rebuild after a
        // Redis loss reads, and a mirror full of slots nobody holds would
        // rebuild a counter that denies playback to viewers watching nothing.
        $this->assertNotNull(
            DB::table('playback.concurrency_slots')->where('session_uuid', $sessionId)->value('released_at'),
        );
    }

    #[Test]
    public function the_reaper_leaves_live_sessions_alone_and_is_idempotent(): void
    {
        $world = $this->playableWorld();
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $this->artisan('kms:playback:reap-sessions')->assertSuccessful();
        $this->assertNull(DB::table('playback.sessions')->where('uuid', $sessionId)->value('ended_at'));

        $this->freezeTimeAt('2026-08-11T12:10:00+00:00');
        $this->artisan('kms:playback:reap-sessions')->assertSuccessful();
        $endedAt = DB::table('playback.sessions')->where('uuid', $sessionId)->value('ended_at');

        // Running it again must not move the recorded end time, or a repeated
        // sweep would rewrite history.
        $this->freezeTimeAt('2026-08-11T12:20:00+00:00');
        $this->artisan('kms:playback:reap-sessions')->assertSuccessful();

        $this->assertSame($endedAt, DB::table('playback.sessions')->where('uuid', $sessionId)->value('ended_at'));
    }

    #[Test]
    public function a_heartbeat_for_another_accounts_session_is_refused(): void
    {
        $world = $this->playableWorld(['email' => 'mine@example.test']);
        $sessionId = $this->startPlayback($world)->assertCreated()->json('session_id');

        $intruder = $this->signedInAccount('intruder@example.test');

        // A session identifier is not a capability. Ownership is checked
        // server-side, or one viewer could keep another's session alive — or
        // read where it is playing from.
        $this->assertProblem(
            $this->postJson("/api/client/v1/playback/sessions/{$sessionId}/heartbeat", [],
                $this->bearer($intruder['access_token'])),
            'PLAYBACK_SESSION_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function a_heartbeat_for_an_unknown_session_is_refused(): void
    {
        $world = $this->playableWorld();

        $this->assertProblem(
            $this->postJson('/api/client/v1/playback/sessions/'.Str::uuid7().'/heartbeat', [],
                $this->bearer($world['access_token'])),
            'PLAYBACK_SESSION_NOT_FOUND',
            404,
        );
    }

    /**
     * Advances the clock the way a real client does: heartbeating along the
     * way, never leaving a gap longer than the session TTL.
     *
     * Jumping straight to the target instant would lapse the concurrency slot
     * and the session would end as "client went away" — correct behaviour, and
     * not what these tests are about.
     *
     * @param  array<string,mixed>  $world
     */
    private function beatUntil(array $world, string $sessionId, string $target): TestResponse
    {
        $now = new \DateTimeImmutable('2026-08-11T12:00:00+00:00');
        $until = new \DateTimeImmutable($target);
        $step = (int) config('kms.playback.session_ttl_seconds') / 2;
        $response = null;

        while ($now < $until) {
            $now = min($now->modify("+{$step} seconds"), $until);
            $this->freezeTimeAt($now->format(DATE_RFC3339));

            $response = $this->postJson(
                "/api/client/v1/playback/sessions/{$sessionId}/heartbeat",
                [],
                $this->bearer($world['access_token']),
            );

            // Stop at the first refusal: that is usually what the test is
            // waiting for, and continuing would mask which beat produced it.
            if ($response->getStatusCode() !== 200) {
                return $response;
            }
        }

        return $response ?? $this->postJson(
            "/api/client/v1/playback/sessions/{$sessionId}/heartbeat",
            [],
            $this->bearer($world['access_token']),
        );
    }
}
