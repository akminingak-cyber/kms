<?php

declare(strict_types=1);

namespace Tests\Feature\Playback;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The decision.
 *
 * Every denial path is proved, not just the happy one: a denial that silently
 * becomes an allow is the failure that costs a content deal, and it produces no
 * error metric at all.
 */
final class PlaybackAuthorizationTest extends TestCase
{
    #[Test]
    public function it_authorizes_playback_and_returns_an_ordered_delivery_target_list(): void
    {
        $world = $this->playableWorld();

        $response = $this->startPlayback($world)->assertCreated();

        $response->assertJsonStructure([
            'session_id', 'decision_id',
            'delivery' => ['targets' => [['format', 'container', 'url', 'priority']], 'expires_at'],
            'heartbeat_interval_seconds',
        ]);

        // A list from day one, even with a single entry: clients that cannot
        // handle a list would block multi-CDN adoption years later.
        $this->assertIsArray($response->json('delivery.targets'));

        // DRM is a later phase. Absent, not stubbed with something plausible.
        $this->assertNull($response->json('protection'));
    }

    #[Test]
    public function every_decision_is_recorded_with_the_inputs_that_produced_it(): void
    {
        $world = $this->playableWorld();
        $response = $this->startPlayback($world)->assertCreated();

        $decision = DB::table('playback.decisions')->where('uuid', $response->json('decision_id'))->first();

        $this->assertNotNull($decision);
        $this->assertSame('allow', $decision->outcome);
        $this->assertNotNull($decision->availability_version, 'the rights version must be recorded');
        $this->assertNotEmpty(json_decode((string) $decision->rights_rule_ids, true));
        $this->assertNotEmpty(json_decode((string) $decision->entitlement_grant_ids, true));
        $this->assertNotNull($decision->territory_method, 'how territory was determined must be recorded');
    }

    #[Test]
    public function a_denial_is_recorded_as_deliberately_as_a_grant(): void
    {
        $world = $this->playableWorld(['subscribe' => false]);

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_NOT_ENTITLED', 403);

        $decision = DB::table('playback.decisions')->where('outcome', 'deny')->first();
        $this->assertNotNull($decision);
        $this->assertSame('PLAYBACK_NOT_ENTITLED', $decision->reason_code);
    }

    #[Test]
    public function it_denies_when_no_right_exists_at_all(): void
    {
        // Default deny: content ingested before its rights are entered is
        // unplayable, and absence of a right is a prohibition.
        $world = $this->playableWorld(['grant_rights' => false]);

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_OUTSIDE_LICENCE_WINDOW', 403);
    }

    #[Test]
    public function it_denies_outside_the_licence_window(): void
    {
        $world = $this->playableWorld([
            'rights' => ['window_start' => '2020-01-01T00:00:00Z', 'window_end' => '2020-06-01T00:00:00Z'],
        ]);

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_OUTSIDE_LICENCE_WINDOW', 403);
    }

    #[Test]
    public function it_denies_a_territory_the_agreement_excludes(): void
    {
        config(['kms.playback.default_territory' => 'DE']);

        $world = $this->playableWorld([
            'rights' => [
                'window_start' => '2020-01-01T00:00:00Z',
                'territory_rules' => [
                    ['effect' => 'include', 'territories' => ['GB', 'IE', 'DE']],
                    // Later rules win, so a correction appended to an agreement
                    // takes effect without rewriting what came before.
                    ['effect' => 'exclude', 'territories' => ['DE']],
                ],
            ],
        ]);

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_NOT_AVAILABLE_IN_TERRITORY', 403);
    }

    #[Test]
    public function it_allows_a_territory_the_agreement_includes(): void
    {
        config(['kms.playback.default_territory' => 'GB']);

        $world = $this->playableWorld([
            'rights' => [
                'window_start' => '2020-01-01T00:00:00Z',
                'territory_rules' => [['effect' => 'include', 'territories' => ['GB']]],
            ],
        ]);

        $this->startPlayback($world)->assertCreated();
    }

    #[Test]
    public function it_denies_a_device_class_the_agreement_excludes(): void
    {
        // The session signs in from a web browser; the right permits TVs only.
        $world = $this->playableWorld([
            'rights' => [
                'window_start' => '2020-01-01T00:00:00Z',
                'platforms' => ['tizen', 'webos', 'androidtv'],
            ],
        ]);

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_PLATFORM_NOT_PERMITTED', 403);
    }

    #[Test]
    public function a_blackout_overrides_an_otherwise_valid_right(): void
    {
        $world = $this->playableWorld();
        $this->startPlayback($world)->assertCreated();
        Cache::flush();

        // Blackouts are never projected: they take effect immediately, which is
        // exactly why they are read live at decision time.
        $this->postJson('/api/admin/v1/rights/blackouts', [
            'subject_type' => 'channel',
            'subject_id' => $world['channel_id'],
            'starts_at' => '2026-08-11T11:00:00Z',
            'ends_at' => '2026-08-11T14:00:00Z',
            'reason' => 'Regional sports blackout',
        ], $this->bearer($world['staff_token']))->assertCreated();

        Cache::flush();

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_BLACKED_OUT', 403);
    }

    #[Test]
    public function it_denies_content_above_the_profiles_parental_limit(): void
    {
        $world = $this->playableWorld();

        $restricted = $this->postJson('/api/client/v1/profiles', ['name' => 'Kids', 'max_rating' => 'PG'],
            $this->bearer($world['access_token']))->assertCreated()->json('data.id');

        Cache::flush();

        // The channel carries no rating, which is treated as unrated rather
        // than unrestricted — default deny extends to missing information.
        $this->assertProblem(
            $this->startPlayback($world, ['profile_id' => $restricted]),
            'PLAYBACK_PARENTAL_BLOCKED',
            403,
        );
    }

    #[Test]
    public function it_refuses_a_profile_belonging_to_another_account(): void
    {
        $mine = $this->playableWorld(['email' => 'mine@example.test']);
        Cache::flush();

        $theirs = $this->signedInAccount('theirs@example.test');
        Cache::flush();

        $theirProfile = $this->getJson('/api/client/v1/profiles', $this->bearer($theirs['access_token']))
            ->json('data.0.id');

        Cache::flush();

        // A profile identifier from another account would otherwise select that
        // household's parental settings — so ownership is verified server-side
        // rather than trusted from the body.
        $this->assertProblem(
            $this->startPlayback($mine, ['profile_id' => $theirProfile]),
            'PROFILE_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function it_denies_unknown_content(): void
    {
        $world = $this->playableWorld();

        $this->assertProblem(
            $this->startPlayback($world, ['content_id' => (string) Str::uuid7()]),
            'PLAYBACK_CONTENT_UNAVAILABLE',
            503,
        );
    }

    #[Test]
    public function it_denies_a_mode_that_is_not_licensed(): void
    {
        // Rights exist for `live` only. Catch-up is a separately licensed
        // exploitation, not a feature of having the channel.
        $world = $this->playableWorld();

        $this->assertProblem(
            $this->startPlayback($world, ['mode' => 'catchup']),
            'PLAYBACK_OUTSIDE_LICENCE_WINDOW',
            403,
        );
    }

    #[Test]
    public function it_denies_when_the_territory_cannot_be_determined(): void
    {
        // Fail closed. Guessing a territory for content that is licensed
        // territorially is a contract breach waiting to happen, so an
        // undetermined territory denies rather than assumes.
        config(['kms.playback.default_territory' => null]);

        $world = $this->playableWorld();

        $this->assertProblem($this->startPlayback($world), 'PLAYBACK_TERRITORY_UNDETERMINED', 403);
    }

    #[Test]
    public function playback_requires_authentication(): void
    {
        $this->assertProblem(
            $this->postJson('/api/client/v1/playback/sessions', [
                'content_id' => (string) Str::uuid7(),
                'profile_id' => (string) Str::uuid7(),
                'mode' => 'live',
            ]),
            'AUTH_REQUIRED',
            401,
        );
    }

    #[Test]
    public function the_delivery_url_keeps_the_token_out_of_the_path(): void
    {
        $world = $this->playableWorld();
        $target = $this->startPlayback($world)->assertCreated()->json('delivery.targets.0');

        [$path, $query] = explode('?', (string) $target['url'], 2);

        // The path is identical for every viewer, so an edge configured to
        // exclude the token from the cache key gets one cache entry per asset
        // rather than one per viewer.
        $this->assertStringContainsString($world['channel_id'], $path);
        $this->assertStringNotContainsString('token', $path);
        $this->assertStringContainsString('token=', $query);
    }
}
