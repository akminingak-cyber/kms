<?php

declare(strict_types=1);

namespace Modules\Administration\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only, partitioned by month.
 *
 * There is deliberately no update or delete path: the audited population must
 * not be able to alter the record of what they did.
 *
 * @property string $uuid
 * @property string $actor_type
 * @property string|null $actor_id
 * @property string $action
 */
final class AuditEntry extends Model
{
    protected $table = 'admin.audit_entries';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
