<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    private const EMAIL = 'viewer@example.test';

    private const PASSWORD = 'correct-horse-battery-staple';

    private function register(): void
    {
        $this->postJson('/api/client/v1/auth/register', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
            'profile_name' => 'Primary',
            'accepts_terms' => true,
        ])->assertCreated();
    }

    private function login(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/client/v1/auth/login', array_merge([
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
            'device_class' => 'web',
            'device_name' => 'Test Browser',
        ], $overrides));
    }

    #[Test]
    public function it_issues_a_session_bound_to_a_device(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        $response = $this->login()->assertOk();

        $response->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'device_id']);
        $this->assertSame('Bearer', $response->json('token_type'));
    }

    #[Test]
    public function it_refuses_an_unverified_account(): void
    {
        $this->register();

        // Registration alone must not grant access: an unverified address may
        // belong to somebody else entirely.
        $this->assertProblem($this->login(), 'AUTH_EMAIL_NOT_VERIFIED', 403);
    }

    #[Test]
    public function it_refuses_a_wrong_password_with_the_same_code_as_an_unknown_account(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        $wrongPassword = $this->login(['password' => 'not-the-password']);
        $unknownAccount = $this->login(['email' => 'nobody@example.test', 'password' => 'anything-at-all']);

        // Identical responses. Distinguishing them would turn the endpoint into
        // an account-enumeration oracle.
        $this->assertProblem($wrongPassword, 'AUTH_INVALID_CREDENTIALS', 401);
        $this->assertProblem($unknownAccount, 'AUTH_INVALID_CREDENTIALS', 401);
    }

    #[Test]
    public function it_rejects_a_television_device_class_on_the_password_endpoint(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        // A television must use the activation flow. Accepting a password login
        // here would create a second, weaker path to the same session.
        $this->assertProblem($this->login(['device_class' => 'tizen']), 'VALIDATION_FAILED', 422);
    }

    #[Test]
    public function it_locks_the_account_after_repeated_failures(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        $threshold = (int) config('kms.accounts.lockout_threshold');

        for ($i = 0; $i < $threshold; $i++) {
            // The per-identity rate limiter would fire first, which is correct
            // defence in depth but not what this test is about. Clearing it
            // isolates the account-level control; RateLimitTest covers the other.
            Cache::flush();
            $this->login(['password' => 'wrong-password-'.$i]);
        }

        Cache::flush();

        // Lockout is account-scoped, so it survives an attacker rotating source
        // addresses — which a per-IP rate limit alone does not.
        $this->assertProblem($this->login(), 'AUTH_ACCOUNT_LOCKED', 423);

        $this->assertDatabaseHas('admin.audit_entries', ['action' => 'identity.account.locked']);
    }

    #[Test]
    public function it_clears_the_failure_count_after_a_successful_sign_in(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        $this->login(['password' => 'wrong'])->assertStatus(401);
        $this->login()->assertOk();

        $this->assertSame(0, (int) $this->accountFor(self::EMAIL)->failed_login_count);
    }

    #[Test]
    public function it_records_every_attempt_successful_or_not(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        $this->login(['password' => 'wrong'])->assertStatus(401);
        $this->login()->assertOk();

        // The failures are the detection surface for credential stuffing.
        $this->assertDatabaseHas('identity.login_attempts', ['email' => self::EMAIL, 'successful' => false]);
        $this->assertDatabaseHas('identity.login_attempts', ['email' => self::EMAIL, 'successful' => true]);
    }

    #[Test]
    public function it_enforces_the_device_limit(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        $limit = (int) config('kms.devices.default_limit');

        for ($i = 0; $i < $limit; $i++) {
            Cache::flush();
            $this->login(['device_name' => 'Device '.$i, 'device_fingerprint' => 'fp-'.$i])->assertOk();
        }

        Cache::flush();

        $this->assertProblem(
            $this->login(['device_name' => 'One too many', 'device_fingerprint' => 'fp-over']),
            'DEVICE_LIMIT_REACHED',
            409,
        );
    }

    #[Test]
    public function it_reuses_a_registration_when_the_same_device_signs_in_again(): void
    {
        $this->register();
        $this->verifyEmailFor(self::EMAIL);

        $first = $this->login(['device_fingerprint' => 'stable-device'])->assertOk();
        $second = $this->login(['device_fingerprint' => 'stable-device'])->assertOk();

        // Known hardware signing in again is a touch, not a new registration —
        // otherwise a user burns their device limit by signing out and in.
        $this->assertSame($first->json('device_id'), $second->json('device_id'));
    }
}
