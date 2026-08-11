<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    private function register(string $email = 'verify@example.test'): void
    {
        $this->postJson('/api/client/v1/auth/register', [
            'email' => $email, 'password' => 'correct-horse-battery-staple',
            'profile_name' => 'Primary', 'accepts_terms' => true,
        ])->assertCreated();
    }

    #[Test]
    public function it_verifies_an_account(): void
    {
        $this->register();

        $this->postJson('/api/client/v1/auth/verify-email', [
            'token' => $this->issueEmailVerificationToken('verify@example.test'),
        ])->assertOk()->assertJsonPath('account.email_verified', true);

        $this->assertDatabaseHas('admin.audit_entries', ['action' => 'identity.account.email_verified']);
    }

    #[Test]
    public function verifying_twice_is_not_an_error_but_records_once(): void
    {
        $this->register();

        $this->postJson('/api/client/v1/auth/verify-email',
            ['token' => $this->issueEmailVerificationToken('verify@example.test')])->assertOk();
        $this->postJson('/api/client/v1/auth/verify-email',
            ['token' => $this->issueEmailVerificationToken('verify@example.test')])->assertOk();

        $this->assertSame(1, DB::table('admin.audit_entries')
            ->where('action', 'identity.account.email_verified')->count());
    }

    #[Test]
    public function an_unknown_token_is_rejected(): void
    {
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/verify-email', ['token' => str_repeat('z', 48)]),
            'ACCOUNT_VERIFICATION_INVALID',
            422,
        );
    }

    #[Test]
    public function an_expired_token_is_rejected(): void
    {
        $this->register();
        $token = $this->issueEmailVerificationToken('verify@example.test');

        $clock = $this->freezeTimeAt('2026-08-11T12:00:00+00:00');
        $clock->advance('+'.((int) config('kms.accounts.email_verification_ttl_seconds') + 60).' seconds');

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/verify-email', ['token' => $token]),
            'ACCOUNT_VERIFICATION_EXPIRED',
            422,
        );
    }

    #[Test]
    public function a_missing_token_is_rejected(): void
    {
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/verify-email', []),
            'ACCOUNT_VERIFICATION_INVALID',
            422,
        );
    }
}
