<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Administration\Domain\TotpVerifier;
use Modules\Administration\Infrastructure\Eloquent\StaffUser;
use Modules\Identity\Application\EmailVerificationService;
use Modules\Identity\Application\PasswordResetService;
use Modules\Identity\Domain\EmailAddress;
use Modules\Identity\Infrastructure\Eloquent\Account;
use Modules\Shared\Domain\Clock;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Rate limiter state lives in Redis and would otherwise leak between
         * tests: every test in a class calls the same endpoint from the same
         * address, so the fifth registration test would be throttled by the
         * first four. Tests that exercise throttling do so deliberately.
         */
        Cache::flush();

        // Manifests go to a real local filesystem under a throwaway root — the
        // genuine adapter, not a substitute for one, so atomic-publish and
        // path-traversal behaviour are actually exercised.
        Storage::fake('manifests');

        /*
         * The encoder allow-list is empty by default and an empty list permits
         * nothing, so a ladder cannot be created until a licence decision has
         * been recorded. This is the *test suite's* recorded assumption, not a
         * platform default: no licence has been cleared for this repository,
         * and the values here are placeholders that exist so ladder tests can
         * run at all. The refusal path is tested explicitly.
         */
        config(['kms.media.permitted_encoders' => [
            ['encoder' => 'test-avc', 'codec' => 'avc', 'licence' => 'test-only', 'requires_gpl_build' => false],
        ]]);

        // Every test starts from a known instant. Token expiry, lockout
        // windows, activation codes and removal cooldowns are all time
        // dependent, and a test that sleeps is a test that is slow and flaky.
        $this->freezeTimeAt('2026-08-11T12:00:00+00:00');
    }

    /**
     * Moves the test clock.
     *
     * The already-bound clock is mutated rather than replaced. Services
     * resolved earlier in the test hold a reference to that instance — a
     * container singleton holding a Clock, for example — and re-binding would
     * leave them on the old time while `app(Clock::class)` reported the new
     * one. That inconsistency is very hard to see from a failing assertion.
     */
    protected function freezeTimeAt(string $iso): TestClock
    {
        $instant = new DateTimeImmutable($iso, new DateTimeZone('UTC'));
        $bound = $this->app->bound(Clock::class) ? $this->app->make(Clock::class) : null;

        if ($bound instanceof TestClock) {
            $bound->setTo($instant);

            return $bound;
        }

        $clock = new TestClock($instant);
        $this->app->instance(Clock::class, $clock);

        return $clock;
    }

    /** Registers, verifies and signs in, returning the tokens and identifiers. */
    protected function signedInAccount(
        string $email = 'viewer@example.test',
        string $password = 'correct-horse-battery-staple',
        string $deviceClass = 'web',
    ): array {
        $this->postJson('/api/client/v1/auth/register', [
            'email' => $email,
            'password' => $password,
            'profile_name' => 'Primary',
            'accepts_terms' => true,
        ])->assertCreated();

        $this->verifyEmailFor($email);

        $login = $this->postJson('/api/client/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'device_class' => $deviceClass,
            'device_name' => 'Test Device',
        ])->assertOk();

        return [
            'access_token' => $login->json('access_token'),
            'refresh_token' => $login->json('refresh_token'),
            'device_id' => $login->json('device_id'),
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * Only the hash of a verification token is stored, so the plaintext cannot
     * be read back — which is the point. Rather than adding a debug endpoint
     * that would then exist in production, tests mint a fresh token through the
     * same service the application uses, and exercise the real verify path.
     */
    protected function verifyEmailFor(string $email): void
    {
        $plaintext = $this->issueEmailVerificationToken($email);

        $this->postJson('/api/client/v1/auth/verify-email', ['token' => $plaintext])->assertOk();
    }

    protected function issueEmailVerificationToken(string $email): string
    {
        $account = Account::query()->where('email', strtolower($email))->firstOrFail();

        return $this->app->make(EmailVerificationService::class)->issue($account);
    }

    protected function issuePasswordResetToken(string $email): string
    {
        $token = $this->app->make(PasswordResetService::class)
            ->request(EmailAddress::fromString($email));

        return (string) $token;
    }

    protected function accountFor(string $email): Account
    {
        return Account::query()->where('email', strtolower($email))->firstOrFail();
    }

    /**
     * Builds a complete, playable world: category, channel, package, plan,
     * worldwide live rights, and a subscribed account with a session.
     *
     * @param  array<string,mixed>  $overrides
     * @return array<string,mixed>
     */
    protected function playableWorld(array $overrides = []): array
    {
        $staff = $this->staffToken($overrides['staff_role'] ?? 'platform_engineer');

        $channelId = $this->postJson('/api/admin/v1/channels', [
            'slug' => $overrides['channel_slug'] ?? 'one',
            'name' => 'Channel One',
            'number' => $overrides['channel_number'] ?? 1,
            'catchup_enabled' => true,
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $packageId = $this->postJson('/api/admin/v1/packages', [
            'slug' => $overrides['package_slug'] ?? 'basic',
            'name' => 'Basic',
            'channel_ids' => [$channelId],
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $planId = $this->postJson('/api/admin/v1/plans', [
            'slug' => $overrides['plan_slug'] ?? 'basic-monthly',
            'name' => 'Basic Monthly',
            'package_id' => $packageId,
            'billing_period' => 'monthly',
            'concurrency_limit' => $overrides['concurrency_limit'] ?? 2,
            'price' => ['amount_minor' => 999, 'currency' => 'EUR'],
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $agreementId = $this->postJson('/api/admin/v1/rights/agreements', [
            'counterparty' => 'Studio', 'reference' => 'AGR-1',
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        if ($overrides['grant_rights'] ?? true) {
            $this->postJson('/api/admin/v1/rights/rights', array_merge([
                'agreement_id' => $agreementId,
                'subject_type' => 'channel',
                'subject_id' => $channelId,
                'exploitation' => 'live',
                'window_start' => '2020-01-01T00:00:00Z',
            ], $overrides['rights'] ?? []), $this->bearer($staff))->assertCreated();
        }

        /*
         * Encode and publish, so playback resolves a real delivery target.
         *
         * Not optional scaffolding: a channel that nobody has encoded is a
         * denial, and a test world in which authorization succeeds against
         * media that does not exist would prove the platform works in a
         * situation it must refuse.
         */
        $publication = null;

        if ($overrides['publish_media'] ?? true) {
            $publication = $this->publishedMedia($staff, $channelId, $overrides);
        }

        Cache::flush();
        $session = $this->signedInAccount($overrides['email'] ?? 'viewer@example.test');
        Cache::flush();

        $profileId = $this->getJson('/api/client/v1/profiles', $this->bearer($session['access_token']))
            ->json('data.0.id');

        if ($overrides['subscribe'] ?? true) {
            $this->postJson('/api/client/v1/subscription', ['plan_id' => $planId],
                $this->bearer($session['access_token']))->assertCreated();
            Cache::flush();
        }

        return [
            'staff_token' => $staff,
            'channel_id' => $channelId,
            'package_id' => $packageId,
            'plan_id' => $planId,
            'agreement_id' => $agreementId,
            'profile_id' => $profileId,
            'publication_id' => $publication,
        ] + $session;
    }

    /**
     * A ladder, a packaging profile, and a published channel.
     *
     * The numbers are chosen so the alignment invariants hold rather than
     * merely pass: 2000ms segments over 1000ms GOPs at 25fps is 25 whole frames
     * per GOP and two whole GOPs per segment, so every rendition can put an IDR
     * frame at every segment boundary.
     *
     * @param  array<string,mixed>  $overrides
     */
    protected function publishedMedia(string $staff, string $channelId, array $overrides = []): string
    {
        $ladderId = $this->postJson('/api/admin/v1/media/ladders', [
            'slug' => 'ladder-'.substr($channelId, 0, 8),
            'name' => 'Test Ladder',
            'content_class' => 'generic',
            'rungs' => $overrides['rungs'] ?? $this->defaultRungs(),
            'audio' => [[
                'label' => 'audio-en', 'language' => 'en', 'role' => 'main', 'codec' => 'aac-lc',
                'bitrate_kbps' => 128, 'channels' => 2, 'sample_rate_hz' => 48_000,
                'source_stream_index' => 0, 'is_default' => true,
            ]],
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $packagingId = $this->postJson('/api/admin/v1/media/packaging-profiles', [
            'slug' => 'pkg-'.substr($channelId, 0, 8),
            'name' => 'Test Packaging',
            'container' => 'cmaf',
            'segment_duration_ms' => 2000,
            'gop_duration_ms' => 1000,
            'timescale' => 90_000,
            'playlist_window_segments' => 6,
            'time_shift_buffer_seconds' => 60,
            'suggested_presentation_delay_ms' => 6000,
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $publicationId = $this->postJson('/api/admin/v1/media/publications', [
            'subject_type' => 'channel',
            'subject_id' => $channelId,
            'ladder_id' => $ladderId,
            'packaging_profile_id' => $packagingId,
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $this->postJson("/api/admin/v1/media/publications/{$publicationId}/publish", [],
            $this->bearer($staff))->assertCreated();

        return (string) $publicationId;
    }

    /** @return list<array<string,mixed>> */
    protected function defaultRungs(): array
    {
        return [
            [
                'label' => 'v360p', 'width' => 640, 'height' => 360,
                'video_bitrate_kbps' => 800, 'max_bitrate_kbps' => 1000,
                'codec' => 'avc', 'encoder' => 'test-avc', 'profile' => 'main', 'level' => 30,
                'frame_rate' => '25',
            ],
            [
                'label' => 'v720p', 'width' => 1280, 'height' => 720,
                'video_bitrate_kbps' => 2500, 'max_bitrate_kbps' => 3200,
                'codec' => 'avc', 'encoder' => 'test-avc', 'profile' => 'high', 'level' => 31,
                'frame_rate' => '25',
            ],
            [
                'label' => 'v1080p', 'width' => 1920, 'height' => 1080,
                'video_bitrate_kbps' => 5000, 'max_bitrate_kbps' => 6500,
                'codec' => 'avc', 'encoder' => 'test-avc', 'profile' => 'high', 'level' => 40,
                'frame_rate' => '25',
            ],
        ];
    }

    /** A staff access token with the given role. */
    protected function staffToken(string $role = 'platform_engineer'): string
    {
        $secret = TotpVerifier::generateSecret();

        StaffUser::query()->create([
            'uuid' => (string) Str::uuid7(),
            'email' => $role.'@staff.test',
            'name' => 'Staff',
            'password_hash' => Hash::make('staff-password-long-enough'),
            'role' => $role,
            'totp_secret' => $secret,
            'mfa_enrolled_at' => now(),
            'status' => 'active',
        ]);

        Cache::flush();

        $token = $this->postJson('/api/admin/v1/auth/sign-in', [
            'email' => $role.'@staff.test',
            'password' => 'staff-password-long-enough',
            'totp_code' => (new TotpVerifier)->codeFor(
                $secret,
                $this->app->make(Clock::class)->now()->getTimestamp(),
            ),
        ])->assertOk()->json('access_token');

        Cache::flush();

        return $token;
    }

    /**
     * Signs in again from the same device.
     *
     * Used after a time jump longer than the refresh-token lifetime, where a
     * real viewer would simply sign in again. The fingerprint is stable, so
     * this touches the existing device rather than consuming another slot.
     *
     * @param  array<string,mixed>  $world
     * @return array<string,mixed>
     */
    protected function reauthenticated(array $world): array
    {
        Cache::flush();

        $login = $this->postJson('/api/client/v1/auth/login', [
            'email' => $world['email'],
            'password' => $world['password'],
            'device_class' => 'web',
            'device_name' => 'Test Device',
            'device_fingerprint' => 'stable-'.$world['email'],
        ])->assertOk();

        Cache::flush();

        return array_merge($world, [
            'access_token' => $login->json('access_token'),
            'refresh_token' => $login->json('refresh_token'),
        ]);
    }

    /**
     * Re-authenticates after a time jump, as a real client would.
     *
     * Access tokens are short-lived by design, so any test that moves time
     * further than that has to refresh rather than reuse a dead token.
     *
     * @param  array<string,mixed>  $world
     * @return array<string,mixed>
     */
    protected function refreshed(array $world): array
    {
        Cache::flush();

        $response = $this->postJson('/api/client/v1/auth/refresh', [
            'refresh_token' => $world['refresh_token'],
        ])->assertOk();

        Cache::flush();

        return array_merge($world, [
            'access_token' => $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token'),
        ]);
    }

    /** Starts playback with the standard body. */
    protected function startPlayback(array $world, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/client/v1/playback/sessions', array_merge([
            'content_id' => $world['channel_id'],
            'profile_id' => $world['profile_id'],
            'mode' => 'live',
        ], $overrides), $this->bearer($world['access_token']));
    }

    /** @return array<string,string> */
    protected function bearer(string $accessToken): array
    {
        return ['Authorization' => 'Bearer '.$accessToken];
    }

    protected function assertProblem(TestResponse $response, string $code, int $status): void
    {
        $response->assertStatus($status);
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertJsonPath('code', $code);
        // Every problem carries a correlation id, so a screenshot is enough to
        // find the request.
        $this->assertNotEmpty($response->json('correlation_id'));
    }
}
