<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Administration\Domain\TotpVerifier;
use Modules\Administration\Infrastructure\Eloquent\StaffUser;
use Modules\Shared\Domain\Clock;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StaffAccessTest extends TestCase
{
    private const PASSWORD = 'staff-password-long-enough';

    private string $secret;

    private function staff(string $role = 'auditor'): StaffUser
    {
        $this->secret = TotpVerifier::generateSecret();

        return StaffUser::query()->create([
            'uuid' => (string) Str::uuid7(),
            'email' => 'ops@example.test',
            'name' => 'Ops',
            'password_hash' => Hash::make(self::PASSWORD),
            'role' => $role,
            'totp_secret' => $this->secret,
            'mfa_enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    private function currentCode(): string
    {
        return (new TotpVerifier)->codeFor(
            $this->secret,
            $this->app->make(Clock::class)->now()->getTimestamp(),
        );
    }

    private function signIn(string $role = 'auditor'): string
    {
        $this->staff($role);

        return $this->postJson('/api/admin/v1/auth/sign-in', [
            'email' => 'ops@example.test',
            'password' => self::PASSWORD,
            'totp_code' => $this->currentCode(),
        ])->assertOk()->json('access_token');
    }

    #[Test]
    public function a_staff_member_signs_in_with_a_password_and_a_totp_code(): void
    {
        $token = $this->signIn();

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('admin.audit_entries', ['action' => 'admin.staff.signed_in']);
    }

    #[Test]
    public function multi_factor_authentication_is_mandatory(): void
    {
        $this->staff();

        // There is no branch that issues a staff token without a verified code.
        $this->assertProblem(
            $this->postJson('/api/admin/v1/auth/sign-in', [
                'email' => 'ops@example.test', 'password' => self::PASSWORD, 'totp_code' => '000000',
            ]),
            'AUTH_MFA_INVALID',
            401,
        );

        // Omitting it entirely is a validation failure, not a way around it.
        $this->assertProblem(
            $this->postJson('/api/admin/v1/auth/sign-in', [
                'email' => 'ops@example.test', 'password' => self::PASSWORD,
            ]),
            'VALIDATION_FAILED',
            422,
        );
    }

    #[Test]
    public function a_wrong_password_is_rejected_even_with_a_valid_code(): void
    {
        $this->staff();

        $this->assertProblem(
            $this->postJson('/api/admin/v1/auth/sign-in', [
                'email' => 'ops@example.test', 'password' => 'wrong', 'totp_code' => $this->currentCode(),
            ]),
            'AUTH_INVALID_CREDENTIALS',
            401,
        );

        $this->assertDatabaseHas('admin.audit_entries', ['action' => 'admin.staff.sign_in_failed']);
    }

    #[Test]
    public function a_viewer_token_cannot_reach_the_admin_surface(): void
    {
        $session = $this->signedInAccount();
        Cache::flush();

        // Two populations, two identity systems. Merging them would create a
        // path from the internet-facing viewer login to administrative
        // capability.
        $this->assertProblem(
            $this->getJson('/api/admin/v1/audit', $this->bearer($session['access_token'])),
            'FORBIDDEN',
            403,
        );
    }

    #[Test]
    public function a_staff_token_cannot_be_used_on_the_client_surface(): void
    {
        $token = $this->signIn();
        Cache::flush();

        $this->assertProblem(
            $this->getJson('/api/client/v1/profiles', $this->bearer($token)),
            'AUTH_TOKEN_INVALID',
            401,
        );
    }

    #[Test]
    public function the_audit_endpoint_requires_authentication(): void
    {
        $this->assertProblem($this->getJson('/api/admin/v1/audit'), 'AUTH_REQUIRED', 401);
    }

    #[Test]
    public function a_role_without_the_grant_is_refused(): void
    {
        $token = $this->signIn('content_operator');
        Cache::flush();

        // Default deny: authentication alone grants nothing, every route names
        // the roles that may reach it.
        $this->assertProblem(
            $this->getJson('/api/admin/v1/audit', $this->bearer($token)),
            'FORBIDDEN',
            403,
        );
    }

    #[Test]
    public function an_auditor_reads_the_trail_with_cursor_pagination(): void
    {
        $this->signedInAccount('viewer@example.test');
        Cache::flush();
        $token = $this->signIn();
        Cache::flush();

        $response = $this->getJson('/api/admin/v1/audit?limit=2', $this->bearer($token))->assertOk();

        $response->assertJsonStructure(['data' => [['id', 'actor_type', 'action', 'occurred_at']], 'page' => ['has_more']]);
        $this->assertLessThanOrEqual(2, count($response->json('data')));
    }

    #[Test]
    public function the_staff_record_never_exposes_its_mfa_secret_or_hash(): void
    {
        $token = $this->signIn();

        $response = $this->postJson('/api/admin/v1/auth/sign-in', [
            'email' => 'ops@example.test', 'password' => self::PASSWORD, 'totp_code' => $this->currentCode(),
        ])->assertOk();

        $body = $response->getContent() ?: '';
        $this->assertStringNotContainsString($this->secret, $body);
        $this->assertStringNotContainsString('totp_secret', $body);
        $this->assertStringNotContainsString('password_hash', $body);
    }
}
