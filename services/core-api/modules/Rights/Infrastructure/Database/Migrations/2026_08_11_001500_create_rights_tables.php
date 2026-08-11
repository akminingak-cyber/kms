<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rights, shaped by ADR-0011.
 *
 * The projection is keyed on subject × exploitation only. Territory and usage
 * rules are **interned** — identical rule sets stored once and shared — and
 * platform/monetization are bitmasks evaluated at read time.
 *
 * That is the whole point: a licensor-wide territory correction rewrites one
 * interned row rather than millions of projection rows, so recompute is
 * proportional to the change rather than to catalog size. A full cross-product
 * of all five dimensions would be hundreds of millions of rows and would make
 * projection lag worst exactly when a rights correction most needs to land.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rights.agreements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('counterparty', 200);
            $table->string('reference', 120);
            $table->timestampTz('term_start')->nullable();
            $table->timestampTz('term_end')->nullable();
            $table->string('reporting_obligation', 200)->nullable();
            $table->timestampsTz();

            $table->unique(['counterparty', 'reference']);
        });

        /*
         * Interned rule sets: immutable and content-addressed by hash. A change
         * creates a new set and repoints the affected rows, which is what
         * preserves the ability to replay a historical decision exactly.
         */
        Schema::create('rights.territory_rule_sets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->char('hash', 64)->unique();
            // Ordered include/exclude rules, evaluated as a ruleset rather than
            // a flat list: exclusions must be able to override inclusions.
            $table->jsonb('rules');
            $table->timestampTz('created_at');
        });

        Schema::create('rights.usage_rule_sets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->char('hash', 64)->unique();
            $table->string('max_resolution', 20)->nullable();
            $table->string('hdcp', 20)->nullable();
            $table->string('security_level', 20)->nullable();
            // A licensor's cap. The lower of this and the plan limit wins, and
            // unlike a plan limit it may never fail open.
            $table->unsignedSmallInteger('concurrency_cap')->nullable();
            $table->timestampTz('created_at');
        });

        Schema::create('rights.rights', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('agreement_id');
            $table->foreign('agreement_id')
                ->references('id')->on('rights.agreements')
                ->cascadeOnDelete();

            $table->string('subject_type', 20);
            $table->uuid('subject_ref');

            $table->string('exploitation', 20);
            $table->unsignedSmallInteger('monetization_mask');
            $table->unsignedSmallInteger('platform_mask');

            $table->timestampTz('window_start');
            $table->timestampTz('window_end')->nullable();

            $table->unsignedBigInteger('territory_rule_set_id');
            $table->foreign('territory_rule_set_id')
                ->references('id')->on('rights.territory_rule_sets')
                ->restrictOnDelete();

            $table->unsignedBigInteger('usage_rule_set_id');
            $table->foreign('usage_rule_set_id')
                ->references('id')->on('rights.usage_rule_sets')
                ->restrictOnDelete();

            // Effective-dated and append-only: a correction supersedes rather
            // than overwrites, because licensor disputes are about what was true
            // on a date, not what is true now.
            $table->timestampTz('superseded_at')->nullable();
            $table->timestampsTz();

            $table->index(['subject_type', 'subject_ref', 'exploitation']);
            $table->index('superseded_at');
        });

        DB::statement(
            "ALTER TABLE rights.rights ADD CONSTRAINT rights_subject_check
             CHECK (subject_type IN ('channel', 'programme', 'title', 'collection'))"
        );

        DB::statement(
            "ALTER TABLE rights.rights ADD CONSTRAINT rights_exploitation_check
             CHECK (exploitation IN ('live', 'restart', 'catchup', 'vod', 'download', 'preview'))"
        );

        /*
         * Blackouts are deliberately NOT projected. They are few, urgent, and
         * frequently applied minutes before they take effect — pushing one
         * through a projection pipeline is the single place where
         * materialisation is exactly the wrong tool.
         */
        Schema::create('rights.blackouts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('subject_type', 20);
            $table->uuid('subject_ref');

            $table->unsignedBigInteger('territory_rule_set_id')->nullable();
            $table->foreign('territory_rule_set_id')
                ->references('id')->on('rights.territory_rule_sets')
                ->restrictOnDelete();

            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('reason', 200);
            $table->timestampTz('lifted_at')->nullable();
            $table->timestampsTz();

            // The hot-path lookup: is anything blacking out this subject now?
            $table->index(['subject_ref', 'starts_at', 'ends_at']);
        });

        /*
         * The materialised projection. One row per subject × exploitation ×
         * interval; everything else is a mask or an interned reference.
         */
        Schema::create('rights.availability', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('subject_type', 20);
            $table->uuid('subject_ref');
            $table->string('exploitation', 20);

            $table->timestampTz('interval_start');
            $table->timestampTz('interval_end')->nullable();

            $table->unsignedBigInteger('territory_rule_set_id');
            $table->foreign('territory_rule_set_id')
                ->references('id')->on('rights.territory_rule_sets')
                ->restrictOnDelete();

            $table->unsignedBigInteger('usage_rule_set_id');
            $table->foreign('usage_rule_set_id')
                ->references('id')->on('rights.usage_rule_sets')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('platform_mask');
            $table->unsignedSmallInteger('monetization_mask');

            // Recorded on every decision, so a decision can be replayed against
            // the versions that produced it.
            $table->unsignedBigInteger('availability_version');
            $table->jsonb('source_rule_ids');
            $table->timestampTz('computed_at');

            $table->unique(['subject_ref', 'exploitation', 'interval_start'], 'availability_subject_unique');
            $table->index(['subject_ref', 'exploitation', 'interval_start', 'interval_end'], 'availability_lookup_idx');
        });

        // Monotonic version counter for the projection as a whole.
        Schema::create('rights.availability_versions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('reason', 120);
            $table->unsignedInteger('subjects_recomputed')->default(0);
            $table->timestampTz('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rights.availability_versions');
        Schema::dropIfExists('rights.availability');
        Schema::dropIfExists('rights.blackouts');
        Schema::dropIfExists('rights.rights');
        Schema::dropIfExists('rights.usage_rule_sets');
        Schema::dropIfExists('rights.territory_rule_sets');
        Schema::dropIfExists('rights.agreements');
    }
};
