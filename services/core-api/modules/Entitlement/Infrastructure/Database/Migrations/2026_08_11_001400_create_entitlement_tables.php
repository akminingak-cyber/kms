<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * A grant records not just that access exists but **why**.
         *
         * Six months later, "why can this account watch this?" must be
         * answerable from a row rather than by reasoning about billing history.
         */
        Schema::create('entitlement.grants', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->uuid('account_uuid');

            $table->string('source', 20);
            $table->uuid('source_ref')->nullable();

            $table->string('scope_type', 20);
            $table->uuid('scope_ref');

            /*
             * The plan's commercial allowance, carried on the grant.
             *
             * Entitlement cannot see plans, and rediscovering the limit on
             * every snapshot rebuild would mean a Product lookup per grant.
             * The licensor's contractual cap is separate and arrives through
             * rights usage rules; the lower of the two wins at playback.
             */
            $table->unsignedSmallInteger('concurrency_limit')->nullable();

            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoked_reason', 60)->nullable();

            $table->timestampsTz();

            $table->index(['account_uuid', 'revoked_at']);
            $table->index(['source', 'source_ref']);
            $table->index('valid_until');
        });

        DB::statement(
            "ALTER TABLE entitlement.grants ADD CONSTRAINT grants_source_check
             CHECK (source IN ('subscription', 'purchase', 'voucher', 'promotion', 'manual'))"
        );

        DB::statement(
            "ALTER TABLE entitlement.grants ADD CONSTRAINT grants_scope_check
             CHECK (scope_type IN ('package', 'channel', 'title'))"
        );

        /*
         * The materialised answer the playback hot path reads.
         *
         * Derived and cached, but explicitly materialised — never recomputed
         * from scratch per request, and never read from billing. Billing being
         * down must not stop existing subscribers watching.
         *
         * `computed_at` makes staleness measurable, which makes it alertable:
         * an unnoticed projection lag means unentitled viewing.
         */
        Schema::create('entitlement.snapshots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('account_uuid')->unique();
            $table->unsignedBigInteger('version');

            // Flattened channel identifiers the account may watch right now.
            // Flattened deliberately: resolving packages to channels on the hot
            // path would put a product lookup inside the latency budget.
            $table->jsonb('channels');
            $table->jsonb('packages');
            $table->jsonb('grant_ids');

            $table->unsignedSmallInteger('concurrency_limit')->default(1);
            $table->string('max_resolution', 20)->nullable();

            $table->timestampTz('computed_at');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();

            $table->index('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlement.snapshots');
        Schema::dropIfExists('entitlement.grants');
    }
};
