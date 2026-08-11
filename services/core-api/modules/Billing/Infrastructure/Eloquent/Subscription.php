<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Subscription extends Model
{
    protected $table = 'billing.subscriptions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'current_period_start' => 'immutable_datetime',
            'current_period_end' => 'immutable_datetime',
            'trial_ends_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }
}
