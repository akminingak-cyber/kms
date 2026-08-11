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
use Modules\Identity\Infrastructure\Eloquent\PasswordResetToken;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

final readonly class PasswordResetService
{
    public function __construct(
        private Clock $clock,
        private SessionService $sessions,
        private AuditRecorder $audit,
    ) {}

    /**
     * Returns the plaintext token when the account exists, null otherwise.
     *
     * The caller responds identically either way: telling an anonymous caller
     * whether an address is registered is account enumeration, and this endpoint
     * is unauthenticated.
     */
    public function request(EmailAddress $email): ?string
    {
        $account = Account::query()->where('email', $email->value)->first();

        if ($account === null) {
            return null;
        }

        $now = $this->clock->now();
        $ttl = (int) config('kms.accounts.password_reset_ttl_seconds');
        $plaintext = Str::random(48);

        PasswordResetToken::query()
            ->where('account_id', $account->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => $now]);

        PasswordResetToken::query()->create([
            'account_id' => $account->id,
            'token_hash' => hash('sha256', $plaintext),
            'expires_at' => $now->modify("+{$ttl} seconds"),
        ]);

        $this->audit->record(AuditEvent::byAccount(
            $account->uuid,
            'identity.password.reset_requested',
            'account',
            $account->uuid,
        ));

        return $plaintext;
    }

    /** @throws ApiProblem */
    public function reset(string $plaintext, string $newPassword): void
    {
        $now = $this->clock->now();

        DB::transaction(function () use ($plaintext, $newPassword, $now): void {
            $token = PasswordResetToken::query()
                ->where('token_hash', hash('sha256', $plaintext))
                ->lockForUpdate()
                ->first();

            if ($token === null || $token->consumed_at !== null) {
                throw ApiProblem::of(ErrorCode::AccountResetInvalid);
            }

            if ($token->expires_at->toDateTimeImmutable() <= $now) {
                throw ApiProblem::of(ErrorCode::AccountResetExpired);
            }

            $account = Account::query()->lockForUpdate()->find($token->account_id);

            if ($account === null) {
                throw ApiProblem::of(ErrorCode::AccountResetInvalid);
            }

            $token->forceFill(['consumed_at' => $now])->save();

            $account->forceFill([
                'password_hash' => Hash::make($newPassword),
                'failed_login_count' => 0,
                'locked_until' => null,
            ])->save();

            /*
             * Every session is revoked, on every reset.
             *
             * A reset performed because an account was compromised, that leaves
             * the attacker's session alive, achieves nothing at all.
             */
            $this->sessions->revokeAllForAccount($account->id, 'password_reset');

            $this->audit->record(AuditEvent::byAccount(
                $account->uuid,
                'identity.password.reset_completed',
                'account',
                $account->uuid,
                ['sessions_revoked' => true],
            ));
        });
    }
}
