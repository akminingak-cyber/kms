<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class LadderRung extends Model
{
    protected $table = 'media.ladder_rungs';

    protected $guarded = [];
}
