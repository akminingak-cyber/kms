<?php

declare(strict_types=1);

namespace Modules\Device\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $account_uuid
 * @property string $device_class
 * @property string $name
 * @property string|null $fingerprint_hash
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property Carbon|null $removed_at
 */
final class Device extends Model
{
    protected $table = 'device.devices';

    protected $guarded = [];

    protected $hidden = ['fingerprint_hash'];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
            'removed_at' => 'immutable_datetime',
        ];
    }
}
