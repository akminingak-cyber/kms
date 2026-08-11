<?php

declare(strict_types=1);

namespace Modules\Profile\Application;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Device\Contracts\DeviceRegistry;
use Modules\Profile\Contracts\ParentalPolicy;
use Modules\Profile\Contracts\ProfileDirectory;
use Modules\Profile\Contracts\ProfileProvisioning;
use Modules\Profile\Infrastructure\Eloquent\Profile;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Infrastructure\AccountLock;

final readonly class ProfileService implements ParentalPolicy, ProfileDirectory, ProfileProvisioning
{
    public function __construct(
        private AuditRecorder $audit,
        private DeviceRegistry $devices,
    ) {}

    public function provisionPrimary(string $accountUuid, string $name, string $locale): string
    {
        $existing = Profile::query()
            ->where('account_uuid', $accountUuid)
            ->where('is_primary', true)
            ->value('uuid');

        if (is_string($existing)) {
            return $existing;
        }

        $profile = Profile::query()->create([
            'uuid' => (string) Str::uuid7(),
            'account_uuid' => $accountUuid,
            'name' => $name,
            'is_primary' => true,
            'locale' => $locale,
        ]);

        return $profile->uuid;
    }

    public function purgeForAccount(string $accountUuid): void
    {
        Profile::query()->where('account_uuid', $accountUuid)->delete();
    }

    /** @return list<Profile> */
    public function listForAccount(string $accountUuid): array
    {
        return Profile::query()
            ->where('account_uuid', $accountUuid)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @param  array{name:string, locale?:string, max_rating?:string|null, pin?:string|null}  $attributes
     *
     * @throws ApiProblem
     */
    public function create(string $accountUuid, array $attributes): Profile
    {
        return DB::transaction(function () use ($accountUuid, $attributes): Profile {
            $limit = (int) config('kms.profiles.max_per_account');

            AccountLock::acquire($accountUuid, 'profile-creation');

            $count = Profile::query()
                ->where('account_uuid', $accountUuid)
                ->count();

            if ($count >= $limit) {
                throw ApiProblem::of(ErrorCode::ProfileLimitReached, ['limit' => $limit]);
            }

            try {
                $profile = Profile::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'account_uuid' => $accountUuid,
                    'name' => $attributes['name'],
                    'is_primary' => false,
                    'locale' => $attributes['locale'] ?? 'en',
                    'max_rating' => $attributes['max_rating'] ?? null,
                    'pin_hash' => isset($attributes['pin']) && $attributes['pin'] !== null
                        ? Hash::make($attributes['pin'])
                        : null,
                ]);
            } catch (QueryException $e) {
                // The unique index on (account_uuid, lower(name)) is the
                // authority on duplicates, not a prior SELECT — checking first
                // would still race.
                if ($this->isUniqueViolation($e)) {
                    throw ApiProblem::of(ErrorCode::ValidationFailed, [
                        'errors' => [['field' => 'name', 'code' => 'NAME_TAKEN']],
                    ]);
                }

                throw $e;
            }

            $this->audit->record(AuditEvent::byAccount(
                $accountUuid,
                'profile.created',
                'profile',
                $profile->uuid,
                ['has_pin' => $profile->hasPin(), 'max_rating' => $profile->max_rating],
            ));

            return $profile;
        });
    }

    /**
     * @param  array<string,mixed>  $attributes
     *
     * @throws ApiProblem
     */
    public function update(string $accountUuid, string $profileUuid, array $attributes): Profile
    {
        $profile = $this->findOwned($accountUuid, $profileUuid);

        $changes = [];

        foreach (['name', 'locale', 'audio_language', 'subtitle_language'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $changes[$field] = $attributes[$field];
            }
        }

        if (array_key_exists('max_rating', $attributes)) {
            $changes['max_rating'] = $attributes['max_rating'];
        }

        if (array_key_exists('pin', $attributes)) {
            $changes['pin_hash'] = $attributes['pin'] === null ? null : Hash::make((string) $attributes['pin']);
        }

        if ($changes !== []) {
            $profile->forceFill($changes)->save();

            $this->audit->record(AuditEvent::byAccount(
                $accountUuid,
                'profile.updated',
                'profile',
                $profile->uuid,
                // Field names only. The audit trail is read by support staff,
                // so it records *what* changed, never the values.
                ['fields' => array_keys($changes)],
            ));
        }

        return $profile;
    }

    /** @throws ApiProblem */
    public function delete(string $accountUuid, string $profileUuid): void
    {
        $profile = $this->findOwned($accountUuid, $profileUuid);

        if ($profile->is_primary) {
            // An account must always have a viewing subject.
            throw ApiProblem::of(ErrorCode::ProfilePrimaryImmutable);
        }

        $profile->delete();

        $this->audit->record(AuditEvent::byAccount(
            $accountUuid,
            'profile.deleted',
            'profile',
            $profileUuid,
        ));
    }

    /**
     * Ownership is checked here, on every access, by scoping the query to the
     * account rather than by loading and then comparing. A missing profile and
     * another account's profile are indistinguishable to the caller, which is
     * what stops the endpoint being an enumeration oracle.
     *
     * @throws ApiProblem
     */
    public function findOwned(string $accountUuid, string $profileUuid): Profile
    {
        $profile = Profile::query()
            ->where('account_uuid', $accountUuid)
            ->where('uuid', $profileUuid)
            ->first();

        if ($profile === null) {
            throw ApiProblem::of(ErrorCode::ProfileNotFound);
        }

        return $profile;
    }

    /**
     * Ratings are ordered least to most restrictive. An unknown rating on
     * either side is treated as the most restrictive value there is: default
     * deny extends to vocabulary we do not recognise, because the alternative
     * is showing a child something because a provider used a label we had not
     * seen before.
     */
    private const RATING_ORDER = ['U' => 1, 'PG' => 2, '12' => 3, '15' => 4, '18' => 5];

    public function belongsToAccount(string $accountUuid, string $profileUuid): bool
    {
        return Profile::query()
            ->where('account_uuid', $accountUuid)
            ->where('uuid', $profileUuid)
            ->exists();
    }

    public function deviceClassFor(string $deviceUuid): ?string
    {
        return $this->devices->classOf($deviceUuid);
    }

    public function permits(string $accountUuid, string $profileUuid, ?string $ageRating): bool
    {
        $profile = Profile::query()
            ->where('account_uuid', $accountUuid)
            ->where('uuid', $profileUuid)
            ->first();

        if ($profile === null) {
            return false;
        }

        // No limit on the profile means no parental restriction.
        if ($profile->max_rating === null) {
            return true;
        }

        $limit = self::RATING_ORDER[strtoupper($profile->max_rating)] ?? 1;
        $content = $ageRating === null ? PHP_INT_MAX : (self::RATING_ORDER[strtoupper($ageRating)] ?? PHP_INT_MAX);

        return $content <= $limit;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return ($e->errorInfo[0] ?? null) === '23505';
    }
}
