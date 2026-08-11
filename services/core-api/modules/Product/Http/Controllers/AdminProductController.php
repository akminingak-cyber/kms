<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Product\Infrastructure\Eloquent\Package;
use Modules\Product\Infrastructure\Eloquent\PackageChannel;
use Modules\Product\Infrastructure\Eloquent\Plan;
use Modules\Product\Infrastructure\Eloquent\Price;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

final class AdminProductController
{
    public function storePackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:60', Rule::unique(Package::class, 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'channel_ids' => ['sometimes', 'array'],
            'channel_ids.*' => ['uuid'],
        ]);

        $package = DB::transaction(function () use ($validated): Package {
            $package = Package::query()->create([
                'uuid' => (string) Str::uuid7(),
                'slug' => $validated['slug'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['channel_ids'] ?? [] as $channelUuid) {
                PackageChannel::query()->create([
                    'package_id' => $package->id,
                    'channel_uuid' => $channelUuid,
                ]);
            }

            return $package;
        });

        return new JsonResponse(['data' => ['id' => $package->uuid, 'slug' => $package->slug]], 201);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:60', Rule::unique(Plan::class, 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'package_id' => ['required', 'uuid'],
            'billing_period' => ['required', 'in:monthly,annual'],
            'trial_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'device_limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'concurrency_limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'max_resolution' => ['sometimes', 'nullable', 'string', 'max:20'],
            'price' => ['sometimes', 'array'],
            'price.amount_minor' => ['required_with:price', 'integer', 'min:0'],
            'price.currency' => ['required_with:price', 'string', 'size:3'],
            'price.territory' => ['sometimes', 'nullable', 'string', 'size:2'],
        ]);

        $packageId = Package::query()->where('uuid', $validated['package_id'])->value('id');

        if ($packageId === null) {
            throw ApiProblem::of(ErrorCode::PackageNotFound);
        }

        $plan = DB::transaction(function () use ($validated, $packageId): Plan {
            $plan = Plan::query()->create([
                'uuid' => (string) Str::uuid7(),
                'slug' => $validated['slug'],
                'name' => $validated['name'],
                'package_id' => $packageId,
                'billing_period' => $validated['billing_period'],
                'trial_days' => $validated['trial_days'] ?? 0,
                'device_limit' => $validated['device_limit'] ?? 5,
                'concurrency_limit' => $validated['concurrency_limit'] ?? 2,
                'max_resolution' => $validated['max_resolution'] ?? null,
            ]);

            if (isset($validated['price'])) {
                Price::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'plan_id' => $plan->id,
                    // Minor units plus an explicit currency. Never a float.
                    'amount_minor' => $validated['price']['amount_minor'],
                    'currency' => strtoupper($validated['price']['currency']),
                    'territory' => $validated['price']['territory'] ?? null,
                    'active_from' => now(),
                ]);
            }

            return $plan;
        });

        return new JsonResponse(['data' => ['id' => $plan->uuid, 'slug' => $plan->slug]], 201);
    }
}
