<?php

declare(strict_types=1);

namespace Modules\Administration\Application;

use Illuminate\Support\Facades\Hash;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Administration\Domain\TotpVerifier;
use Modules\Administration\Infrastructure\Eloquent\StaffUser;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;
use Modules\Shared\Infrastructure\Token\AccessTokenService;

/**
 * Staff authentication.
 *
 * Separate from viewer authentication by design: separate credential store,
 * separate token audience, separate lifetime. A staff member who is also a
 * subscriber has two unrelated identities, because merging them would create a
 * path from the internet-facing viewer login to administrative capability —
 * exactly the path an attacker looks for.
 *
 * MFA is mandatory and unconditional. There is no branch here that issues a
 * token without a verified TOTP code.
 */
final readonly class StaffAuthService
{
    /** Staff sessions are short: a forgotten open tab is an unattended console. */
    private const SESSION_TTL_SECONDS = 3600;

    public function __construct(
        private Clock $clock,
        private TotpVerifier $totp,
        private AccessTokenService $tokens,
        private AuditRecorder $audit,
    ) {}

    /**
     * @return array{token:string, expires_in:int, staff:StaffUser}
     *
     * @throws ApiProblem
     */
    public function authenticate(string $email, string $password, string $totpCode): array
    {
        $staff = StaffUser::query()->where('email', strtolower(trim($email)))->first();

        if ($staff === null) {
            // Same constant-work path as the viewer login, for the same reason:
            // an early return is an enumeration oracle.
            Hash::make($password);

            throw ApiProblem::of(ErrorCode::AuthInvalidCredentials);
        }

        if (! Hash::check($password, $staff->password_hash)) {
            $this->audit->record(AuditEvent::bySystem(
                'admin.staff.sign_in_failed',
                'staff',
                $staff->uuid,
                ['reason' => 'bad_password'],
            ));

            throw ApiProblem::of(ErrorCode::AuthInvalidCredentials);
        }

        if ($staff->status !== 'active') {
            throw ApiProblem::of(ErrorCode::AuthAccountSuspended);
        }

        if (! $this->totp->verify($staff->totp_secret, $totpCode, $this->clock->now()->getTimestamp())) {
            $this->audit->record(AuditEvent::bySystem(
                'admin.staff.sign_in_failed',
                'staff',
                $staff->uuid,
                ['reason' => 'bad_mfa'],
            ));

            throw ApiProblem::of(ErrorCode::AuthMfaInvalid);
        }

        $issued = $this->tokens->issue(
            subject: $staff->uuid,
            deviceUuid: 'staff-console',
            claims: ['typ' => 'staff', 'role' => $staff->role],
            ttlSeconds: self::SESSION_TTL_SECONDS,
        );

        $staff->forceFill(['last_login_at' => $this->clock->now()])->save();

        $this->audit->record(AuditEvent::byStaff(
            $staff->uuid,
            'admin.staff.signed_in',
            'staff',
            $staff->uuid,
            ['role' => $staff->role],
        ));

        return ['token' => $issued['token'], 'expires_in' => self::SESSION_TTL_SECONDS, 'staff' => $staff];
    }
}
