<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PackagingProfileRow extends Model
{
    protected $table = 'media.packaging_profiles';

    protected $guarded = [];
}
