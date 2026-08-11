<?php

declare(strict_types=1);

namespace Modules\Device\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Device\Contracts\DeviceDescriptor;
use Modules\Device\Contracts\DeviceLimitExceeded;
use Modules\Device\Contracts\DeviceRegistry;
use Modules\Device\Infrastructure\Eloquent\Device;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Infrastructure\AccountLock;

/** The adapter behind {@see DeviceRegistry}. */
final readonly class DeviceRegistryService implements DeviceRegistry
{
    public function __construct(
        private Clock $clock,
        private AuditRecorder $audit,
    ) {}

    public function registerOrTouch(
        string $accountUuid,
        string $deviceClass,
        string $name,
        ?string $fingerprint,
        ?string $platformVersion = null,
    ): DeviceDescriptor {
        $now = $this->clock->now();
        $fingerprintHash = $fingerprint !== null ? hash('sha256', $fingerprint) : null;

        return DB::transaction(function () use ($accountUuid, $deviceClass, $name, $fingerprintHash, $platformVersion, $now): DeviceDescriptor {
            // Known hardware signing in again is a touch, not a new registration.
            if ($fingerprintHash !== null) {
                $existing = Device::query()
                    ->where('account_uuid', $accountUuid)
                    ->where('fingerprint_hash', $fingerprintHash)
                    ->whereNull('removed_at')
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    $existing->forceFill([
                        'last_seen_at' => $now,
                        'platform_version' => $platformVersion ?? $existing->platform_version,
                    ])->save();

                    return $this->describe($existing);
                }
            }

            $limit = (int) config('kms.devices.default_limit');

            // Serialise the check-then-insert for this account. A row lock
            // would not stop a concurrent insert, and PostgreSQL rejects
            // FOR UPDATE alongside an aggregate.
            AccountLock::acquire($accountUuid, 'device-registration');

            $active = Device::query()
                ->where('account_uuid', $accountUuid)
                ->whereNull('removed_at')
                ->count();

            if ($active >= $limit) {
                throw new DeviceLimitExceeded($limit);
            }

            $device = Device::query()->create([
                'uuid' => (string) Str::uuid7(),
                'account_uuid' => $accountUuid,
                'device_class' => $deviceClass,
                'name' => $name,
                'fingerprint_hash' => $fingerprintHash,
                'platform_version' => $platformVersion,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            $this->audit->record(AuditEvent::byAccount(
                $accountUuid,
                'device.registered',
                'device',
                $device->uuid,
                ['device_class' => $deviceClass, 'name' => $name],
            ));

            return $this->describe($device);
        });
    }

    public function isActive(string $accountUuid, string $deviceUuid): bool
    {
        return Device::query()
            ->where('account_uuid', $accountUuid)
            ->where('uuid', $deviceUuid)
            ->whereNull('removed_at')
            ->exists();
    }

    public function remove(string $accountUuid, string $deviceUuid): void
    {
        $device = Device::query()
            ->where('account_uuid', $accountUuid)
            ->where('uuid', $deviceUuid)
            ->whereNull('removed_at')
            ->first();

        if ($device === null) {
            return;
        }

        $device->forceFill(['removed_at' => $this->clock->now()])->save();

        $this->audit->record(AuditEvent::byAccount(
            $accountUuid,
            'device.removed',
            'device',
            $deviceUuid,
            ['device_class' => $device->device_class],
        ));
    }

    /** @return list<DeviceDescriptor> */
    public function listActive(string $accountUuid): array
    {
        return Device::query()
            ->where('account_uuid', $accountUuid)
            ->whereNull('removed_at')
            ->orderBy('first_seen_at')
            ->get()
            ->map(fn (Device $d): DeviceDescriptor => $this->describe($d))
            ->all();
    }

    /**
     * How long the account must wait before removing another device.
     *
     * Without a cooldown the device limit is trivially cycled: remove, add,
     * remove, add — which is the sharing pattern the limit exists to bound.
     * Returns 0 when a removal is permitted now.
     */
    public function removalCooldownRemaining(string $accountUuid): int
    {
        $hours = (int) config('kms.devices.removal_cooldown_hours');

        if ($hours <= 0) {
            return 0;
        }

        /*
         * Read the model attribute rather than a SQL aggregate. An aggregate
         * returns a raw driver value that can be an empty string rather than
         * null when there is no match — and `new DateTimeImmutable('')` is
         * "now", which silently turned "no previous removal" into "a removal
         * happened this instant" and blocked every removal forever.
         */
        $latest = Device::query()
            ->where('account_uuid', $accountUuid)
            ->whereNotNull('removed_at')
            ->orderByDesc('removed_at')
            ->first();

        if ($latest === null || $latest->removed_at === null) {
            return 0;
        }

        $available = $latest->removed_at->toDateTimeImmutable()->modify("+{$hours} hours");

        return max(0, $available->getTimestamp() - $this->clock->now()->getTimestamp());
    }

    private function describe(Device $device): DeviceDescriptor
    {
        return new DeviceDescriptor(
            uuid: $device->uuid,
            deviceClass: $device->device_class,
            name: $device->name,
            firstSeenAt: $device->first_seen_at->toDateTimeImmutable(),
            lastSeenAt: $device->last_seen_at->toDateTimeImmutable(),
        );
    }
}
