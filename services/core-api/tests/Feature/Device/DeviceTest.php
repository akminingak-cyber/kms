<?php

declare(strict_types=1);

namespace Tests\Feature\Device;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DeviceTest extends TestCase
{
    #[Test]
    public function it_lists_the_accounts_devices_and_marks_the_current_one(): void
    {
        $session = $this->signedInAccount();

        $response = $this->getJson('/api/client/v1/devices', $this->bearer($session['access_token']))->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $session['device_id'])
            ->assertJsonPath('data.0.is_current', true);
    }

    #[Test]
    public function removing_a_device_revokes_its_sessions_in_the_same_operation(): void
    {
        $session = $this->signedInAccount();
        Cache::flush();

        // Sign in a second device so the current session survives the removal.
        $second = $this->postJson('/api/client/v1/auth/login', [
            'email' => $session['email'],
            'password' => $session['password'],
            'device_class' => 'mobile',
            'device_name' => 'Phone',
            'device_fingerprint' => 'phone-1',
        ])->assertOk();

        Cache::flush();

        $this->deleteJson('/api/client/v1/devices/'.$second->json('device_id'), [],
            $this->bearer($session['access_token']))->assertNoContent();

        // A removed device that keeps a live refresh token is not removed in
        // any sense the account holder would recognise.
        $this->assertProblem(
            $this->postJson('/api/client/v1/auth/refresh', ['refresh_token' => $second->json('refresh_token')]),
            'AUTH_TOKEN_REVOKED',
            401,
        );

        // Its access token stops working too, because the device is gone.
        $this->assertProblem(
            $this->getJson('/api/client/v1/profiles', $this->bearer($second->json('access_token'))),
            'DEVICE_NOT_REGISTERED',
            403,
        );
    }

    #[Test]
    public function a_removal_cooldown_stops_the_device_limit_being_cycled(): void
    {
        $session = $this->signedInAccount();
        Cache::flush();

        $second = $this->postJson('/api/client/v1/auth/login', [
            'email' => $session['email'], 'password' => $session['password'],
            'device_class' => 'mobile', 'device_name' => 'Phone', 'device_fingerprint' => 'phone-1',
        ])->assertOk();

        Cache::flush();

        $third = $this->postJson('/api/client/v1/auth/login', [
            'email' => $session['email'], 'password' => $session['password'],
            'device_class' => 'tablet', 'device_name' => 'Tablet', 'device_fingerprint' => 'tablet-1',
        ])->assertOk();

        Cache::flush();

        $this->deleteJson('/api/client/v1/devices/'.$second->json('device_id'), [],
            $this->bearer($session['access_token']))->assertNoContent();

        // Remove, add, remove, add is the sharing pattern the device limit
        // exists to bound, so a second removal has to wait.
        $this->assertProblem(
            $this->deleteJson('/api/client/v1/devices/'.$third->json('device_id'), [],
                $this->bearer($session['access_token'])),
            'DEVICE_REMOVAL_COOLDOWN',
            409,
        );
    }

    #[Test]
    public function the_cooldown_expires(): void
    {
        $session = $this->signedInAccount();
        Cache::flush();

        $second = $this->postJson('/api/client/v1/auth/login', [
            'email' => $session['email'], 'password' => $session['password'],
            'device_class' => 'mobile', 'device_name' => 'Phone', 'device_fingerprint' => 'phone-1',
        ])->assertOk();

        Cache::flush();

        $third = $this->postJson('/api/client/v1/auth/login', [
            'email' => $session['email'], 'password' => $session['password'],
            'device_class' => 'tablet', 'device_name' => 'Tablet', 'device_fingerprint' => 'tablet-1',
        ])->assertOk();

        Cache::flush();

        $this->deleteJson('/api/client/v1/devices/'.$second->json('device_id'), [],
            $this->bearer($session['access_token']))->assertNoContent();

        // Past the cooldown. Re-freeze at an absolute instant rather than
        // mutating the bound clock, so every later resolution sees it.
        $this->freezeTimeAt('2026-08-12T13:00:00+00:00');

        // The access token expired while we waited — which is the point of a
        // short access-token lifetime. Refresh, as a real client would.
        Cache::flush();
        $refreshed = $this->postJson('/api/client/v1/auth/refresh', [
            'refresh_token' => $session['refresh_token'],
        ])->assertOk();

        Cache::flush();

        $this->deleteJson('/api/client/v1/devices/'.$third->json('device_id'), [],
            $this->bearer($refreshed->json('access_token')))->assertNoContent();
    }

    #[Test]
    public function one_account_cannot_remove_another_accounts_device(): void
    {
        $mine = $this->signedInAccount('mine@example.test');
        Cache::flush();
        $theirs = $this->signedInAccount('theirs@example.test');
        Cache::flush();

        $this->assertProblem(
            $this->deleteJson('/api/client/v1/devices/'.$theirs['device_id'], [],
                $this->bearer($mine['access_token'])),
            'DEVICE_NOT_FOUND',
            404,
        );

        // And theirs still works.
        $this->getJson('/api/client/v1/profiles', $this->bearer($theirs['access_token']))->assertOk();
    }

    #[Test]
    public function an_absent_device_is_not_found(): void
    {
        $session = $this->signedInAccount();

        $this->assertProblem(
            $this->deleteJson('/api/client/v1/devices/'.Str::uuid7(), [], $this->bearer($session['access_token'])),
            'DEVICE_NOT_FOUND',
            404,
        );
    }
}
