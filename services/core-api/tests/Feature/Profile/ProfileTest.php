<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProfileTest extends TestCase
{
    #[Test]
    public function it_lists_the_primary_profile_created_at_registration(): void
    {
        $session = $this->signedInAccount();

        $this->getJson('/api/client/v1/profiles', $this->bearer($session['access_token']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_primary', true);
    }

    #[Test]
    public function it_creates_a_profile_with_a_parental_rating_and_pin(): void
    {
        $session = $this->signedInAccount();

        $response = $this->postJson('/api/client/v1/profiles', [
            'name' => 'Kids', 'max_rating' => 'PG', 'pin' => '1234',
        ], $this->bearer($session['access_token']))->assertCreated();

        $response->assertJsonPath('data.max_rating', 'PG')
            // Whether a PIN is set is safe to tell its owner; the PIN is not.
            ->assertJsonPath('data.has_pin', true);

        $this->assertStringNotContainsString('1234', $response->getContent() ?: '');
    }

    #[Test]
    public function it_refuses_a_duplicate_name_within_an_account(): void
    {
        $session = $this->signedInAccount();

        $this->postJson('/api/client/v1/profiles', ['name' => 'Kids'],
            $this->bearer($session['access_token']))->assertCreated();

        $response = $this->postJson('/api/client/v1/profiles', ['name' => 'KIDS'],
            $this->bearer($session['access_token']));

        // Enforced by a unique index on (account_uuid, lower(name)), not by a
        // prior SELECT, which would still race.
        $this->assertProblem($response, 'VALIDATION_FAILED', 422);
        $response->assertJsonPath('meta.errors.0.code', 'NAME_TAKEN');
    }

    #[Test]
    public function it_enforces_the_profile_limit(): void
    {
        $session = $this->signedInAccount();
        $limit = (int) config('kms.profiles.max_per_account');

        // One already exists from registration.
        for ($i = 1; $i < $limit; $i++) {
            Cache::flush();
            $this->postJson('/api/client/v1/profiles', ['name' => 'Profile '.$i],
                $this->bearer($session['access_token']))->assertCreated();
        }

        Cache::flush();

        $this->assertProblem(
            $this->postJson('/api/client/v1/profiles', ['name' => 'One too many'],
                $this->bearer($session['access_token'])),
            'PROFILE_LIMIT_REACHED',
            409,
        );
    }

    #[Test]
    public function it_refuses_to_delete_the_primary_profile(): void
    {
        $session = $this->signedInAccount();

        $primary = $this->getJson('/api/client/v1/profiles', $this->bearer($session['access_token']))
            ->json('data.0.id');

        // An account must always have a viewing subject.
        $this->assertProblem(
            $this->deleteJson("/api/client/v1/profiles/{$primary}", [], $this->bearer($session['access_token'])),
            'PROFILE_PRIMARY_IMMUTABLE',
            422,
        );
    }

    #[Test]
    public function it_updates_and_deletes_a_secondary_profile(): void
    {
        $session = $this->signedInAccount();

        $id = $this->postJson('/api/client/v1/profiles', ['name' => 'Guest'],
            $this->bearer($session['access_token']))->json('data.id');

        $this->patchJson("/api/client/v1/profiles/{$id}", ['name' => 'Visitor', 'locale' => 'fr'],
            $this->bearer($session['access_token']))
            ->assertOk()
            ->assertJsonPath('data.name', 'Visitor')
            ->assertJsonPath('data.locale', 'fr');

        $this->deleteJson("/api/client/v1/profiles/{$id}", [], $this->bearer($session['access_token']))
            ->assertNoContent();

        $this->assertProblem(
            $this->getJson("/api/client/v1/profiles/{$id}", $this->bearer($session['access_token'])),
            'PROFILE_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function one_account_cannot_reach_another_accounts_profile(): void
    {
        $mine = $this->signedInAccount('mine@example.test');
        Cache::flush();
        $theirs = $this->signedInAccount('theirs@example.test');
        Cache::flush();

        $theirProfile = $this->getJson('/api/client/v1/profiles', $this->bearer($theirs['access_token']))
            ->json('data.0.id');

        // "Not found", not "forbidden": the caller learns nothing about which
        // identifiers exist on other accounts.
        $this->assertProblem(
            $this->getJson("/api/client/v1/profiles/{$theirProfile}", $this->bearer($mine['access_token'])),
            'PROFILE_NOT_FOUND',
            404,
        );

        $this->assertProblem(
            $this->patchJson("/api/client/v1/profiles/{$theirProfile}", ['name' => 'Hijacked'],
                $this->bearer($mine['access_token'])),
            'PROFILE_NOT_FOUND',
            404,
        );

        $this->assertProblem(
            $this->deleteJson("/api/client/v1/profiles/{$theirProfile}", [], $this->bearer($mine['access_token'])),
            'PROFILE_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function a_malformed_identifier_is_not_found_rather_than_a_server_error(): void
    {
        $session = $this->signedInAccount();

        $this->assertProblem(
            $this->getJson('/api/client/v1/profiles/not-a-uuid', $this->bearer($session['access_token'])),
            'PROFILE_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function an_absent_identifier_is_not_found(): void
    {
        $session = $this->signedInAccount();

        $this->assertProblem(
            $this->getJson('/api/client/v1/profiles/'.Str::uuid7(), $this->bearer($session['access_token'])),
            'PROFILE_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function profile_endpoints_require_authentication(): void
    {
        $this->assertProblem($this->getJson('/api/client/v1/profiles'), 'AUTH_REQUIRED', 401);
        $this->assertProblem($this->postJson('/api/client/v1/profiles', ['name' => 'X']), 'AUTH_REQUIRED', 401);
    }

    #[Test]
    public function updating_records_which_fields_changed_but_never_their_values(): void
    {
        $session = $this->signedInAccount();

        $id = $this->postJson('/api/client/v1/profiles', ['name' => 'Guest'],
            $this->bearer($session['access_token']))->json('data.id');

        $this->patchJson("/api/client/v1/profiles/{$id}", ['pin' => '9876'],
            $this->bearer($session['access_token']))->assertOk();

        $entry = DB::table('admin.audit_entries')
            ->where('action', 'profile.updated')->first();

        $this->assertNotNull($entry);
        $this->assertStringContainsString('pin_hash', (string) $entry->context);
        // The audit trail is read by support staff. It records what changed,
        // never the value.
        $this->assertStringNotContainsString('9876', (string) $entry->context);
    }
}
