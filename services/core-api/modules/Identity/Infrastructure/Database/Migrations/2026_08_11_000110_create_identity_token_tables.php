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
         * Refresh tokens are modelled as a *family*, not as independent tokens.
         *
         * Rotation issues a new token and marks the old one used. If a token
         * that has already been used is presented again, either it was stolen
         * or the client is broken — and in both cases the safe response is to
         * revoke the whole family. Tracking `family_id` and `parent_id` is what
         * makes that detection possible at all.
         *
         * Only the hash is stored: a database disclosure must not yield usable
         * tokens.
         */
        Schema::create('identity.refresh_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // Intra-schema foreign key: permitted, and enforced by the database.
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')
                ->references('id')->on('identity.accounts')
                ->cascadeOnDelete();

            $table->uuid('family_id');
            $table->char('token_hash', 64)->unique();

            // Cross-context reference to device.devices — identifier only, no
            // foreign key, because it crosses a schema boundary (ADR-0003).
            $table->uuid('device_uuid');

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')
                ->references('id')->on('identity.refresh_tokens')
                ->nullOnDelete();

            $table->timestampTz('issued_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoked_reason', 40)->nullable();
            $table->timestampsTz();

            $table->index(['account_id', 'revoked_at']);
            $table->index('family_id');
            $table->index('device_uuid');
            // Drives the expiry sweep.
            $table->index('expires_at');
        });

        Schema::create('identity.email_verification_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')
                ->references('id')->on('identity.accounts')
                ->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();

            $table->index(['account_id', 'consumed_at']);
            $table->index('expires_at');
        });

        Schema::create('identity.password_reset_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')
                ->references('id')->on('identity.accounts')
                ->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();

            $table->index(['account_id', 'consumed_at']);
            $table->index('expires_at');
        });

        /*
         * Append-only. Retained separately from the audit log because it is
         * written on *failed* authentication too, where there is no actor to
         * attribute the action to, and because it is the detection surface for
         * credential stuffing (threat T1).
         */
        Schema::create('identity.login_attempts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('email', 320);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->boolean('successful');
            $table->string('failure_reason', 40)->nullable();
            $table->timestampTz('occurred_at');

            $table->index(['email', 'occurred_at']);
            $table->index(['ip_address', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity.login_attempts');
        Schema::dropIfExists('identity.password_reset_tokens');
        Schema::dropIfExists('identity.email_verification_tokens');
        // Self-referencing FK: drop it before the table to keep the down path clean.
        DB::statement('ALTER TABLE IF EXISTS identity.refresh_tokens DROP CONSTRAINT IF EXISTS refresh_tokens_parent_id_foreign');
        Schema::dropIfExists('identity.refresh_tokens');
    }
};
