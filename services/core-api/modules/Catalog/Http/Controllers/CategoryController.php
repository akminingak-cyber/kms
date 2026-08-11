<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Infrastructure\Eloquent\Category;

/** Categories vary by nothing but locale, so they are freely cacheable. */
final class CategoryController
{
    public function index(): JsonResponse
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();
        $byId = $categories->keyBy('id');

        return (new JsonResponse([
            'data' => $categories->map(static fn (Category $c): array => [
                'id' => $c->uuid,
                'slug' => $c->slug,
                'name' => $c->name,
                'parent_id' => $c->parent_id === null ? null : $byId->get($c->parent_id)?->uuid,
                'sort_order' => (int) $c->sort_order,
            ])->all(),
        ]))->setPublic()->setMaxAge(3600);
    }
}
