<?php

declare(strict_types=1);

namespace Modules\Device\Application;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Device\Contracts\DeviceLimitExceeded;
use Modules\Device\Infrastructure\Eloquent\ActivationCode;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * Television activation — a device authorization grant in the style of RFC 8628.
 *
 * Televisions have no usable keyboard. The person signs in on a device that has
 * one, and approves a short code shown on the screen.
 *
 * Two secrets, deliberately:
 *
 *  - `user_code` is short, so it can be read off a screen and typed. It is
 *    low-entropy, so it is only unique among *pending* codes, expires in
 *    minutes, and can only ever be redeemed by an already-authenticated caller.
 *  - `device_code` is high-entropy and is what the television polls with. It is
 *    as sensitive as a refresh token, so only its hash is stored.
 *
 * Getting that split wrong — polling with the short code — would let anyone who
 * can guess an 8-character code collect somebody else's tokens.
 */
final readonly class ActivationService
{
    public function __construct(
        private Clock $clock,
        private DeviceRegistryService $devices,
        private AuditRecorder $audit,
    ) {}

    /** @return array{user_code:string, device_code:string, expires_at:DateTimeImmutable, interval:int} */
    public function start(string $deviceClass, string $name, ?string $ip): array
    {
        $now = $this->clock->now();
        $ttl = (int) config('kms.activation.user_code_ttl_seconds');
        $deviceCode = Str::random(64);

        $userCode = $this->allocateUserCode();

        ActivationCode::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_code' => $userCode,
            'device_code_hash' => hash('sha256', $deviceCode),
            'device_class' => $deviceClass,
            'name' => $name,
            'status' => 'pending',
            'ip_address' => $ip,
            'expires_at' => $now->modify("+{$ttl} seconds"),
        ]);

        return [
            'user_code' => $userCode,
            'device_code' => $deviceCode,
            'expires_at' => $now->modify("+{$ttl} seconds"),
            'interval' => (int) config('kms.activation.poll_interval_seconds'),
        ];
    }

    /**
     * Approve a code, from an authenticated session on a device with a keyboard.
     *
     * @throws ApiProblem
     */
    public function approve(string $accountUuid, string $userCode): ActivationCode
    {
        $now = $this->clock->now();
        $normalised = strtoupper(trim(str_replace([' ', '-'], '', $userCode)));

        return DB::transaction(function () use ($accountUuid, $normalised, $now): ActivationCode {
            $code = ActivationCode::query()
                ->where('user_code', $normalised)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($code === null) {
                throw ApiProblem::of(ErrorCode::ActivationInvalidCode);
            }

            if ($code->expires_at->toDateTimeImmutable() <= $now) {
                $code->forceFill(['status' => 'expired'])->save();

                throw ApiProblem::of(ErrorCode::ActivationExpired);
            }

            // The device is created at approval time, under the approving
            // account, so the device limit is enforced by the same rule as any
            // other registration.
            try {
                $device = $this->devices->registerOrTouch(
                    $accountUuid,
                    $code->device_class,
                    $code->name,
                    null,
                );
            } catch (DeviceLimitExceeded $exceeded) {
                throw ApiProblem::of(ErrorCode::DeviceLimitReached, ['limit' => $exceeded->limit]);
            }

            $code->forceFill([
                'status' => 'approved',
                'account_uuid' => $accountUuid,
                'device_uuid' => $device->uuid,
                'approved_at' => $now,
            ])->save();

            $this->audit->record(AuditEvent::byAccount(
                $accountUuid,
                'device.activation_approved',
                'device',
                $device->uuid,
                ['device_class' => $code->device_class, 'name' => $code->name],
            ));

            return $code;
        });
    }

    /**
     * The television polls with its device code.
     *
     * Returns the approved code exactly once — it is marked consumed inside the
     * same transaction, so a device code cannot be redeemed twice even if two
     * polls arrive together.
     *
     * @throws ApiProblem
     */
    public function redeem(string $deviceCode): ActivationCode
    {
        $now = $this->clock->now();

        return DB::transaction(function () use ($deviceCode, $now): ActivationCode {
            $code = ActivationCode::query()
                ->where('device_code_hash', hash('sha256', $deviceCode))
                ->lockForUpdate()
                ->first();

            if ($code === null) {
                throw ApiProblem::of(ErrorCode::ActivationInvalidCode);
            }

            if ($code->status === 'consumed') {
                throw ApiProblem::of(ErrorCode::ActivationAlreadyUsed);
            }

            if ($code->status === 'denied') {
                throw ApiProblem::of(ErrorCode::ActivationInvalidCode);
            }

            if ($code->expires_at->toDateTimeImmutable() <= $now) {
                if ($code->status === 'pending') {
                    $code->forceFill(['status' => 'expired'])->save();
                }

                throw ApiProblem::of(ErrorCode::ActivationExpired);
            }

            if ($code->status === 'pending') {
                $code->forceFill(['last_polled_at' => $now])->save();

                throw ApiProblem::of(ErrorCode::ActivationPending, [
                    'interval' => (int) config('kms.activation.poll_interval_seconds'),
                ]);
            }

            $code->forceFill(['status' => 'consumed', 'consumed_at' => $now])->save();

            return $code;
        });
    }

    /**
     * A code is only unique among pending codes, so collisions are possible and
     * are simply retried. The alphabet excludes characters that are ambiguous
     * on a television screen at three metres.
     */
    private function allocateUserCode(): string
    {
        $alphabet = (string) config('kms.activation.user_code_alphabet');
        $length = (int) config('kms.activation.user_code_length');
        $max = strlen($alphabet) - 1;

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }

            $taken = ActivationCode::query()
                ->where('user_code', $code)
                ->where('status', 'pending')
                ->exists();

            if (! $taken) {
                return $code;
            }
        }

        throw ApiProblem::of(ErrorCode::ServiceUnavailable, detail: 'Could not allocate an activation code.');
    }
}
