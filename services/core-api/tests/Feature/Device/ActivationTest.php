<?php

declare(strict_types=1);

namespace Tests\Feature\Device;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Television activation, end to end.
 *
 * A Phase 1 exit criterion: the hardest authentication interaction in the
 * platform, proven before any TV application exists.
 */
final class ActivationTest extends TestCase
{
    private function start(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/client/v1/auth/device/authorize', array_merge([
            'device_class' => 'tizen',
            'device_name' => 'Living Room TV',
        ], $overrides));
    }

    #[Test]
    public function a_television_pairs_with_an_authenticated_account(): void
    {
        // 1. The television asks for a code.
        $start = $this->start()->assertCreated();
        $userCode = $start->json('user_code');
        $deviceCode = $start->json('device_code');

        $this->assertNotSame($userCode, $deviceCode);
        $this->assertSame(8, strlen($userCode));

        // 2. It polls, and is told to wait.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/device/token', ['device_code' => $deviceCode]),
            'ACTIVATION_PENDING',
            428,
        );

        // 3. The person approves it from a device with a keyboard.
        $session = $this->signedInAccount();
        Cache::flush();

        $approval = $this->postJson('/api/client/v1/auth/device/approve', ['user_code' => $userCode],
            $this->bearer($session['access_token']))->assertOk();

        // The approval names the device, so the account holder sees what they
        // are authorising rather than approving a blank.
        $approval->assertJsonPath('device.name', 'Living Room TV')
            ->assertJsonPath('device.device_class', 'tizen');

        // 4. The television polls again and receives a real session.
        $tokens = $this->postJson('/api/client/v1/auth/device/token', ['device_code' => $deviceCode])->assertOk();

        $tokens->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'device_id']);

        // 5. That session works like any other.
        $this->getJson('/api/client/v1/profiles', $this->bearer($tokens->json('access_token')))->assertOk();
    }

    #[Test]
    public function a_device_code_can_only_be_redeemed_once(): void
    {
        $start = $this->start()->assertCreated();
        $session = $this->signedInAccount();
        Cache::flush();

        $this->postJson('/api/client/v1/auth/device/approve', ['user_code' => $start->json('user_code')],
            $this->bearer($session['access_token']))->assertOk();

        $this->postJson('/api/client/v1/auth/device/token', ['device_code' => $start->json('device_code')])->assertOk();

        // A second redemption would hand a second party the same session.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/device/token', ['device_code' => $start->json('device_code')]),
            'ACTIVATION_ALREADY_USED',
            409,
        );
    }

    #[Test]
    public function the_short_user_code_cannot_be_used_to_collect_tokens(): void
    {
        $start = $this->start()->assertCreated();

        // The user code is low entropy by necessity — it is read off a screen.
        // Polling with it must not work, or guessing an eight-character code
        // would collect somebody else's session.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/device/token', ['device_code' => $start->json('user_code')]),
            'ACTIVATION_INVALID_CODE',
            422,
        );
    }

    #[Test]
    public function an_expired_code_cannot_be_approved(): void
    {
        $start = $this->start()->assertCreated();
        $session = $this->signedInAccount();
        Cache::flush();

        $clock = $this->freezeTimeAt('2026-08-11T12:00:00+00:00');
        $clock->advance('+'.((int) config('kms.activation.user_code_ttl_seconds') + 60).' seconds');

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/device/approve', ['user_code' => $start->json('user_code')],
                $this->bearer($session['access_token'])),
            'ACTIVATION_EXPIRED',
            422,
        );
    }

    #[Test]
    public function an_unknown_user_code_is_rejected(): void
    {
        $session = $this->signedInAccount();
        Cache::flush();

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/device/approve', ['user_code' => 'AAAAAAAA'],
                $this->bearer($session['access_token'])),
            'ACTIVATION_INVALID_CODE',
            422,
        );
    }

    #[Test]
    public function approval_requires_authentication(): void
    {
        $start = $this->start()->assertCreated();

        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/device/approve', ['user_code' => $start->json('user_code')]),
            'AUTH_REQUIRED',
            401,
        );
    }

    #[Test]
    public function it_refuses_a_non_television_device_class(): void
    {
        // The pairing flow exists because televisions have no keyboard. A web
        // browser has one and must use the password endpoint.
        $this->assertProblem($this->start(['device_class' => 'web']), 'VALIDATION_FAILED', 422);
    }

    #[Test]
    public function it_stores_only_the_hash_of_the_device_code(): void
    {
        $start = $this->start()->assertCreated();

        $this->assertDatabaseMissing('device.activation_codes', [
            'device_code_hash' => $start->json('device_code'),
        ]);

        $this->assertDatabaseHas('device.activation_codes', [
            'device_code_hash' => hash('sha256', $start->json('device_code')),
        ]);
    }

    #[Test]
    public function approving_records_an_audit_entry(): void
    {
        $start = $this->start()->assertCreated();
        $session = $this->signedInAccount();
        Cache::flush();

        $this->postJson('/api/client/v1/auth/device/approve', ['user_code' => $start->json('user_code')],
            $this->bearer($session['access_token']))->assertOk();

        $this->assertDatabaseHas('admin.audit_entries', ['action' => 'device.activation_approved']);
    }

    #[Test]
    public function two_pending_codes_never_collide(): void
    {
        $codes = [];

        for ($i = 0; $i < 20; $i++) {
            $codes[] = $this->start(['device_name' => 'TV '.$i])->assertCreated()->json('user_code');
        }

        $this->assertCount(20, array_unique($codes));
        $this->assertSame(20, DB::table('device.activation_codes')->where('status', 'pending')->count());
    }
}
