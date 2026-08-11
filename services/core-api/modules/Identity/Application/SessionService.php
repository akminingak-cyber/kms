<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Identity\Contracts\SessionIssuer;
use Modules\Identity\Contracts\SessionRevoker;
use Modules\Identity\Infrastructure\Eloquent\Account;
use Modules\Identity\Infrastructure\Eloquent\RefreshToken;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Infrastructure\Token\AccessTokenService;

/**
 * Issue, rotate and revoke sessions.
 *
 * The refresh token is modelled as a *family*, not as an independent secret.
 * Rotation issues a new token and marks its parent used. If a token that has
 * already been used is presented again, either it was stolen or the client is
 * broken — and the safe response to both is the same: revoke the entire family
 * and force a fresh sign-in.
 *
 * Without family tracking, a stolen refresh token is usable until it expires
 * and its theft is undetectable. With it, the legitimate client's next refresh
 * exposes the theft.
 */
final readonly class SessionService implements SessionIssuer, SessionRevoker
{
    public function __construct(
        private Clock $clock,
        private AccessTokenService $accessTokens,
        private AuditRecorder $audit,
    ) {}

    /**
     * @return array{access_token:string, refresh_token:string, expires_in:int, refresh_expires_at:DateTimeImmutable}
     */
    public function issue(Account $account, string $deviceUuid, ?string $familyId = null, ?int $parentId = null): array
    {
        $now = $this->clock->now();
        $refreshTtl = (int) config('kms.tokens.refresh_ttl_seconds');

        // The refresh token is a high-entropy opaque secret. Only its hash is
        // stored, so a database disclosure yields nothing usable.
        $plaintext = Str::random(64);

        RefreshToken::query()->create([
            'uuid' => (string) Str::uuid7(),
            'account_id' => $account->id,
            'family_id' => $familyId ?? (string) Str::uuid7(),
            'token_hash' => $this->hash($plaintext),
            'device_uuid' => $deviceUuid,
            'parent_id' => $parentId,
            'issued_at' => $now,
            'expires_at' => $now->modify("+{$refreshTtl} seconds"),
        ]);

        $access = $this->accessTokens->issue($account->uuid, $deviceUuid);

        return [
            'access_token' => $access['token'],
            'refresh_token' => $plaintext,
            'expires_in' => (int) config('kms.tokens.access_ttl_seconds'),
            'refresh_expires_at' => $now->modify("+{$refreshTtl} seconds"),
        ];
    }

    /**
     * Rotate a refresh token.
     *
     * @return array{access_token:string, refresh_token:string, expires_in:int, refresh_expires_at:DateTimeImmutable}
     *
     * @throws ApiProblem
     */
    public function rotate(string $presented): array
    {
        $now = $this->clock->now();
        $hash = $this->hash($presented);

        $outcome = DB::transaction(function () use ($hash, $now): array {
            /*
             * Locked for update: two concurrent refreshes with the same token
             * must not both succeed. Without the lock, a legitimate client
             * retrying on a flaky connection could race itself into two valid
             * families, and genuine reuse could slip through undetected.
             */
            $token = RefreshToken::query()
                ->where('token_hash', $hash)
                ->lockForUpdate()
                ->first();

            if ($token === null) {
                throw ApiProblem::of(ErrorCode::AuthTokenInvalid);
            }

            if ($token->used_at !== null) {
                /*
                 * Reuse detected.
                 *
                 * The revocation deliberately does NOT happen here. Throwing
                 * inside the transaction would roll the revocation back along
                 * with everything else — the family would stay usable and the
                 * only trace would be a 401. The detection is returned instead,
                 * and acted on after the transaction commits.
                 */
                return ['reuse' => $token];
            }

            if ($token->revoked_at !== null) {
                throw ApiProblem::of(ErrorCode::AuthTokenRevoked);
            }

            if ($token->expires_at->toDateTimeImmutable() <= $now) {
                throw ApiProblem::of(ErrorCode::AuthTokenExpired);
            }

            $account = Account::query()->find($token->account_id);

            if ($account === null || ! $account->status()->canAuthenticate()) {
                throw ApiProblem::of(ErrorCode::AuthAccountSuspended);
            }

            $token->forceFill(['used_at' => $now])->save();

            return ['tokens' => $this->issue($account, $token->device_uuid, $token->family_id, $token->id)];
        });

        if (isset($outcome['reuse'])) {
            $compromised = $outcome['reuse'];

            // Outside the transaction, so it survives the exception below.
            $this->revokeFamily($compromised->family_id, 'reuse_detected');

            $this->audit->record(AuditEvent::bySystem(
                'identity.session.refresh_reuse_detected',
                'account',
                $this->accountUuidFor($compromised->account_id),
                ['family_id' => $compromised->family_id, 'device_uuid' => $compromised->device_uuid],
            ));

            throw ApiProblem::of(ErrorCode::AuthRefreshReuseDetected);
        }

        return $outcome['tokens'];
    }

    /**
     * Issue a session for a device that authenticated by pairing code rather
     * than by password. The account was already authenticated when it approved
     * the code, so there is no second credential check here — approving *was*
     * the authentication.
     */
    public function issueForActivatedDevice(string $accountUuid, string $deviceUuid): array
    {
        $account = Account::query()->where('uuid', $accountUuid)->first();

        if ($account === null || ! $account->status()->canAuthenticate()) {
            throw ApiProblem::of(ErrorCode::AuthAccountSuspended);
        }

        return $this->issue($account, $deviceUuid);
    }

    /** Revokes the family the presented token belongs to. Idempotent. */
    public function revokeByToken(string $presented, string $reason = 'signed_out'): void
    {
        $token = RefreshToken::query()->where('token_hash', $this->hash($presented))->first();

        if ($token !== null) {
            $this->revokeFamily($token->family_id, $reason);
        }
    }

    /** Revokes every live session for a device. Used when a device is removed. */
    public function revokeSessionsForDevice(string $accountUuid, string $deviceUuid, string $reason): void
    {
        $accountId = Account::query()->where('uuid', $accountUuid)->value('id');

        if ($accountId === null) {
            return;
        }

        $this->revokeForDevice((int) $accountId, $deviceUuid, $reason);
    }

    public function revokeForDevice(int $accountId, string $deviceUuid, string $reason): void
    {
        RefreshToken::query()
            ->where('account_id', $accountId)
            ->where('device_uuid', $deviceUuid)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $this->clock->now(), 'revoked_reason' => $reason]);
    }

    /**
     * Revokes every live session for an account.
     *
     * Called on password reset: a reset that leaves the attacker's session
     * alive achieves nothing.
     */
    public function revokeAllForAccount(int $accountId, string $reason): void
    {
        RefreshToken::query()
            ->where('account_id', $accountId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $this->clock->now(), 'revoked_reason' => $reason]);
    }

    public function revokeFamily(string $familyId, string $reason): void
    {
        RefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $this->clock->now(), 'revoked_reason' => $reason]);
    }

    /**
     * SHA-256 rather than a password hash: the token is 64 characters of
     * high-entropy random, so there is nothing to brute-force, and lookup must
     * be a single indexed read on the authentication path.
     */
    private function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }

    private function accountUuidFor(int $accountId): ?string
    {
        return Account::query()->whereKey($accountId)->value('uuid');
    }
}
