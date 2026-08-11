<?php

declare(strict_types=1);

namespace Modules\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/** @property int $id @property string $uuid @property string $slug @property string $name */
final class Category extends Model
{
    protected $table = 'catalog.categories';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
