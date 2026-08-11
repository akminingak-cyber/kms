<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
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
