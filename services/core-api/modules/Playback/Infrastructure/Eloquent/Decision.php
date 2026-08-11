<?php

declare(strict_types=1);

namespace Modules\Playback\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Decision extends Model
{
    protected $table = 'playback.decisions';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'rights_rule_ids' => 'array',
            'entitlement_grant_ids' => 'array',
            'applied_restrictions' => 'array',
            'availability_version' => 'integer',
            'decided_at' => 'immutable_datetime',
        ];
    }
}
