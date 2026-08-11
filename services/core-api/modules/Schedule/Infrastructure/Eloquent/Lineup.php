<?php

declare(strict_types=1);

namespace Modules\Schedule\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/** @property int $id @property string $uuid @property string $slug */
final class Lineup extends Model
{
    protected $table = 'schedule.lineups';

    protected $guarded = [];
}
