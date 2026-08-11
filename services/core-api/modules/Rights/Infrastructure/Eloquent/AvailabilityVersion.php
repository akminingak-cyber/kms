<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class AvailabilityVersion extends Model
{
    protected $table = 'rights.availability_versions';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['computed_at' => 'immutable_datetime', 'subjects_recomputed' => 'integer'];
    }
}
