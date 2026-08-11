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
        Schema::create('device.devices', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // Cross-context reference to identity.accounts — no foreign key.
            $table->uuid('account_uuid');

            /*
             * Device class is authoritative platform data, not a display label:
             * from Phase 4 it feeds the rights and DRM decision. It is therefore
             * constrained at the database level and never taken from a client
             * claim without server-side corroboration.
             */
            $table->string('device_class', 20);
            $table->string('name', 100);
            $table->char('fingerprint_hash', 64)->nullable();
            $table->string('platform_version', 50)->nullable();

            $table->timestampTz('first_seen_at');
            $table->timestampTz('last_seen_at');
            $table->timestampTz('removed_at')->nullable();
            $table->timestampsTz();

            $table->index(['account_uuid', 'removed_at']);
            $table->index('last_seen_at');
        });

        DB::statement(
            "ALTER TABLE device.devices ADD CONSTRAINT devices_class_check
             CHECK (device_class IN ('web', 'mobile', 'tablet', 'androidtv', 'tizen', 'webos', 'stb'))"
        );

        // One live registration per physical device per account. Partial, so a
        // removed device does not block the same hardware being re-registered.
        DB::statement(
            'CREATE UNIQUE INDEX devices_account_fingerprint_unique
             ON device.devices (account_uuid, fingerprint_hash)
             WHERE fingerprint_hash IS NOT NULL AND removed_at IS NULL'
        );

        /*
         * Television activation, RFC 8628 in shape.
         *
         * Two secrets: a short `user_code` a person types on a phone, and a
         * high-entropy `device_code` the television polls with. Only the device
         * code's hash is stored — the polling secret is as sensitive as a
         * refresh token.
         */
        Schema::create('device.activation_codes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            $table->string('user_code', 16);
            $table->char('device_code_hash', 64)->unique();

            $table->string('device_class', 20);
            $table->string('name', 100);
            $table->string('status', 20)->default('pending');

            // Populated on approval. Cross-context identifiers, no foreign keys.
            $table->uuid('account_uuid')->nullable();
            $table->uuid('device_uuid')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestampTz('expires_at');
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampTz('last_polled_at')->nullable();
            $table->timestampsTz();

            $table->index('expires_at');
            $table->index(['account_uuid', 'status']);
        });

        DB::statement(
            "ALTER TABLE device.activation_codes ADD CONSTRAINT activation_status_check
             CHECK (status IN ('pending', 'approved', 'denied', 'expired', 'consumed'))"
        );

        DB::statement(
            "ALTER TABLE device.activation_codes ADD CONSTRAINT activation_class_check
             CHECK (device_class IN ('androidtv', 'tizen', 'webos', 'stb'))"
        );

        // A user code only needs to be unique while it can still be redeemed.
        DB::statement(
            "CREATE UNIQUE INDEX activation_user_code_pending_unique
             ON device.activation_codes (user_code)
             WHERE status = 'pending'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('device.activation_codes');
        Schema::dropIfExists('device.devices');
    }
};
