<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One schema per bounded context (ADR-0003).
 *
 * This runs before every module migration, because a module's tables cannot be
 * created until its schema exists. Only the contexts implemented in Phase 1 are
 * created here; later phases add their own, one schema at a time.
 *
 * Framework infrastructure (cache, jobs) stays in `public`: it belongs to no
 * bounded context, and putting it in one would imply an ownership that does not
 * exist.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $schemas = [
        'identity', 'profile', 'device', 'admin',
        'catalog', 'schedule', 'product', 'billing', 'entitlement', 'rights', 'playback',
        'media',
    ];

    public function up(): void
    {
        foreach ($this->schemas as $schema) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->schemas) as $schema) {
            DB::statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        }
    }
};
