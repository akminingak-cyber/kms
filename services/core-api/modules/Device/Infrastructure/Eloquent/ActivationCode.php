<?php

declare(strict_types=1);

namespace Modules\Device\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $user_code
 * @property string $device_code_hash
 * @property string $device_class
 * @property string $name
 * @property string $status
 * @property string|null $account_uuid
 * @property string|null $device_uuid
 * @property Carbon $expires_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $last_polled_at
 */
final class ActivationCode extends Model
{
    protected $table = 'device.activation_codes';

    protected $guarded = [];

    protected $hidden = ['device_code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'last_polled_at' => 'immutable_datetime',
        ];
    }
}
