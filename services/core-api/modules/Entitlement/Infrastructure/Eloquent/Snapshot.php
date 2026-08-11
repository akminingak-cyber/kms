<?php

declare(strict_types=1);

namespace Modules\Entitlement\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Snapshot extends Model
{
    protected $table = 'entitlement.snapshots';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'packages' => 'array',
            'grant_ids' => 'array',
            'version' => 'integer',
            'concurrency_limit' => 'integer',
            'computed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
