<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class UsageRuleSet extends Model
{
    protected $table = 'rights.usage_rule_sets';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['concurrency_cap' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
