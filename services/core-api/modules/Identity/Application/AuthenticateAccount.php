<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Device\Contracts\DeviceLimitExceeded;
use Modules\Device\Contracts\DeviceRegistry;
use Modules\Identity\Domain\EmailAddress;
use Modules\Identity\Infrastructure\Eloquent\Account;
use Modules\Identity\Infrastructure\Eloquent\LoginAttempt;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * Password sign-in, binding the resulting session to a device.
 *
 * Two controls beyond the password check itself:
 *
 *  - Every attempt is recorded, successful or not. The failures are the
 *    detection surface for credential stuffing (threat T1); without them an
 *    attack is invisible.
 *  - Account-level lockout backs up the rate limiter. A distributed attacker
 *    rotating source addresses defeats a per-IP limit; the account's own
 *    counter does not care where the attempt came from.
 */
final readonly class AuthenticateAccount
{
    public function __construct(
        private Clock $clock,
        private SessionService $sessions,
        private DeviceRegistry $devices,
        private AuditRecorder $audit,
    ) {}

    /**
     * @return array{account:Account, device_uuid:string, tokens:array<string,mixed>}
     *
     * @throws ApiProblem
     */
    public function handle(
        EmailAddress $email,
        string $password,
        string $deviceClass,
        string $deviceName,
        ?string $fingerprint,
        ?string $ip,
        ?string $userAgent,
    ): array {
        $now = $this->clock->now();
        $account = Account::query()->where('email', $email->value)->first();

        if ($account === null) {
            /*
             * Hash anyway. Returning immediately on an unknown email makes the
             * response measurably faster than for a known one, which turns the
             * login endpoint into an account-enumeration oracle.
             */
            Hash::make($password);
            $this->recordAttempt($email->value, null, $ip, $userAgent, false, 'unknown_account');

            throw ApiProblem::of(ErrorCode::AuthInvalidCredentials);
        }

        if ($account->isLocked($now)) {
            $this->recordAttempt($email->value, $account->id, $ip, $userAgent, false, 'locked');

            throw ApiProblem::of(ErrorCode::AuthAccountLocked, [
                'retry_after_seconds' => max(0, $account->locked_until->getTimestamp() - $now->getTimestamp()),
            ]);
        }

        if (! Hash::check($password, $account->password_hash)) {
            $this->registerFailure($account, $email->value, $ip, $userAgent);

            throw ApiProblem::of(ErrorCode::AuthInvalidCredentials);
        }

        if (! $account->status()->canAuthenticate()) {
            $this->recordAttempt($email->value, $account->id, $ip, $userAgent, false, 'not_active');

            throw ApiProblem::of(ErrorCode::AuthAccountSuspended);
        }

        if (! $account->hasVerifiedEmail()) {
            $this->recordAttempt($email->value, $account->id, $ip, $userAgent, false, 'email_unverified');

            throw ApiProblem::of(ErrorCode::AuthEmailNotVerified);
        }

        // Credentials are good; bind the session to a device.
        try {
            $device = $this->devices->registerOrTouch(
                $account->uuid,
                $deviceClass,
                $deviceName,
                $fingerprint,
            );
        } catch (DeviceLimitExceeded $exceeded) {
            $this->recordAttempt($email->value, $account->id, $ip, $userAgent, false, 'device_limit');

            throw ApiProblem::of(ErrorCode::DeviceLimitReached, ['limit' => $exceeded->limit]);
        }

        $tokens = DB::transaction(function () use ($account, $device, $now): array {
            $account->forceFill([
                'failed_login_count' => 0,
                'locked_until' => null,
                'last_login_at' => $now,
            ])->save();

            return $this->sessions->issue($account, $device->uuid);
        });

        $this->recordAttempt($email->value, $account->id, $ip, $userAgent, true, null);

        $this->audit->record(AuditEvent::byAccount(
            $account->uuid,
            'identity.session.started',
            'device',
            $device->uuid,
            ['device_class' => $device->deviceClass],
        ));

        return ['account' => $account, 'device_uuid' => $device->uuid, 'tokens' => $tokens];
    }

    private function registerFailure(Account $account, string $email, ?string $ip, ?string $userAgent): void
    {
        $threshold = (int) config('kms.accounts.lockout_threshold');
        $lockSeconds = (int) config('kms.accounts.lockout_seconds');
        $now = $this->clock->now();

        $count = $account->failed_login_count + 1;
        $attributes = ['failed_login_count' => $count];

        if ($count >= $threshold) {
            $attributes['locked_until'] = $now->modify("+{$lockSeconds} seconds");
            $attributes['failed_login_count'] = 0;

            $this->audit->record(AuditEvent::bySystem(
                'identity.account.locked',
                'account',
                $account->uuid,
                ['threshold' => $threshold, 'lock_seconds' => $lockSeconds],
            ));
        }

        $account->forceFill($attributes)->save();
        $this->recordAttempt($email, $account->id, $ip, $userAgent, false, 'bad_password');
    }

    private function recordAttempt(
        string $email,
        ?int $accountId,
        ?string $ip,
        ?string $userAgent,
        bool $successful,
        ?string $reason,
    ): void {
        LoginAttempt::query()->create([
            'email' => $email,
            'account_id' => $accountId,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 512) : null,
            'successful' => $successful,
            'failure_reason' => $reason,
            'occurred_at' => $this->clock->now(),
        ]);
    }
}
