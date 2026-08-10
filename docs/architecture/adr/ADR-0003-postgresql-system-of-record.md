# ADR-0003: PostgreSQL as system of record, schema per bounded context

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture

## Context

Sixteen contexts share one codebase initially (ADR-0001) but must be separable later. The data
layer decides whether that separation is ever possible: if every context's tables are joined to
every other's by foreign keys, extraction is a rewrite regardless of how clean the code is.

The workloads differ sharply. Billing and entitlements need strong transactional consistency.
Playback authorization needs sub-millisecond reads. EPG is bulk-written and time-range-queried.
Telemetry is write-dominated at volumes an OLTP database should never see.

## Options considered

**A. One database, one shared schema.** Simple, and the fastest way to make the contexts inseparable.

**B. One database per context from day one.** Removes the temptation to join, but forces distributed
transactions and cross-database reporting before the boundaries are validated, and multiplies
operational burden with no team to carry it.

**C. One database, one schema per bounded context, no cross-context foreign keys.** Keeps
transactional simplicity within a context, makes cross-context coupling visible and reviewable, and
makes later extraction a matter of moving a schema.

## Decision

**Option C**, with these rules:

1. One PostgreSQL cluster, one logical database, **one schema per bounded context**
   (`identity`, `profile`, `device`, `catalog`, `schedule`, `media`, `discovery`, `product`,
   `billing`, `entitlement`, `rights`, `playback`, `protection`, `delivery`, `admin`, `notification`).
2. **No foreign key may cross a schema boundary.** Cross-context references are stored as
   identifiers and validated through the owning context's port. Enforced by a CI check on
   migrations.
3. Each deployment profile connects with a **role scoped to the schemas it needs** —
   `playback-authorizer` cannot write to `billing`, and cannot read `protection` at all.
4. Redis is a cache, queue, lock and counter store. **Never a system of record.** Anything in Redis
   must be reconstructible from PostgreSQL or be acceptable to lose.
5. **Telemetry does not go into this database.** Events go to the collector and on to object storage
   or a warehouse; only aggregates return.
6. High-volume time-based tables (EPG programmes, playback sessions, decision audit, admin audit)
   are **partitioned by time** from their first migration. Retrofitting partitioning onto a large
   live table is painful and avoidable.
7. Public identifiers are **ULIDs** exposed in APIs; internal primary keys stay `bigint` for index
   locality. Sequential integers are never exposed externally — they leak business volume and invite
   enumeration.

## Consequences

**Accepted costs**
- No database-enforced referential integrity across contexts; correctness rests on ports, events and
  tests. This is the deliberate trade for separability.
- Cross-context reporting needs purpose-built read models or a warehouse, not ad-hoc joins.
- Schema-scoped roles add setup work to every new deployment profile.

**Made easier**
- Extracting a context later is moving one schema and repointing one connection.
- Blast radius is visible: a role that cannot reach a schema cannot damage it.
- Query plans and index strategy can be tuned per context without a shared-table tug of war.

**Revisit when**
- A single context's write volume or storage size justifies its own cluster, or
- Read replicas can no longer absorb the read load for the decision plane.
