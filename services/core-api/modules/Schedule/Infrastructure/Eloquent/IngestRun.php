<?php

declare(strict_types=1);

namespace Modules\Schedule\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/** Append-only ingest history, retaining the provider's raw payload. */
final class IngestRun extends Model
{
    protected $table = 'schedule.ingest_runs';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'errors' => 'array',
            'received_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
