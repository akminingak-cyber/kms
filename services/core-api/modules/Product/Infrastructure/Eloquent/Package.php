<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Package extends Model
{
    protected $table = 'product.packages';

    protected $guarded = [];
}
