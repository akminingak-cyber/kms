<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Publication extends Model
{
    protected $table = 'media.publications';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'available_from' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'retired_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<EncodingLadder, $this> */
    public function ladder(): BelongsTo
    {
        return $this->belongsTo(EncodingLadder::class, 'ladder_id');
    }

    /** @return BelongsTo<PackagingProfileRow, $this> */
    public function packaging(): BelongsTo
    {
        return $this->belongsTo(PackagingProfileRow::class, 'packaging_profile_id');
    }

    /** @return HasMany<PublicationManifest, $this> */
    public function manifests(): HasMany
    {
        return $this->hasMany(PublicationManifest::class, 'publication_id');
    }
}
