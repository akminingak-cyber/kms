<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Price extends Model
{
    protected $table = 'product.prices';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'active_from' => 'immutable_datetime', 'active_to' => 'immutable_datetime'];
    }
}
