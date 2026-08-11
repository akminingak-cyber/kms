<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $family_id
 * @property string $token_hash
 * @property string $device_uuid
 * @property int|null $parent_id
 * @property Carbon $issued_at
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $revoked_at
 * @property string|null $revoked_reason
 */
final class RefreshToken extends Model
{
    protected $table = 'identity.refresh_tokens';

    protected $guarded = [];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function isUsable(\DateTimeImmutable $now): bool
    {
        return $this->used_at === null
            && $this->revoked_at === null
            && $this->expires_at->toDateTimeImmutable() > $now;
    }
}
