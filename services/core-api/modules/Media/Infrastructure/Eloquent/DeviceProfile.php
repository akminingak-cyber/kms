<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class DeviceProfile extends Model
{
    protected $table = 'media.device_profiles';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['formats' => 'array'];
    }
}
