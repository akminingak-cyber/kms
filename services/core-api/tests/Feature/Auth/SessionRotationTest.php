<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SessionRotationTest extends TestCase
{
    #[Test]
    public function it_rotates_a_refresh_token_and_issues_a_new_pair(): void
    {
        $session = $this->signedInAccount();

        $response = $this->postJson('/api/client/v1/auth/refresh', [
            'refresh_token' => $session['refresh_token'],
        ])->assertOk();

        $this->assertNotSame($session['refresh_token'], $response->json('refresh_token'));
        $this->assertNotEmpty($response->json('access_token'));
    }

    #[Test]
    public function it_detects_reuse_and_burns_the_whole_family(): void
    {
        $session = $this->signedInAccount();

        $rotated = $this->postJson('/api/client/v1/auth/refresh', [
            'refresh_token' => $session['refresh_token'],
        ])->assertOk();

        // Presenting the already-rotated token means either theft or a broken
        // client. Both are handled the same way, because we cannot tell them
        // apart and only one of them is safe to tolerate.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => $session['refresh_token']]),
            'AUTH_REFRESH_REUSE_DETECTED',
            401,
        );

        // The legitimate client's newest token is dead too. That is the point:
        // an attacker who stole a token cannot keep the session alive by racing.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => $rotated->json('refresh_token')]),
            'AUTH_TOKEN_REVOKED',
            401,
        );

        $this->assertDatabaseHas('admin.audit_entries', [
            'action' => 'identity.session.refresh_reuse_detected',
        ]);
    }

    #[Test]
    public function it_chains_rotations_within_one_family(): void
    {
        $session = $this->signedInAccount();
        $token = $session['refresh_token'];

        for ($i = 0; $i < 3; $i++) {
            $token = $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => $token])
                ->assertOk()
                ->json('refresh_token');
        }

        $this->assertSame(1, DB::table('identity.refresh_tokens')->distinct()->count('family_id'));
        $this->assertSame(4, DB::table('identity.refresh_tokens')->count());
    }

    #[Test]
    public function it_rejects_an_unknown_refresh_token(): void
    {
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => str_repeat('x', 64)]),
            'AUTH_TOKEN_INVALID',
            401,
        );
    }

    #[Test]
    public function it_rejects_an_expired_refresh_token(): void
    {
        $session = $this->signedInAccount();

        $clock = $this->freezeTimeAt('2026-08-11T12:00:00+00:00');
        $clock->advance('+'.((int) config('kms.tokens.refresh_ttl_seconds') + 60).' seconds');

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => $session['refresh_token']]),
            'AUTH_TOKEN_EXPIRED',
            401,
        );
    }

    #[Test]
    public function logging_out_revokes_the_family(): void
    {
        $session = $this->signedInAccount();

        $this->postJson('/api/client/v1/auth/logout', ['refresh_token' => $session['refresh_token']],
            $this->bearer($session['access_token']))->assertNoContent();

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => $session['refresh_token']]),
            'AUTH_TOKEN_REVOKED',
            401,
        );
    }

    #[Test]
    public function logging_out_revokes_the_access_token_immediately(): void
    {
        $session = $this->signedInAccount();

        $this->getJson('/api/client/v1/profiles', $this->bearer($session['access_token']))->assertOk();

        $this->postJson('/api/client/v1/auth/logout', ['refresh_token' => $session['refresh_token']],
            $this->bearer($session['access_token']))->assertNoContent();

        // The access token is still cryptographically valid; the revocation set
        // is what makes sign-out take effect before its expiry.
        $this->assertProblem(
            $this->getJson('/api/client/v1/profiles', $this->bearer($session['access_token'])),
            'AUTH_TOKEN_REVOKED',
            401,
        );
    }

    #[Test]
    public function an_access_token_is_rejected_once_it_expires(): void
    {
        $session = $this->signedInAccount();

        $clock = $this->freezeTimeAt('2026-08-11T12:00:00+00:00');
        $clock->advance('+'.((int) config('kms.tokens.access_ttl_seconds') + 120).' seconds');

        $this->assertProblem(
            $this->getJson('/api/client/v1/profiles', $this->bearer($session['access_token'])),
            'AUTH_TOKEN_EXPIRED',
            401,
        );
    }

    #[Test]
    public function a_tampered_access_token_is_rejected(): void
    {
        $session = $this->signedInAccount();
        $parts = explode('.', $session['access_token']);
        $forged = $parts[0].'.'.$parts[1].'.'.strrev($parts[2]);

        $this->assertProblem(
            $this->getJson('/api/client/v1/profiles', $this->bearer($forged)),
            'AUTH_TOKEN_INVALID',
            401,
        );
    }
}
