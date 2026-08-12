<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The admin read surface.
 *
 * Until this phase the admin API was write-only: an operator could create a
 * channel, a plan or a right and had no way to see any of them. An admin panel
 * built on that would be a set of forms with no visibility, which is not an
 * admin panel.
 *
 * The role tests carry as much weight as the shape tests. Read access is wider
 * than write access on purpose — support needs to see what it cannot change —
 * but "wider" is not "open", and a route that forgets to name its roles is
 * closed rather than public.
 */
final class AdminReadSurfaceTest extends TestCase
{
    #[Test]
    public function it_reports_who_the_signed_in_staff_member_is(): void
    {
        $token = $this->staffToken('content_operator');

        $response = $this->getJson('/api/admin/v1/me', $this->bearer($token))->assertOk();

        $response->assertJsonPath('data.role', 'content_operator');
        $this->assertNotEmpty($response->json('data.id'));
    }

    #[Test]
    public function the_channel_list_includes_channels_a_viewer_cannot_see(): void
    {
        $world = $this->playableWorld();

        $channels = $this->getJson('/api/admin/v1/channels', $this->bearer($world['staff_token']))
            ->assertOk()
            ->json('data');

        // Deliberately not the client endpoint with a staff token on it: the
        // client list is filtered to what is playable, and "why is this channel
        // missing from the app?" is answered by seeing what that filter removes.
        $this->assertCount(1, $channels);
        $this->assertSame($world['channel_id'], $channels[0]['id']);
        $this->assertArrayHasKey('status', $channels[0]);
        $this->assertArrayHasKey('next_cursor', $this->getJson('/api/admin/v1/channels',
            $this->bearer($world['staff_token']))->json('page'));
    }

    #[Test]
    public function a_channel_detail_shows_what_has_actually_been_ingested(): void
    {
        $world = $this->playableWorld();

        $response = $this->getJson("/api/admin/v1/channels/{$world['channel_id']}",
            $this->bearer($world['staff_token']))->assertOk();

        // "The EPG looks wrong" is answered by looking at rows rather than at a
        // count of them.
        $response->assertJsonStructure(['data' => ['id', 'slug', 'status', 'recent_programmes']]);
    }

    #[Test]
    public function an_unknown_channel_is_not_found_rather_than_a_server_error(): void
    {
        $token = $this->staffToken();

        $this->assertProblem(
            $this->getJson('/api/admin/v1/channels/019ff0f4-0000-7000-8000-0000000000ff', $this->bearer($token)),
            'CHANNEL_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function plans_carry_their_price_as_minor_units_with_a_currency(): void
    {
        $world = $this->playableWorld();

        $plans = $this->getJson('/api/admin/v1/plans', $this->bearer($world['staff_token']))
            ->assertOk()
            ->json('data');

        $this->assertSame(999, $plans[0]['price']['amount_minor']);
        // Never a float, and never an integer without the currency beside it.
        $this->assertSame('EUR', $plans[0]['price']['currency']);
        $this->assertSame('Basic', $plans[0]['package']['name']);
    }

    #[Test]
    public function rights_decode_their_bitmasks_into_names(): void
    {
        $world = $this->playableWorld([
            'rights' => [
                'window_start' => '2020-01-01T00:00:00Z',
                'platforms' => ['web', 'tizen'],
                'usage' => ['max_resolution' => '720p'],
            ],
        ]);

        $rights = $this->getJson('/api/admin/v1/rights/rights', $this->bearer($world['staff_token']))
            ->assertOk()
            ->json('data');

        // "platform_mask: 17" is not something anyone should decode by hand
        // while a licensor is on the phone.
        $this->assertSame(['web', 'tizen'], $rights[0]['platforms']);
        $this->assertSame('720p', $rights[0]['usage_rules']['max_resolution']);
        $this->assertSame('Studio', $rights[0]['agreement']['counterparty']);
    }

    #[Test]
    public function an_unknown_agreement_filter_matches_nothing_rather_than_everything(): void
    {
        $world = $this->playableWorld();

        $rights = $this->getJson(
            '/api/admin/v1/rights/rights?agreement_id=019ff0f4-0000-7000-8000-0000000000ff',
            $this->bearer($world['staff_token']),
        )->assertOk()->json('data');

        // A filter that silently stops filtering is how an operator concludes a
        // right exists when it does not.
        $this->assertSame([], $rights);
    }

    #[Test]
    public function the_decision_log_answers_why_a_viewer_was_refused(): void
    {
        $world = $this->playableWorld(['subscribe' => false]);
        $this->startPlayback($world)->assertForbidden();

        $decisions = $this->getJson('/api/admin/v1/playback/decisions?outcome=deny',
            $this->bearer($world['staff_token']))->assertOk()->json('data');

        $this->assertCount(1, $decisions);
        $this->assertSame('PLAYBACK_NOT_ENTITLED', $decisions[0]['reason_code']);
        // The inputs that produced the verdict, not just the verdict.
        $this->assertSame($world['channel_id'], $decisions[0]['content_id']);
        $this->assertNotNull($decisions[0]['territory_method']);
        $this->assertArrayHasKey('rights_rule_ids', $decisions[0]);
    }

    #[Test]
    public function the_decision_log_exposes_no_personal_data(): void
    {
        $world = $this->playableWorld();
        $this->startPlayback($world)->assertCreated();

        $body = $this->getJson('/api/admin/v1/playback/decisions', $this->bearer($world['staff_token']))
            ->assertOk()
            ->getContent();

        /*
         * Accounts appear as identifiers, never as names or email addresses.
         * Otherwise a diagnostic tool becomes a viewing-history search engine
         * over the whole subscriber base.
         */
        $this->assertStringNotContainsString('viewer@example.test', (string) $body);
        $this->assertStringNotContainsString('email', (string) $body);
    }

    #[Test]
    public function a_session_whose_heartbeat_lapsed_is_marked_as_such(): void
    {
        $world = $this->playableWorld();
        $this->startPlayback($world)->assertCreated();

        $live = $this->getJson('/api/admin/v1/playback/sessions', $this->bearer($world['staff_token']))
            ->assertOk()->json('data');
        $this->assertFalse($live[0]['heartbeat_lapsed']);

        // Past the session TTL with no heartbeat. A leaked concurrency slot is
        // invisible if the panel only shows a timestamp.
        $this->freezeTimeAt('2026-08-11T12:10:00+00:00');
        Cache::flush();
        $world = $this->reauthenticated($world);

        $stale = $this->getJson('/api/admin/v1/playback/sessions', $this->bearer($world['staff_token']))
            ->assertOk()->json('data');
        $this->assertTrue($stale[0]['heartbeat_lapsed']);
    }

    #[Test]
    public function the_staff_list_never_discloses_a_credential(): void
    {
        $token = $this->staffToken('auditor');

        $body = $this->getJson('/api/admin/v1/staff', $this->bearer($token))->assertOk()->getContent();

        foreach (['password', 'hash', 'totp_secret', '$2y$'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, (string) $body);
        }

        $this->assertTrue($this->getJson('/api/admin/v1/staff', $this->bearer($token))
            ->json('data.0.mfa_enrolled'));
    }

    #[Test]
    public function publications_carry_the_hashes_of_the_manifests_actually_written(): void
    {
        $world = $this->playableWorld();

        $publications = $this->getJson('/api/admin/v1/media/publications', $this->bearer($world['staff_token']))
            ->assertOk()->json('data');

        $this->assertSame('published', $publications[0]['status']);
        // "Did that republish change anything?" is answered from a hash rather
        // than by diffing two manifests by eye.
        $this->assertCount(10, $publications[0]['manifests']);
        $this->assertNotEmpty($publications[0]['manifests'][0]['content_hash']);
    }

    #[Test]
    public function every_read_route_refuses_a_role_that_was_not_named(): void
    {
        // A support agent may read the catalog and the decision log, and may
        // not read rights, media configuration or the staff list.
        $support = $this->staffToken('support_agent');

        foreach (['/channels', '/categories', '/plans', '/playback/decisions', '/me'] as $permitted) {
            $this->getJson('/api/admin/v1'.$permitted, $this->bearer($support))->assertOk();
        }

        foreach (['/rights/rights', '/rights/agreements', '/media/publications', '/staff'] as $refused) {
            $this->getJson('/api/admin/v1'.$refused, $this->bearer($support))
                ->assertForbidden();
        }
    }

    #[Test]
    public function every_read_route_requires_staff_authentication(): void
    {
        foreach ([
            '/me', '/staff', '/channels', '/categories', '/packages', '/plans',
            '/rights/rights', '/rights/agreements', '/rights/blackouts',
            '/media/publications', '/media/packaging-profiles',
            '/playback/decisions', '/playback/sessions',
        ] as $route) {
            $this->getJson('/api/admin/v1'.$route)->assertUnauthorized();
        }
    }

    #[Test]
    public function a_page_limit_beyond_the_maximum_is_refused(): void
    {
        $token = $this->staffToken();

        // Bounded so a client cannot ask for a page that costs more than the
        // request is worth.
        $this->assertProblem(
            $this->getJson('/api/admin/v1/channels?limit=5000', $this->bearer($token)),
            'VALIDATION_FAILED',
            422,
        );
    }
}
