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
         * We are the system of record for subscription state (ADR-0009). No
         * payment provider is integrated in this phase — a provider reference
         * column exists so the adapter has somewhere to land, and is null until
         * a PSP is selected (OQ-9).
         */
        Schema::create('billing.subscriptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // Cross-context references, identifier only.
            $table->uuid('account_uuid');
            $table->uuid('plan_uuid');

            $table->string('status', 20);
            $table->timestampTz('started_at');
            $table->timestampTz('current_period_start');
            $table->timestampTz('current_period_end');
            $table->timestampTz('trial_ends_at')->nullable();

            // Cancellation is a decision, not an immediate end: the subscriber
            // keeps access until the period they paid for runs out.
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('ended_at')->nullable();

            $table->string('provider', 40)->nullable();
            $table->string('provider_ref', 120)->nullable();

            $table->timestampsTz();

            $table->index(['account_uuid', 'status']);
            $table->index('current_period_end');
        });

        DB::statement(
            "ALTER TABLE billing.subscriptions ADD CONSTRAINT subscriptions_status_check
             CHECK (status IN ('trialing', 'active', 'past_due', 'cancelled', 'expired'))"
        );

        // An account may hold only one live subscription at a time. Enforced by
        // the database rather than by application discipline, because two live
        // subscriptions would produce two overlapping entitlement grants and an
        // unanswerable billing question.
        DB::statement(
            "CREATE UNIQUE INDEX subscriptions_one_live_per_account
             ON billing.subscriptions (account_uuid)
             WHERE status IN ('trialing', 'active', 'past_due')"
        );

        /*
         * Append-only. Mutating a current-period row destroys the history that
         * proration, refunds and disputes are answered from.
         */
        Schema::create('billing.subscription_periods', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subscription_id');
            $table->foreign('subscription_id')
                ->references('id')->on('billing.subscriptions')
                ->cascadeOnDelete();

            $table->timestampTz('period_start');
            $table->timestampTz('period_end');
            $table->string('reason', 40);
            $table->timestampTz('recorded_at');

            $table->index(['subscription_id', 'period_start']);
        });

        /*
         * Every lifecycle transition, with what caused it. A subscription's
         * current status answers "what now"; this answers "how did it get here",
         * which is the question support and finance actually ask.
         */
        Schema::create('billing.subscription_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('subscription_id');
            $table->foreign('subscription_id')
                ->references('id')->on('billing.subscriptions')
                ->cascadeOnDelete();

            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('reason', 60);
            $table->jsonb('context')->nullable();
            $table->timestampTz('occurred_at');

            $table->index(['subscription_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing.subscription_events');
        Schema::dropIfExists('billing.subscription_periods');
        Schema::dropIfExists('billing.subscriptions');
    }
};
