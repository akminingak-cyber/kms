# Migrations and Schema Change Management

## 1. Principles

1. **Deployments do not take the platform down.** A schema change must be safe while the previous
   version of the code is still running, because during a rolling deploy both versions run at once.
2. **Migrations are forward-only in production.** `down()` exists for local development. In
   production, a mistake is corrected by a new migration, not by rolling back a schema change against
   data that has moved on.
3. **Schema changes and code deploys are separate steps**, so either can be paused.
4. **Destructive changes are separated in time from the code change that makes them safe** — usually
   by at least one full release.
5. **Every migration is tested against a realistic data volume** before it touches production. A
   migration that is instant on 1,000 rows can lock a table for minutes on 100 million.

## 2. Expand / contract

The only pattern permitted for changes to columns that live code touches.

### Renaming a column (`plan_id` → `product_plan_id`)

| Release | Migration | Code |
|---|---|---|
| **1 — Expand** | Add `product_plan_id`, nullable. Backfill in batches. Add a trigger or dual-write so both stay in sync | Writes both, reads `plan_id` |
| **2 — Migrate** | — | Writes both, reads `product_plan_id` |
| **3 — Contract** | Drop the trigger, drop `plan_id` | Writes and reads `product_plan_id` only |

Three releases to rename a column is not overhead — it is the price of never taking the platform
down, and it is far cheaper than one unplanned outage during a subscription renewal run.

### Same pattern applies to
- Changing a column type → new column, backfill, switch, drop
- Splitting or merging tables → new structure, dual-write, migrate readers, drop
- Adding a `NOT NULL` column → add nullable, backfill, add the constraint (validated separately)
- Changing an enumeration → add the new value, migrate data, remove the old value later

## 3. PostgreSQL-specific rules

| Operation | Rule |
|---|---|
| `CREATE INDEX` | **Always `CONCURRENTLY`** on any table with meaningful volume. Outside a transaction; check for an invalid index afterwards and retry if needed |
| `ADD COLUMN` with a default | Safe on modern PostgreSQL for non-volatile defaults; still verify on the target major before assuming |
| `ADD CONSTRAINT` | Add `NOT VALID` first, then `VALIDATE CONSTRAINT` separately — validation takes a weaker lock |
| `ALTER COLUMN TYPE` | Usually a table rewrite. Use expand/contract instead |
| `DROP COLUMN` | Fast, but only after no running code references it |
| Backfills | **Batched**, throttled, resumable, and run outside the migration transaction. Never `UPDATE` a large table in one statement |
| Long transactions | Prohibited in migrations. They block autovacuum and hold locks |
| Lock acquisition | Set a short `lock_timeout` so a migration fails fast instead of queueing behind a long query and blocking everything behind it |

That last rule prevents a specific and common outage: a DDL statement waits on an existing long
transaction, and every subsequent query on that table queues behind the DDL's lock request. The table
becomes unavailable without anything appearing to be "running".

## 4. Ownership and layout

Migrations live with their module, not in a global directory:

```
services/core-api/modules/<Context>/Infrastructure/Database/Migrations/
```

- A migration touches **one schema only**. A change spanning contexts is two migrations in two
  modules, reviewed by both owners.
- Filenames are timestamp-prefixed; ordering across modules is by timestamp.
- Migrations that require a backfill ship with the backfill as a **separate, resumable job**, never
  inline.

## 5. CI checks on every migration

Automated, blocking:

- [ ] No foreign key crossing a schema boundary (ADR-0003)
- [ ] No `CREATE INDEX` without `CONCURRENTLY` on a table above a size threshold
- [ ] No column drop or rename in the same PR as the code change that stops using it
- [ ] `lock_timeout` and `statement_timeout` set
- [ ] Migration runs clean against a database seeded to a realistic volume
- [ ] Up and down both execute in local mode
- [ ] Time-partitioned tables include their partitioning at creation
- [ ] No `SELECT *` in a projection or view definition

## 6. Data migrations

Data migrations are **jobs, not migrations**. They are:

- Idempotent — re-running produces the same result
- Resumable — they record progress and continue after interruption
- Batched and throttled — with a measured effect on database load
- Observable — progress, rate and errors are visible in dashboards
- Reversible **or** explicitly documented as irreversible, with sign-off

A data migration that cannot be resumed will, at some point, be interrupted halfway through a table
of tens of millions of rows. Design for that from the start.

## 7. Partitioned tables

Tables listed in [`scaling-and-retention.md`](scaling-and-retention.md) are created partitioned in
their **first** migration. Adding partitioning later means rewriting a large table.

- Partitions are created ahead of time by a scheduled job, with alerting if the runway drops below a
  threshold. A missing future partition means failed inserts — a silent, sudden outage of the
  affected write path.
- Old partitions are detached and archived per the retention policy; detaching is cheap, deleting
  rows is not.

## 8. Zero-downtime deployment sequence

```
1. Run expand migrations                (old code still running, unaffected)
2. Deploy new code to a canary          (writes both, reads old)
3. Verify canary health and metrics
4. Complete the rollout
5. Run backfill jobs                    (throttled, monitored)
6. Deploy read-switch                   (reads new)
7. Verify
8. Next release: contract migrations    (drop the old structure)
```

Rollback at any step before 8 is a code rollback only — the schema still supports the old code. That
property is the entire point of the sequence.
