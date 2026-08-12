<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The media control plane.
 *
 * These tables hold the *configuration* of the media plane — what to encode,
 * how to cut it, and where the result is addressed. They hold no media, and
 * they are not a job queue: the encoding and packaging pipeline arrives with the
 * VOD phase, and a state machine with nothing driving it would be scaffolding
 * claiming to be a feature.
 *
 * Volumes here are small and change rarely, so this is deliberately normalised
 * with real foreign keys — the opposite trade-off from the availability
 * projection or the decision log, and for the opposite reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS media');

        /*
         * The ladder is data, not code, so it can be tuned per content class
         * without a deployment — live sport needs more bits than back-catalogue
         * film at the same perceived quality
         * (docs/streaming/ingest-and-transcoding.md §3).
         */
        Schema::create('media.encoding_ladders', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->string('content_class', 30);
            $table->string('status', 20)->default('draft');
            $table->timestampsTz();

            $table->index(['content_class', 'status']);
        });

        Schema::create('media.ladder_rungs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('ladder_id')->constrained('media.encoding_ladders')->cascadeOnDelete();
            $table->string('label', 40);
            $table->unsignedSmallInteger('position');

            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->unsignedInteger('video_bitrate_kbps');
            $table->unsignedInteger('max_bitrate_kbps');

            $table->string('codec', 20);
            $table->string('encoder', 40);
            $table->string('profile', 20);
            // Level × 10, so 4.0 is 40 — an integer, because levels are a fixed
            // enumeration and storing 3.1 as a float invites 3.0999999.
            $table->unsignedSmallInteger('level');
            $table->unsignedInteger('frame_rate_numerator');
            $table->unsignedInteger('frame_rate_denominator');
            // Set only for codecs whose RFC 6381 string this platform does not
            // derive; null means "derive it".
            $table->string('codec_string', 60)->nullable();

            $table->timestampsTz();

            // The label is a path segment on the origin, so two rungs sharing
            // one would write over each other's segments.
            $table->unique(['ladder_id', 'label']);
            $table->unique(['ladder_id', 'position']);
        });

        Schema::create('media.audio_renditions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('ladder_id')->constrained('media.encoding_ladders')->cascadeOnDelete();
            $table->string('label', 40);
            $table->string('language', 12);
            $table->string('role', 20);
            $table->string('codec', 20);
            $table->unsignedInteger('bitrate_kbps');
            $table->unsignedSmallInteger('channels');
            $table->unsignedInteger('sample_rate_hz');
            $table->unsignedSmallInteger('source_stream_index');
            $table->boolean('is_default')->default(false);
            $table->string('codec_string', 60)->nullable();
            $table->timestampsTz();

            $table->unique(['ladder_id', 'label']);
        });

        Schema::create('media.packaging_profiles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->string('container', 10);
            $table->unsignedInteger('segment_duration_ms');
            $table->unsignedInteger('gop_duration_ms');
            $table->unsignedInteger('timescale');
            $table->unsignedSmallInteger('playlist_window_segments');
            $table->unsignedInteger('time_shift_buffer_seconds');
            $table->unsignedInteger('suggested_presentation_delay_ms');
            /*
             * Null until Phase 6, and null means unencrypted — not "encrypted
             * with something we have not built". A packaging profile that
             * claimed cbcs here would produce manifests advertising protection
             * that is not applied.
             */
            $table->string('encryption_scheme', 20)->nullable();
            $table->timestampsTz();
        });

        /*
         * A publication binds a subject — today a channel, later a VOD asset —
         * to the ladder and packaging profile it is encoded with, and fixes its
         * address on the origin.
         *
         * `subject_ref` intentionally carries no foreign key: the channel lives
         * in the `schedule` schema and no foreign key crosses a schema boundary
         * (CLAUDE.md §5). Referential integrity across contexts is maintained by
         * events and by reconciliation, not by the database.
         */
        Schema::create('media.publications', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            $table->string('subject_type', 20);
            $table->uuid('subject_ref');

            $table->foreignId('ladder_id')->constrained('media.encoding_ladders')->restrictOnDelete();
            $table->foreignId('packaging_profile_id')->constrained('media.packaging_profiles')->restrictOnDelete();

            $table->string('origin_prefix', 120)->unique();
            $table->string('status', 20)->default('draft');
            // The instant the presentation timeline starts, which is what a
            // DASH player converts wall-clock time into a segment number with.
            // Wrong by a second and every player starts a second off the edge.
            $table->timestampTz('available_from')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('retired_at')->nullable();
            $table->unsignedInteger('revision')->default(0);

            $table->timestampsTz();

            $table->index(['subject_type', 'subject_ref', 'status']);
        });

        /*
         * One row per generated manifest: publication × delivery policy ×
         * format. The content hash makes a regeneration that changes nothing
         * visible as such, which matters because a manifest change is a client
         * contract change and gets reviewed like one.
         */
        Schema::create('media.publication_manifests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('publication_id')->constrained('media.publications')->cascadeOnDelete();
            $table->string('policy', 20);
            $table->string('format', 10);
            $table->string('storage_path', 255);
            $table->char('content_hash', 64);
            $table->unsignedInteger('byte_size');
            $table->unsignedInteger('revision');
            $table->timestampTz('generated_at');
            $table->timestampsTz();

            $table->unique(['publication_id', 'policy', 'format']);
        });

        /*
         * Device class → what we actually offer it.
         *
         * Data rather than code because it is corrected as device-lab results
         * arrive, and those results are not available from here: an older
         * television that cannot play CMAF fMP4 under HLS needs a different
         * segment set entirely, and which televisions those are is an empirical
         * question (docs/streaming/packaging-and-delivery.md §1).
         *
         * A device class with no row is offered the platform default rather
         * than nothing — this table narrows, it does not gate. Gating lives in
         * rights and entitlement, where it can be audited.
         */
        Schema::create('media.device_profiles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('device_class', 20)->unique();
            $table->string('container', 10);
            $table->jsonb('formats');
            $table->unsignedSmallInteger('max_height')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media.device_profiles');
        Schema::dropIfExists('media.publication_manifests');
        Schema::dropIfExists('media.publications');
        Schema::dropIfExists('media.packaging_profiles');
        Schema::dropIfExists('media.audio_renditions');
        Schema::dropIfExists('media.ladder_rungs');
        Schema::dropIfExists('media.encoding_ladders');
    }
};
