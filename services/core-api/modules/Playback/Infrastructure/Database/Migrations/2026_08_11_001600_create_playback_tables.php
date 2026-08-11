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
        Schema::create('playback.sessions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            $table->uuid('account_uuid');
            $table->uuid('profile_uuid');
            $table->uuid('device_uuid');
            $table->string('device_class', 20);

            $table->string('content_type', 20);
            $table->uuid('content_ref');
            $table->string('mode', 20);

            $table->timestampTz('started_at');
            $table->timestampTz('last_heartbeat_at');
            $table->timestampTz('ended_at')->nullable();
            $table->string('end_reason', 40)->nullable();

            $table->timestampsTz();

            $table->index(['account_uuid', 'ended_at']);
            $table->index('last_heartbeat_at');
        });

        /*
         * The durable mirror of the Redis concurrency counter.
         *
         * Redis is authoritative for enforcement because the check is on the
         * hot path; this table is what lets the counter be rebuilt after a
         * Redis loss, and what makes leaked slots visible.
         */
        Schema::create('playback.concurrency_slots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('session_uuid')->unique();
            $table->uuid('account_uuid');
            $table->timestampTz('acquired_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('released_at')->nullable();

            $table->index(['account_uuid', 'released_at']);
            $table->index('expires_at');
        });

        /*
         * The compliance record.
         *
         * Every decision, allowed or denied, with the inputs that produced it —
         * so a licensor's question about a specific date is answered from a
         * record rather than by re-deriving from today's data.
         *
         * Append-only, partitioned by time, and with no indexes beyond the
         * partition key and the identifier: query-driven indexes belong on an
         * offline copy, because every extra index on the platform's
         * highest-volume table is a permanent write tax on the latency-critical
         * path.
         */
        DB::statement(<<<'SQL'
            CREATE TABLE playback.decisions (
                id                    bigserial    NOT NULL,
                uuid                  uuid         NOT NULL,
                outcome               varchar(10)  NOT NULL,
                reason_code           varchar(60)  NULL,
                account_uuid          uuid         NULL,
                profile_uuid          uuid         NULL,
                device_uuid           uuid         NULL,
                device_class          varchar(20)  NULL,
                session_uuid          uuid         NULL,
                content_type          varchar(20)  NOT NULL,
                content_ref           uuid         NOT NULL,
                mode                  varchar(20)  NOT NULL,
                availability_version  bigint       NULL,
                rights_rule_ids       jsonb        NULL,
                entitlement_grant_ids jsonb        NULL,
                territory             varchar(2)   NULL,
                territory_method      varchar(30)  NULL,
                applied_restrictions  jsonb        NULL,
                concurrency_source    varchar(20)  NULL,
                correlation_id        uuid         NULL,
                client                varchar(20)  NULL,
                client_version        varchar(40)  NULL,
                decided_at            timestamptz  NOT NULL,
                PRIMARY KEY (id, decided_at),
                CONSTRAINT decisions_outcome_check CHECK (outcome IN ('allow', 'deny'))
            ) PARTITION BY RANGE (decided_at)
        SQL);

        DB::statement('CREATE UNIQUE INDEX decisions_uuid_idx ON playback.decisions (uuid, decided_at)');

        DB::statement('CREATE TABLE playback.decisions_default PARTITION OF playback.decisions DEFAULT');

        foreach ($this->monthRange() as [$from, $to, $suffix]) {
            DB::statement(
                "CREATE TABLE playback.decisions_{$suffix}
                 PARTITION OF playback.decisions
                 FOR VALUES FROM ('{$from}') TO ('{$to}')"
            );
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS playback.decisions CASCADE');
        Schema::dropIfExists('playback.concurrency_slots');
        Schema::dropIfExists('playback.sessions');
    }

    /** @return list<array{0:string,1:string,2:string}> */
    private function monthRange(): array
    {
        $months = [];
        $cursor = new DateTimeImmutable('first day of this month 00:00:00', new DateTimeZone('UTC'));

        for ($i = 0; $i < 4; $i++) {
            $next = $cursor->modify('+1 month');
            $months[] = [$cursor->format('Y-m-d H:i:sP'), $next->format('Y-m-d H:i:sP'), $cursor->format('Y_m')];
            $cursor = $next;
        }

        return $months;
    }
};
