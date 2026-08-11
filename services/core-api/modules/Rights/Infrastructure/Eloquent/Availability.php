<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Availability extends Model
{
    protected $table = 'rights.availability';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'interval_start' => 'immutable_datetime',
            'interval_end' => 'immutable_datetime',
            'platform_mask' => 'integer',
            'monetization_mask' => 'integer',
            'availability_version' => 'integer',
            'source_rule_ids' => 'array',
            'computed_at' => 'immutable_datetime',
        ];
    }
}
