<?php

declare(strict_types=1);

namespace Modules\Rights\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class Agreement extends Model
{
    protected $table = 'rights.agreements';

    protected $guarded = [];
}
