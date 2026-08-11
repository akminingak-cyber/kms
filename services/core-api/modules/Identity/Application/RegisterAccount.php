<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Identity\Domain\EmailAddress;
use Modules\Identity\Infrastructure\Eloquent\Account;
use Modules\Profile\Contracts\ProfileProvisioning;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * Registration spans two bounded contexts: an account without a profile is not
 * a usable account, so both are created in one transaction.
 *
 * Note what is imported: `Modules\Profile\Contracts\ProfileProvisioning` and
 * nothing else from Profile. That is the boundary rule working — the call site
 * does not change if Profile later moves behind the network.
 */
final readonly class RegisterAccount
{
    public function __construct(
        private Clock $clock,
        private ProfileProvisioning $profiles,
        private EmailVerificationService $verification,
        private AuditRecorder $audit,
    ) {}

    /** @return array{account:Account, profile_uuid:string, verification_token:string} */
    public function handle(EmailAddress $email, string $password, string $profileName, string $locale): array
    {
        return DB::transaction(function () use ($email, $password, $profileName, $locale): array {
            if (Account::query()->where('email', $email->value)->exists()) {
                throw ApiProblem::of(ErrorCode::AccountEmailTaken);
            }

            $now = $this->clock->now();

            $account = Account::query()->create([
                'uuid' => (string) Str::uuid7(),
                'email' => $email->value,
                // Argon2id, configured in config/hashing.php.
                'password_hash' => Hash::make($password),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $profileUuid = $this->profiles->provisionPrimary($account->uuid, $profileName, $locale);

            $token = $this->verification->issue($account);

            $this->audit->record(AuditEvent::byAccount(
                $account->uuid,
                'identity.account.registered',
                'account',
                $account->uuid,
                ['primary_profile_uuid' => $profileUuid],
            ));

            return [
                'account' => $account,
                'profile_uuid' => $profileUuid,
                'verification_token' => $token,
            ];
        });
    }
}
