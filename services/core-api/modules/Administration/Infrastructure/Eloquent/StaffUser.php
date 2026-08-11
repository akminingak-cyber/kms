<?php

declare(strict_types=1);

namespace Modules\Administration\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $email
 * @property string $name
 * @property string $password_hash
 * @property string $role
 * @property string $totp_secret
 * @property string $status
 */
final class StaffUser extends Model
{
    protected $table = 'admin.staff_users';

    protected $guarded = [];

    /** Neither the password hash nor the MFA secret is ever serialised. */
    protected $hidden = ['password_hash', 'totp_secret'];

    protected function casts(): array
    {
        return [
            'mfa_enrolled_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            // Encrypted at rest: a database disclosure must not hand over the
            // second factor along with the first.
            'totp_secret' => 'encrypted',
        ];
    }
}
