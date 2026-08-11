<?php

declare(strict_types=1);

namespace Modules\Schedule\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $uuid
 * @property string $channel_uuid
 * @property string $title
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $revision
 */
final class Programme extends Model
{
    protected $table = 'schedule.programmes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'revision' => 'integer',
            'season_number' => 'integer',
            'episode_number' => 'integer',
        ];
    }
}
