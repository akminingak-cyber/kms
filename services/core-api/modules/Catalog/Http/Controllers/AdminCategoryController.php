<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Catalog\Infrastructure\Eloquent\Category;
use Modules\Shared\Http\CursorList;

final class AdminCategoryController
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->orderBy('sort_order')->orderBy('id');

        return new JsonResponse(CursorList::respond($request, $query, static fn (Category $category): array => [
            'id' => $category->uuid,
            'slug' => $category->slug,
            'name' => $category->name,
            'parent_id' => $category->parent_id === null ? null : (int) $category->parent_id,
            'sort_order' => (int) $category->sort_order,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:60', Rule::unique(Category::class, 'slug')],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['sometimes', 'nullable', 'uuid'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ]);

        $parentId = null;

        if (! empty($validated['parent_id'])) {
            $parentId = Category::query()->where('uuid', $validated['parent_id'])->value('id');
        }

        $category = Category::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'parent_id' => $parentId,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return new JsonResponse(['data' => ['id' => $category->uuid, 'slug' => $category->slug]], 201);
    }
}
