<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class RightGrant extends Model
{
    protected $table = 'rights.rights';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'window_start' => 'immutable_datetime',
            'window_end' => 'immutable_datetime',
            'superseded_at' => 'immutable_datetime',
            'monetization_mask' => 'integer',
            'platform_mask' => 'integer',
        ];
    }
}
