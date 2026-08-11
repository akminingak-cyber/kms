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
        Schema::create('schedule.channels', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->unsignedSmallInteger('number')->nullable()->unique();
            $table->string('logo_url', 500)->nullable();

            // Cross-context reference to catalog.categories — identifier only.
            $table->uuid('category_uuid')->nullable();

            $table->string('status', 20)->default('active');
            // Whether the channel may be recorded at all. Content without a
            // recording right must never reach the buffer, so this is data on
            // the channel and not a deployment flag.
            $table->boolean('catchup_enabled')->default(false);
            $table->timestampsTz();

            $table->index(['status', 'number']);
            $table->index('category_uuid');
        });

        DB::statement(
            "ALTER TABLE schedule.channels ADD CONSTRAINT channels_status_check
             CHECK (status IN ('active', 'inactive'))"
        );

        Schema::create('schedule.lineups', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            // A lineup is per market: channel ordering differs by territory.
            $table->string('territory', 2)->nullable();
            $table->timestampsTz();
        });

        Schema::create('schedule.lineup_entries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('lineup_id');
            $table->foreign('lineup_id')
                ->references('id')->on('schedule.lineups')
                ->cascadeOnDelete();

            $table->uuid('channel_uuid');
            $table->unsignedSmallInteger('position');
            $table->timestampsTz();

            $table->unique(['lineup_id', 'channel_uuid']);
            $table->unique(['lineup_id', 'position']);
        });

        /*
         * Programmes: the largest control-plane table, and almost always
         * queried by channel over a time range. Partitioned by `starts_at` from
         * its first migration — retrofitting partitioning onto a large live
         * table is painful and entirely avoidable.
         *
         * Raw SQL because Laravel's schema builder cannot express partitioning,
         * and the primary key must include the partition key.
         */
        DB::statement(<<<'SQL'
            CREATE TABLE schedule.programmes (
                id             bigserial     NOT NULL,
                uuid           uuid          NOT NULL,
                channel_uuid   uuid          NOT NULL,
                title          varchar(300)  NOT NULL,
                subtitle       varchar(300)  NULL,
                description    text          NULL,
                category_uuid  uuid          NULL,
                age_rating     varchar(20)   NULL,
                series_ref     varchar(120)  NULL,
                season_number  smallint      NULL,
                episode_number smallint      NULL,
                starts_at      timestamptz   NOT NULL,
                ends_at        timestamptz   NOT NULL,
                source         varchar(60)   NULL,
                source_ref     varchar(120)  NULL,
                revision       integer       NOT NULL DEFAULT 1,
                created_at     timestamptz   NULL,
                updated_at     timestamptz   NULL,
                PRIMARY KEY (id, starts_at),
                CONSTRAINT programmes_time_order CHECK (ends_at > starts_at)
            ) PARTITION BY RANGE (starts_at)
        SQL);

        // The EPG query is "this channel, this window", so the index leads on
        // channel and then time.
        DB::statement('CREATE INDEX programmes_channel_time_idx ON schedule.programmes (channel_uuid, starts_at, ends_at)');
        DB::statement('CREATE INDEX programmes_time_idx ON schedule.programmes (starts_at)');
        DB::statement('CREATE UNIQUE INDEX programmes_uuid_idx ON schedule.programmes (uuid, starts_at)');
        // Provider identity, used to reconcile corrections rather than
        // duplicate a corrected programme.
        DB::statement('CREATE UNIQUE INDEX programmes_source_idx ON schedule.programmes (source, source_ref, starts_at) WHERE source_ref IS NOT NULL');

        DB::statement('CREATE TABLE schedule.programmes_default PARTITION OF schedule.programmes DEFAULT');

        foreach ($this->monthRange() as [$from, $to, $suffix]) {
            DB::statement(
                "CREATE TABLE schedule.programmes_{$suffix}
                 PARTITION OF schedule.programmes
                 FOR VALUES FROM ('{$from}') TO ('{$to}')"
            );
        }

        /*
         * Append-only ingest history, retaining the provider's raw payload.
         *
         * Schedule providers issue corrections, and without the original
         * payload a mis-mapping cannot be diagnosed or reprocessed.
         */
        Schema::create('schedule.ingest_runs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('source', 60);
            $table->string('status', 20);
            $table->unsignedInteger('programmes_received')->default(0);
            $table->unsignedInteger('programmes_applied')->default(0);
            $table->unsignedInteger('programmes_rejected')->default(0);
            $table->jsonb('payload')->nullable();
            $table->jsonb('errors')->nullable();
            $table->timestampTz('received_at');
            $table->timestampTz('completed_at')->nullable();

            $table->index(['source', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule.ingest_runs');
        DB::statement('DROP TABLE IF EXISTS schedule.programmes CASCADE');
        Schema::dropIfExists('schedule.lineup_entries');
        Schema::dropIfExists('schedule.lineups');
        Schema::dropIfExists('schedule.channels');
    }

    /** @return list<array{0:string,1:string,2:string}> */
    private function monthRange(): array
    {
        $months = [];
        // One month behind for catch-up, several ahead for published schedule.
        $cursor = (new DateTimeImmutable('first day of this month 00:00:00', new DateTimeZone('UTC')))
            ->modify('-1 month');

        for ($i = 0; $i < 6; $i++) {
            $next = $cursor->modify('+1 month');
            $months[] = [$cursor->format('Y-m-d H:i:sP'), $next->format('Y-m-d H:i:sP'), $cursor->format('Y_m')];
            $cursor = $next;
        }

        return $months;
    }
};
