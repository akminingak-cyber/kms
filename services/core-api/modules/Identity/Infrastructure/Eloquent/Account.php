<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Identity\Domain\AccountStatus;

/**
 * @property int $id
 * @property string $uuid
 * @property string $email
 * @property string $password_hash
 * @property string $status
 * @property Carbon|null $email_verified_at
 * @property int $failed_login_count
 * @property Carbon|null $locked_until
 * @property Carbon|null $last_login_at
 */
final class Account extends Model
{
    // Schema-qualified, always. The boundary checker fails the build if a model
    // in this module points anywhere but the `identity` schema.
    protected $table = 'identity.accounts';

    protected $guarded = [];

    /** Never serialise the hash, even accidentally. */
    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'failed_login_count' => 'integer',
        ];
    }

    public function status(): AccountStatus
    {
        return AccountStatus::from($this->status);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isLocked(\DateTimeImmutable $now): bool
    {
        return $this->locked_until !== null
            && $this->locked_until->toDateTimeImmutable() > $now;
    }
}
