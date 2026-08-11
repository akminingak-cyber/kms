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
         * An account is the billing and security subject; a profile is the
         * viewing subject. All viewing state hangs off a profile, never an
         * account — retrofitting that once history exists is close to
         * impossible (docs/database/conceptual-model.md).
         */
        Schema::create('profile.profiles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // Cross-context reference to identity.accounts — no foreign key.
            $table->uuid('account_uuid');

            $table->string('name', 50);
            $table->boolean('is_primary')->default(false);

            // Null means unrestricted. Enforced server-side at playback from
            // Phase 4; the client only ever renders the outcome.
            $table->string('max_rating', 20)->nullable();
            $table->string('pin_hash')->nullable();

            $table->string('locale', 20)->default('en');
            $table->string('audio_language', 20)->nullable();
            $table->string('subtitle_language', 20)->nullable();

            $table->timestampsTz();

            $table->index('account_uuid');
        });

        // Profile names are how a household tells profiles apart, so they must
        // be unique within the account.
        DB::statement(
            'CREATE UNIQUE INDEX profiles_account_name_unique
             ON profile.profiles (account_uuid, lower(name))'
        );

        // Exactly one primary profile per account, enforced by the database
        // rather than by application discipline.
        DB::statement(
            'CREATE UNIQUE INDEX profiles_account_primary_unique
             ON profile.profiles (account_uuid)
             WHERE is_primary'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('profile.profiles');
    }
};
