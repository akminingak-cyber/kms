<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class TerritoryRuleSet extends Model
{
    protected $table = 'rights.territory_rule_sets';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['rules' => 'array', 'created_at' => 'immutable_datetime'];
    }
}
