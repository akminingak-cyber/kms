<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Identity\Infrastructure\Eloquent\Account;
use Modules\Identity\Infrastructure\Eloquent\EmailVerificationToken;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

final readonly class EmailVerificationService
{
    public function __construct(
        private Clock $clock,
        private AuditRecorder $audit,
    ) {}

    /** @return string the plaintext token, delivered by email and never stored */
    public function issue(Account $account): string
    {
        $now = $this->clock->now();
        $ttl = (int) config('kms.accounts.email_verification_ttl_seconds');
        $plaintext = Str::random(48);

        // Any outstanding token is invalidated: only the most recent link works,
        // so a link from an intercepted older email cannot be used later.
        EmailVerificationToken::query()
            ->where('account_id', $account->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => $now]);

        EmailVerificationToken::query()->create([
            'account_id' => $account->id,
            'token_hash' => hash('sha256', $plaintext),
            'expires_at' => $now->modify("+{$ttl} seconds"),
        ]);

        return $plaintext;
    }

    /** @throws ApiProblem */
    public function verify(string $plaintext): Account
    {
        $now = $this->clock->now();

        return DB::transaction(function () use ($plaintext, $now): Account {
            $token = EmailVerificationToken::query()
                ->where('token_hash', hash('sha256', $plaintext))
                ->lockForUpdate()
                ->first();

            if ($token === null || $token->consumed_at !== null) {
                throw ApiProblem::of(ErrorCode::AccountVerificationInvalid);
            }

            if ($token->expires_at->toDateTimeImmutable() <= $now) {
                throw ApiProblem::of(ErrorCode::AccountVerificationExpired);
            }

            $account = Account::query()->lockForUpdate()->find($token->account_id);

            if ($account === null) {
                throw ApiProblem::of(ErrorCode::AccountVerificationInvalid);
            }

            $token->forceFill(['consumed_at' => $now])->save();

            // Idempotent: verifying twice is not an error, it just does nothing
            // the second time.
            if (! $account->hasVerifiedEmail()) {
                $account->forceFill(['email_verified_at' => $now])->save();

                $this->audit->record(AuditEvent::byAccount(
                    $account->uuid,
                    'identity.account.email_verified',
                    'account',
                    $account->uuid,
                ));
            }

            return $account;
        });
    }
}
