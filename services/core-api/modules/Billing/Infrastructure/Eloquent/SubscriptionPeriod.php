<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionPeriod extends Model
{
    protected $table = 'billing.subscription_periods';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['period_start' => 'immutable_datetime', 'period_end' => 'immutable_datetime', 'recorded_at' => 'immutable_datetime'];
    }
}
