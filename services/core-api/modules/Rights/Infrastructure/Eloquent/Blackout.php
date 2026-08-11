<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Blackout extends Model
{
    protected $table = 'rights.blackouts';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'lifted_at' => 'immutable_datetime'];
    }
}
