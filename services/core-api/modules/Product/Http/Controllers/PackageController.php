<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Product\Infrastructure\Eloquent\Package;
use Modules\Product\Infrastructure\Eloquent\PackageChannel;
use Modules\Product\Infrastructure\Eloquent\Plan;
use Modules\Product\Infrastructure\Eloquent\Price;

/**
 * What is for sale.
 *
 * Publicly cacheable: identical for every caller in a territory and currency.
 * Money is an object with an explicit currency, never a bare number — a bare
 * `9.99` invites float arithmetic on the client and rounding disputes at the
 * till.
 */
final class PackageController
{
    public function index(): JsonResponse
    {
        $packages = Package::query()->where('status', 'active')->orderBy('name')->get();
        $channelsByPackage = PackageChannel::query()
            ->whereIn('package_id', $packages->pluck('id'))
            ->get()
            ->groupBy('package_id');

        return (new JsonResponse([
            'data' => $packages->map(static fn (Package $p): array => [
                'id' => $p->uuid,
                'slug' => $p->slug,
                'name' => $p->name,
                'description' => $p->description,
                'channel_ids' => ($channelsByPackage->get($p->id) ?? collect())->pluck('channel_uuid')->values()->all(),
            ])->all(),
        ]))->setPublic()->setMaxAge(300);
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::query()->where('status', 'active')->orderBy('name')->get();
        $packages = Package::query()->whereIn('id', $plans->pluck('package_id'))->get()->keyBy('id');

        $prices = Price::query()
            ->whereIn('plan_id', $plans->pluck('id'))
            ->whereNull('active_to')
            ->get()
            ->groupBy('plan_id');

        return (new JsonResponse([
            'data' => $plans->map(static fn (Plan $plan): array => [
                'id' => $plan->uuid,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'package_id' => $packages->get($plan->package_id)?->uuid,
                'billing_period' => $plan->billing_period,
                'trial_days' => (int) $plan->trial_days,
                'device_limit' => (int) $plan->device_limit,
                'concurrency_limit' => (int) $plan->concurrency_limit,
                'max_resolution' => $plan->max_resolution,
                'prices' => ($prices->get($plan->id) ?? collect())->map(static fn (Price $price): array => [
                    'amount_minor' => (int) $price->amount_minor,
                    'currency' => $price->currency,
                    'territory' => $price->territory,
                ])->values()->all(),
            ])->all(),
        ]))->setPublic()->setMaxAge(300);
    }
}
