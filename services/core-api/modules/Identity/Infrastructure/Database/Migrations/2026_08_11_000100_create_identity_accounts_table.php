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
        Schema::create('identity.accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            // Public identifier. Internal id never leaves the database.
            $table->uuid('uuid')->unique();

            // Stored lower-cased and trimmed by the EmailAddress value object,
            // so a plain unique index is sufficient and case-insensitive in
            // practice without depending on the citext extension.
            $table->string('email', 320)->unique();
            $table->string('password_hash');

            $table->string('status', 20)->default('active');
            $table->timestampTz('email_verified_at')->nullable();

            // Lockout state lives on the account so a distributed rate limiter
            // failing open cannot also disable lockout (defence in depth).
            $table->unsignedSmallInteger('failed_login_count')->default(0);
            $table->timestampTz('locked_until')->nullable();
            $table->timestampTz('last_login_at')->nullable();

            $table->timestampsTz();

            $table->index('status');
            $table->index('locked_until');
        });

        DB::statement(
            "ALTER TABLE identity.accounts ADD CONSTRAINT accounts_status_check
             CHECK (status IN ('active', 'suspended', 'closed'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('identity.accounts');
    }
};
