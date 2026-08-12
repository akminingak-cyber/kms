<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand-only: three nullable columns, no backfill, no default that has to be
 * written to every existing row.
 *
 * `capabilities` and `quality_caps` are what a session was *planned* with. They
 * are stored rather than re-derived on heartbeat because re-deriving them would
 * silently change a live session's delivery targets when a device profile or a
 * usage rule changed — a viewer's stream would switch quality class mid-programme
 * with nothing recording why.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playback.sessions', function (Blueprint $table): void {
            $table->timestampTz('revalidated_at')->nullable();
            $table->jsonb('capabilities')->nullable();
            $table->jsonb('quality_caps')->nullable();
        });

        Schema::table('playback.sessions', function (Blueprint $table): void {
            // The sweep looks for live sessions whose revalidation is overdue;
            // without this it scans every session ever recorded.
            $table->index(['ended_at', 'revalidated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('playback.sessions', function (Blueprint $table): void {
            $table->dropIndex(['ended_at', 'revalidated_at']);
            $table->dropColumn(['revalidated_at', 'capabilities', 'quality_caps']);
        });
    }
};
