<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PackageChannel extends Model
{
    protected $table = 'product.package_channels';

    protected $guarded = [];
}
