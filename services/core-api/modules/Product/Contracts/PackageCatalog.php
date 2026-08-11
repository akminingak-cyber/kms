<?php

declare(strict_types=1);

namespace Modules\Product\Contracts;

/** Product's inbound port. Entitlement resolves packages to channels through it. */
interface PackageCatalog
{
    /** @return list<string> channel identifiers in the package */
    public function channelsInPackage(string $packageUuid): array;

    public function packageForPlan(string $planUuid): ?string;

    /** @return array{concurrency_limit:int, device_limit:int, max_resolution:?string, trial_days:int}|null */
    public function planLimits(string $planUuid): ?array;

    public function planIsActive(string $planUuid): bool;
}
