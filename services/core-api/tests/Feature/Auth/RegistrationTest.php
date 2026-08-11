<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    private const VALID = [
        'email' => 'new@example.test',
        'password' => 'correct-horse-battery-staple',
        'profile_name' => 'Primary',
        'accepts_terms' => true,
    ];

    #[Test]
    public function it_registers_an_account_with_a_primary_profile_in_one_transaction(): void
    {
        $response = $this->postJson('/api/client/v1/auth/register', self::VALID);

        $response->assertCreated()
            ->assertJsonPath('account.email', 'new@example.test')
            ->assertJsonPath('account.email_verified', false);

        $accountUuid = $response->json('account.id');

        // The profile exists because both were created together. An account
        // with no viewing subject is not a usable account.
        $this->assertSame(1, DB::table('profile.profiles')
            ->where('account_uuid', $accountUuid)
            ->where('is_primary', true)
            ->count());
    }

    #[Test]
    public function it_never_returns_the_password_hash(): void
    {
        $response = $this->postJson('/api/client/v1/auth/register', self::VALID);

        $this->assertStringNotContainsString('password', strtolower($response->getContent() ?: ''));
    }

    #[Test]
    public function it_rejects_a_duplicate_email_address(): void
    {
        $this->postJson('/api/client/v1/auth/register', self::VALID)->assertCreated();

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/register', self::VALID),
            'ACCOUNT_EMAIL_TAKEN',
            409,
        );
    }

    #[Test]
    public function it_treats_email_addresses_case_insensitively(): void
    {
        $this->postJson('/api/client/v1/auth/register', self::VALID)->assertCreated();

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/register', [...self::VALID, 'email' => 'NEW@Example.TEST']),
            'ACCOUNT_EMAIL_TAKEN',
            409,
        );
    }

    #[Test]
    public function it_rolls_back_completely_when_profile_provisioning_fails(): void
    {
        // A name longer than the column is a database-level failure inside the
        // same transaction as the account insert. Neither row may survive.
        $this->postJson('/api/client/v1/auth/register', [
            ...self::VALID,
            'profile_name' => str_repeat('a', 50),
        ])->assertCreated();

        $before = DB::table('identity.accounts')->count();

        $this->postJson('/api/client/v1/auth/register', [
            ...self::VALID,
            'email' => 'second@example.test',
            'profile_name' => str_repeat('b', 51),
        ])->assertStatus(422);

        $this->assertSame($before, DB::table('identity.accounts')->count());
    }

    #[Test]
    public function it_rejects_a_password_below_the_minimum_length(): void
    {
        $response = $this->postJson('/api/client/v1/auth/register', [...self::VALID, 'password' => 'short']);

        $this->assertProblem($response, 'VALIDATION_FAILED', 422);
        $response->assertJsonPath('meta.errors.0.field', 'password');
    }

    #[Test]
    public function it_rejects_a_malformed_email_address(): void
    {
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/register', [...self::VALID, 'email' => 'not-an-email']),
            'VALIDATION_FAILED',
            422,
        );
    }

    #[Test]
    public function it_reports_every_validation_failure_not_just_the_first(): void
    {
        $response = $this->postJson('/api/client/v1/auth/register', [
            'email' => 'bad', 'password' => 'x', 'profile_name' => '', 'accepts_terms' => false,
        ]);

        $this->assertProblem($response, 'VALIDATION_FAILED', 422);
        $this->assertGreaterThanOrEqual(3, count($response->json('meta.errors')));
    }

    #[Test]
    public function it_records_the_registration_in_the_audit_trail(): void
    {
        $response = $this->postJson('/api/client/v1/auth/register', self::VALID);

        $this->assertDatabaseHas('admin.audit_entries', [
            'action' => 'identity.account.registered',
            'actor_id' => $response->json('account.id'),
            'actor_type' => 'account',
        ]);
    }
}
