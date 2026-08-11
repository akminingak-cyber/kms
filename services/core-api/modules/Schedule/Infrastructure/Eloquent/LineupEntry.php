<?php

declare(strict_types=1);

namespace Modules\Schedule\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/** @property int $lineup_id @property string $channel_uuid @property int $position */
final class LineupEntry extends Model
{
    protected $table = 'schedule.lineup_entries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
