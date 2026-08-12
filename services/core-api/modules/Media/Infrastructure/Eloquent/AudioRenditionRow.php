<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Named for the table rather than the domain concept, because `AudioRendition`
 * is the domain value object and having two of those in one module reads as a
 * mistake at every call site.
 */
final class AudioRenditionRow extends Model
{
    protected $table = 'media.audio_renditions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }
}
