<?php

declare(strict_types=1);

namespace Modules\Schedule\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/** @property int $id @property string $uuid @property string $slug @property string $name @property string $status */
final class Channel extends Model
{
    protected $table = 'schedule.channels';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['number' => 'integer', 'catchup_enabled' => 'boolean'];
    }
}
