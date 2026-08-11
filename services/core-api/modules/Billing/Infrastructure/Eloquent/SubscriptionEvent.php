<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionEvent extends Model
{
    protected $table = 'billing.subscription_events';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
