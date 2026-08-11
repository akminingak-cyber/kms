<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    #[Test]
    public function it_responds_identically_for_known_and_unknown_addresses(): void
    {
        $this->signedInAccount('known@example.test');

        $known = $this->postJson('/api/client/v1/auth/password/forgot', ['email' => 'known@example.test']);
        $unknown = $this->postJson('/api/client/v1/auth/password/forgot', ['email' => 'nobody@example.test']);

        // This endpoint is unauthenticated. Distinguishing the two would turn
        // it into an account-enumeration oracle.
        $known->assertStatus(202);
        $unknown->assertStatus(202);
        $this->assertSame($known->getContent(), $unknown->getContent());
    }

    #[Test]
    public function resetting_a_password_revokes_every_existing_session(): void
    {
        $session = $this->signedInAccount('reset@example.test');
        $token = $this->issuePasswordResetToken('reset@example.test');

        $this->postJson('/api/client/v1/auth/password/reset', [
            'token' => $token,
            'password' => 'a-brand-new-password-entirely',
        ])->assertNoContent();

        // A reset performed because an account was compromised, that leaves the
        // attacker's session alive, achieves nothing.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => $session['refresh_token']]),
            'AUTH_TOKEN_REVOKED',
            401,
        );

        $this->assertDatabaseHas('admin.audit_entries', ['action' => 'identity.password.reset_completed']);
    }

    #[Test]
    public function the_new_password_works_and_the_old_one_does_not(): void
    {
        $this->signedInAccount('reset@example.test');
        $token = $this->issuePasswordResetToken('reset@example.test');

        $this->postJson('/api/client/v1/auth/password/reset', [
            'token' => $token, 'password' => 'a-brand-new-password-entirely',
        ])->assertNoContent();

        $this->postJson('/api/client/v1/auth/login', [
            'email' => 'reset@example.test', 'password' => 'correct-horse-battery-staple',
            'device_class' => 'web', 'device_name' => 'Browser',
        ])->assertStatus(401);

        $this->postJson('/api/client/v1/auth/login', [
            'email' => 'reset@example.test', 'password' => 'a-brand-new-password-entirely',
            'device_class' => 'web', 'device_name' => 'Browser',
        ])->assertOk();
    }

    #[Test]
    public function a_reset_token_is_single_use(): void
    {
        $this->signedInAccount('reset@example.test');
        $token = $this->issuePasswordResetToken('reset@example.test');

        $this->postJson('/api/client/v1/auth/password/reset', [
            'token' => $token, 'password' => 'a-brand-new-password-entirely',
        ])->assertNoContent();

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/password/reset', [
                'token' => $token, 'password' => 'yet-another-password-here',
            ]),
            'ACCOUNT_RESET_INVALID',
            422,
        );
    }

    #[Test]
    public function an_expired_reset_token_is_rejected(): void
    {
        $this->signedInAccount('reset@example.test');
        $token = $this->issuePasswordResetToken('reset@example.test');

        $clock = $this->freezeTimeAt('2026-08-11T12:00:00+00:00');
        $clock->advance('+'.((int) config('kms.accounts.password_reset_ttl_seconds') + 60).' seconds');

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/password/reset', [
                'token' => $token, 'password' => 'a-brand-new-password-entirely',
            ]),
            'ACCOUNT_RESET_EXPIRED',
            422,
        );
    }

    #[Test]
    public function issuing_a_new_reset_token_invalidates_the_previous_one(): void
    {
        $this->signedInAccount('reset@example.test');
        $first = $this->issuePasswordResetToken('reset@example.test');
        $second = $this->issuePasswordResetToken('reset@example.test');

        // Only the newest link works, so a link from an intercepted older email
        // cannot be used later.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/password/reset', [
                'token' => $first, 'password' => 'a-brand-new-password-entirely',
            ]),
            'ACCOUNT_RESET_INVALID',
            422,
        );

        $this->postJson('/api/client/v1/auth/password/reset', [
            'token' => $second, 'password' => 'a-brand-new-password-entirely',
        ])->assertNoContent();
    }
}
