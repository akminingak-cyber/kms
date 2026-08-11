<?php

declare(strict_types=1);

namespace Modules\Product\Application;

use Modules\Product\Contracts\PackageCatalog;
use Modules\Product\Infrastructure\Eloquent\Package;
use Modules\Product\Infrastructure\Eloquent\PackageChannel;
use Modules\Product\Infrastructure\Eloquent\Plan;

final class PackageCatalogService implements PackageCatalog
{
    /** @return list<string> */
    public function channelsInPackage(string $packageUuid): array
    {
        $packageId = Package::query()->where('uuid', $packageUuid)->value('id');

        if ($packageId === null) {
            return [];
        }

        return PackageChannel::query()
            ->where('package_id', $packageId)
            ->pluck('channel_uuid')
            ->all();
    }

    public function packageForPlan(string $planUuid): ?string
    {
        $plan = Plan::query()->where('uuid', $planUuid)->first();

        if ($plan === null) {
            return null;
        }

        return Package::query()->whereKey($plan->package_id)->value('uuid');
    }

    /** @return array{concurrency_limit:int, device_limit:int, max_resolution:?string, trial_days:int}|null */
    public function planLimits(string $planUuid): ?array
    {
        $plan = Plan::query()->where('uuid', $planUuid)->first();

        if ($plan === null) {
            return null;
        }

        return [
            'concurrency_limit' => (int) $plan->concurrency_limit,
            'device_limit' => (int) $plan->device_limit,
            'max_resolution' => $plan->max_resolution,
            'trial_days' => (int) $plan->trial_days,
        ];
    }

    public function planIsActive(string $planUuid): bool
    {
        return Plan::query()->where('uuid', $planUuid)->where('status', 'active')->exists();
    }
}
