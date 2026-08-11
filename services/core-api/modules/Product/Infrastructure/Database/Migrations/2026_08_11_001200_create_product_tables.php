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
        Schema::create('product.packages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampsTz();
        });

        DB::statement(
            "ALTER TABLE product.packages ADD CONSTRAINT packages_status_check
             CHECK (status IN ('active', 'retired'))"
        );

        // What a package contains. Cross-context reference to schedule.channels
        // by identifier, so no foreign key.
        Schema::create('product.package_channels', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('package_id');
            $table->foreign('package_id')
                ->references('id')->on('product.packages')
                ->cascadeOnDelete();

            $table->uuid('channel_uuid');
            $table->timestampsTz();

            $table->unique(['package_id', 'channel_uuid']);
            $table->index('channel_uuid');
        });

        Schema::create('product.plans', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);

            $table->unsignedBigInteger('package_id');
            $table->foreign('package_id')
                ->references('id')->on('product.packages')
                ->restrictOnDelete();

            $table->string('billing_period', 20);
            $table->unsignedSmallInteger('trial_days')->default(0);

            // Plan-level limits. The licensor's own caps live in rights usage
            // rules, and the lower of the two always wins at playback.
            $table->unsignedSmallInteger('device_limit')->default(5);
            $table->unsignedSmallInteger('concurrency_limit')->default(2);
            $table->string('max_resolution', 20)->nullable();

            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->index('status');
        });

        DB::statement(
            "ALTER TABLE product.plans ADD CONSTRAINT plans_period_check
             CHECK (billing_period IN ('monthly', 'annual'))"
        );

        DB::statement(
            "ALTER TABLE product.plans ADD CONSTRAINT plans_status_check
             CHECK (status IN ('active', 'retired'))"
        );

        /*
         * Money is stored as minor units plus an ISO 4217 currency, never as a
         * float and never as a bare integer without its currency.
         */
        Schema::create('product.prices', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('plan_id');
            $table->foreign('plan_id')
                ->references('id')->on('product.plans')
                ->cascadeOnDelete();

            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('territory', 2)->nullable();

            $table->timestampTz('active_from');
            $table->timestampTz('active_to')->nullable();
            $table->timestampsTz();

            $table->index(['plan_id', 'territory', 'active_from']);
        });

        DB::statement(
            'ALTER TABLE product.prices ADD CONSTRAINT prices_amount_check
             CHECK (amount_minor >= 0)'
        );

        // One live price per plan, currency and territory at a time.
        DB::statement(
            'CREATE UNIQUE INDEX prices_current_unique
             ON product.prices (plan_id, currency, COALESCE(territory, \'*\'))
             WHERE active_to IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product.prices');
        Schema::dropIfExists('product.plans');
        Schema::dropIfExists('product.package_channels');
        Schema::dropIfExists('product.packages');
    }
};
