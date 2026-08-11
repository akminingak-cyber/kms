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
        Schema::create('admin.staff_users', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('email', 320)->unique();
            $table->string('name', 100);
            $table->string('password_hash');
            $table->string('role', 30);

            /*
             * MFA is mandatory for staff — no exceptions
             * (docs/security/identity-and-access.md §5). The secret is stored
             * encrypted at rest, and the column is NOT NULL so an account
             * without MFA cannot exist rather than merely being discouraged.
             */
            $table->text('totp_secret');
            $table->timestampTz('mfa_enrolled_at');

            $table->string('status', 20)->default('active');
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();

            $table->index('role');
        });

        DB::statement(
            "ALTER TABLE admin.staff_users ADD CONSTRAINT staff_role_check
             CHECK (role IN ('platform_engineer', 'content_operator', 'rights_manager',
                             'commercial_operator', 'support_agent', 'auditor'))"
        );

        DB::statement(
            "ALTER TABLE admin.staff_users ADD CONSTRAINT staff_status_check
             CHECK (status IN ('active', 'suspended'))"
        );

        /*
         * The audit trail. Append-only, and partitioned by month from its first
         * migration — retrofitting partitioning onto a large live table is
         * painful and entirely avoidable (docs/database/migrations-*.md §7).
         *
         * Raw SQL because Laravel's schema builder cannot express partitioning.
         * The primary key must include the partition key, hence (id, occurred_at).
         */
        DB::statement(<<<'SQL'
            CREATE TABLE admin.audit_entries (
                id           bigserial      NOT NULL,
                uuid         uuid           NOT NULL,
                actor_type   varchar(20)    NOT NULL,
                actor_id     uuid           NULL,
                action       varchar(80)    NOT NULL,
                subject_type varchar(40)    NULL,
                subject_id   uuid           NULL,
                correlation_id uuid         NULL,
                ip_address   varchar(45)    NULL,
                user_agent   varchar(512)   NULL,
                context      jsonb          NULL,
                occurred_at  timestamptz    NOT NULL,
                PRIMARY KEY (id, occurred_at),
                CONSTRAINT audit_actor_type_check
                    CHECK (actor_type IN ('account', 'staff', 'system'))
            ) PARTITION BY RANGE (occurred_at)
        SQL);

        DB::statement('CREATE INDEX audit_entries_action_idx ON admin.audit_entries (action, occurred_at DESC)');
        DB::statement('CREATE INDEX audit_entries_actor_idx ON admin.audit_entries (actor_id, occurred_at DESC)');
        DB::statement('CREATE INDEX audit_entries_subject_idx ON admin.audit_entries (subject_type, subject_id, occurred_at DESC)');
        DB::statement('CREATE INDEX audit_entries_correlation_idx ON admin.audit_entries (correlation_id)');
        DB::statement('CREATE UNIQUE INDEX audit_entries_uuid_idx ON admin.audit_entries (uuid, occurred_at)');

        /*
         * A DEFAULT partition is a deliberate safety net. A missing future
         * partition otherwise causes insert failures — an abrupt outage of the
         * write path with no gradual degradation to warn anyone. Scheduled
         * partition creation still runs (and is alerted on); this makes its
         * failure survivable rather than immediate.
         */
        DB::statement('CREATE TABLE admin.audit_entries_default PARTITION OF admin.audit_entries DEFAULT');

        foreach ($this->monthRange() as [$from, $to, $suffix]) {
            DB::statement(
                "CREATE TABLE admin.audit_entries_{$suffix}
                 PARTITION OF admin.audit_entries
                 FOR VALUES FROM ('{$from}') TO ('{$to}')"
            );
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS admin.audit_entries CASCADE');
        Schema::dropIfExists('admin.staff_users');
    }

    /**
     * Current month plus a runway of future months.
     *
     * @return list<array{0:string,1:string,2:string}>
     */
    private function monthRange(): array
    {
        $months = [];
        $cursor = new DateTimeImmutable('first day of this month 00:00:00', new DateTimeZone('UTC'));

        for ($i = 0; $i < 4; $i++) {
            $next = $cursor->modify('+1 month');
            $months[] = [
                $cursor->format('Y-m-d H:i:sP'),
                $next->format('Y-m-d H:i:sP'),
                $cursor->format('Y_m'),
            ];
            $cursor = $next;
        }

        return $months;
    }
};
