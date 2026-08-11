<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only. Written on failure as well as success, because the failures are
 * the detection surface for credential stuffing (threat T1).
 *
 * @property string $email
 * @property int|null $account_id
 * @property bool $successful
 */
final class LoginAttempt extends Model
{
    protected $table = 'identity.login_attempts';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
