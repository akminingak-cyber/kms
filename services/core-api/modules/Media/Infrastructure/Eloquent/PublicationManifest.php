<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PublicationManifest extends Model
{
    protected $table = 'media.publication_manifests';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['generated_at' => 'immutable_datetime'];
    }
}
