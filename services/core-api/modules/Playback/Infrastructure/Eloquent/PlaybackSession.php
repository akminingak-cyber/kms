<?php

declare(strict_types=1);

namespace Modules\Playback\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PlaybackSession extends Model
{
    protected $table = 'playback.sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'last_heartbeat_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
