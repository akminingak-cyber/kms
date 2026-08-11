<?php

declare(strict_types=1);

namespace Modules\Entitlement\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Grant extends Model
{
    protected $table = 'entitlement.grants';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['valid_from' => 'immutable_datetime', 'valid_until' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }
}
