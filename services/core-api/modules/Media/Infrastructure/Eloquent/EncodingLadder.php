<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EncodingLadder extends Model
{
    protected $table = 'media.encoding_ladders';

    protected $guarded = [];

    /** @return HasMany<LadderRung, $this> */
    public function rungs(): HasMany
    {
        return $this->hasMany(LadderRung::class, 'ladder_id')->orderBy('position');
    }

    /** @return HasMany<AudioRenditionRow, $this> */
    public function audio(): HasMany
    {
        return $this->hasMany(AudioRenditionRow::class, 'ladder_id')->orderBy('label');
    }
}
