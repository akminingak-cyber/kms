<?php

declare(strict_types=1);

namespace Modules\Profile\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $account_uuid
 * @property string $name
 * @property bool $is_primary
 * @property string|null $max_rating
 * @property string|null $pin_hash
 * @property string $locale
 */
final class Profile extends Model
{
    protected $table = 'profile.profiles';

    protected $guarded = [];

    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function hasPin(): bool
    {
        return $this->pin_hash !== null;
    }
}
