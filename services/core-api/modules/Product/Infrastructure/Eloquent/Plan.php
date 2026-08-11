<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Plan extends Model
{
    protected $table = 'product.plans';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['device_limit' => 'integer', 'concurrency_limit' => 'integer', 'trial_days' => 'integer'];
    }
}
