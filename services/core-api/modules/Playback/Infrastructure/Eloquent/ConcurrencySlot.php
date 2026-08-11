<?php

declare(strict_types=1);

namespace Modules\Playback\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class ConcurrencySlot extends Model
{
    protected $table = 'playback.concurrency_slots';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['acquired_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime', 'released_at' => 'immutable_datetime'];
    }
}
