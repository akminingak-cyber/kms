<?php

declare(strict_types=1);

namespace Modules\Device\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Device\Application\DeviceRegistryService;
use Modules\Identity\Contracts\SessionRevoker;
use Modules\Shared\Domain\Identifier;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

final class DeviceController
{
    public function __construct(
        private readonly DeviceRegistryService $devices,
        private readonly SessionRevoker $sessions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $accountUuid = (string) $request->attributes->get('kms.account_uuid');
        $currentDevice = (string) $request->attributes->get('kms.device_uuid');

        $devices = array_map(static fn ($d): array => [
            'id' => $d->uuid,
            'name' => $d->name,
            'device_class' => $d->deviceClass,
            'first_seen_at' => $d->firstSeenAt->format(DATE_RFC3339),
            'last_seen_at' => $d->lastSeenAt->format(DATE_RFC3339),
            'is_current' => $d->uuid === $currentDevice,
        ], $this->devices->listActive($accountUuid));

        return new JsonResponse([
            'data' => $devices,
            'limit' => (int) config('kms.devices.default_limit'),
        ]);
    }

    /**
     * Removing a device revokes its sessions in the same operation.
     *
     * A removed device that keeps a live refresh token is not removed in any
     * sense the account holder would recognise.
     */
    public function destroy(Request $request, string $deviceId): JsonResponse
    {
        $accountUuid = (string) $request->attributes->get('kms.account_uuid');

        if (Identifier::tryFromString($deviceId) === null) {
            throw ApiProblem::of(ErrorCode::DeviceNotFound);
        }

        if (! $this->devices->isActive($accountUuid, $deviceId)) {
            // A device belonging to another account is indistinguishable from
            // one that does not exist, so this is not an enumeration oracle.
            throw ApiProblem::of(ErrorCode::DeviceNotFound);
        }

        $cooldown = $this->devices->removalCooldownRemaining($accountUuid);

        if ($cooldown > 0) {
            throw ApiProblem::of(ErrorCode::DeviceRemovalCooldown, ['retry_after_seconds' => $cooldown]);
        }

        $this->devices->remove($accountUuid, $deviceId);
        $this->sessions->revokeSessionsForDevice($accountUuid, $deviceId, 'device_removed');

        return new JsonResponse(null, 204);
    }
}
