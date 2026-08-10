# Database Strategy

Decision record: [`../architecture/adr/ADR-0003-postgresql-system-of-record.md`](../architecture/adr/ADR-0003-postgresql-system-of-record.md)

## Contents

| Document | Covers |
|---|---|
| This document | Topology, schema strategy, identifiers, consistency, Redis, backup |
| [`conceptual-model.md`](conceptual-model.md) | The main aggregates per context and the relationships that matter |
| [`migrations-and-change-management.md`](migrations-and-change-management.md) | How schema changes ship without downtime |
| [`scaling-and-retention.md`](scaling-and-retention.md) | Partitioning, growth, retention, archival |

---

## 1. Storage topology

| Store | Role | Rule |
|---|---|---|
| **PostgreSQL** | System of record for all control-plane state | Everything that must be correct lives here |
| **Redis** | Cache, queues, distributed locks, concurrency counters, rate limits | **Never a system of record.** Everything in Redis is reconstructible or acceptable to lose |
| **Object storage** | Media (mezzanine, renditions, packages), raw event archives, backups | Immutable content, addressed by identifier |
| **Analytics store** | Telemetry at rest, aggregation, BI | Fed asynchronously; never queried by the decision plane |
| **Key vault / KMS** | Content keys and secrets | Separate trust domain — [`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md) |

PostgreSQL version: target the current stable major at Phase 1 start; the client available in this
environment is 16.13, but the server version is a Phase 1 decision made with the hosting decision
(**OQ-16**). Pin the major explicitly and keep local, CI and production on the same one — subtle
planner and collation differences between majors cause bugs that reproduce nowhere but production.

## 2. Schema strategy

**One database, one schema per bounded context.** Schemas match the module list in
[`../architecture/03-repository-structure.md`](../architecture/03-repository-structure.md):

```
identity   profile   device     catalog    schedule   media      discovery  product
billing    entitlement          rights     playback   protection delivery   admin      notification
```

Rules:

1. **A module writes only to its own schema.** Enforced by database role, not by convention.
2. **No foreign key crosses a schema boundary.** Cross-context references are identifier columns
   named for their target (`catalog_title_id`), validated through the owning context's port. A CI
   check rejects migrations that create cross-schema constraints.
3. **Read models may span contexts** — a projection in `discovery` may contain data derived from
   `catalog`, `rights` and `entitlement`. It is derived data, maintained by projection from events,
   and rebuildable from source at any time.
4. **Each deployment profile gets its own role** with the narrowest grants that let it work.
   `playback-authorizer` has read on `entitlement`, `rights`, `profile`, `device`, `catalog`,
   `schedule`; write only on `playback`; **no access at all to `protection` or `billing`.**

Why this matters concretely: when the platform later needs to extract Entitlements or Rights into its
own service, the work is moving a schema and repointing a connection — not untangling a decade of
joins.

## 3. Identifiers

| Kind | Type | Where |
|---|---|---|
| Internal primary key | `bigint` identity | All tables. Index locality and join performance |
| Public identifier | **ULID**, stored as `char(26)` or `uuid`, unique-indexed | Every entity exposed in an API |
| Natural/vendor keys | Their own type | In a per-integration mapping table, never on the aggregate |
| Correlation | `correlation_id` (ULID) | Propagated across every request, event and log line |

**Sequential integers are never exposed in an API.** They leak business volume (`/titles/1042` tells
a competitor the catalog size) and invite enumeration. ULIDs are chosen over random UUIDv4 for index
locality; they are lexicographically sortable by creation time, which also makes cursor pagination
natural. UUIDv7 is an acceptable alternative if the chosen PostgreSQL major and the framework both
support it cleanly — decide once, in Phase 1, and apply it everywhere.

## 4. Consistency model

| Boundary | Consistency | Mechanism |
|---|---|---|
| Within an aggregate | Strong | Single transaction |
| Within a bounded context | Strong | Single transaction, same schema |
| Across contexts, same request | **Eventual** | Domain events via transactional outbox |
| Entitlement snapshot | Eventual, bounded staleness | Recomputed on billing events; staleness budget defined and **monitored** |
| Rights availability projection | Eventual, versioned | Recomputed on rights change; version recorded in every decision |
| Analytics | Eventual, best effort | Async pipeline; may drop under load |

The staleness of the entitlement snapshot deserves an explicit product decision rather than a default:
if a subscription is cancelled, how long may playback continue to be authorized? Zero is achievable
but costly on the hot path; a bounded window (say, minutes) is usual. It is a **product and
commercial decision** (**OQ-23**), and whatever is chosen must be monitored and alerted on, because
an unnoticed projection lag means unentitled viewing.

### Transactional outbox

Every domain event is written to an `outbox` table **in the same transaction** as the state change,
then relayed to the queue by a dispatcher process with at-least-once delivery. Consumers are
idempotent, keyed by `event_id`.

This is not ceremony. Publishing directly from application code produces two failure modes —
committed state with a lost event, or a published event whose transaction rolled back — and both
create inconsistencies in billing and entitlement data that are effectively undiscoverable until a
customer complains.

## 5. Redis usage

| Use | Durability requirement | On Redis loss |
|---|---|---|
| Response/query cache | None | Cold cache, higher database load |
| Queues | Jobs must not vanish | Persistence enabled; critical jobs also have a database-backed reconciliation sweep |
| Concurrency slots | Authoritative for **enforcement**, mirrored durably | Sessions in PostgreSQL allow rebuild; brief over-permissiveness accepted and documented |
| Rate limits | None | Limits reset |
| Distributed locks | Correctness-relevant | Lock holders must tolerate loss; no lock protects a non-idempotent operation |

**Rule:** if losing a Redis key would cause incorrect money, incorrect entitlement, or unauthorized
playback that cannot be detected and corrected, it does not belong only in Redis.

## 6. Backup and recovery

- **Continuous WAL archiving with point-in-time recovery.** Nightly base backups.
- **RPO and RTO targets are business decisions** (**OQ-24**) — recorded and then designed to, not
  assumed. Note the asymmetry: losing 15 minutes of playback telemetry is a nuisance; losing 15
  minutes of payment records is a financial and legal problem. Different stores may justify different
  targets.
- **Restore is tested quarterly** by restoring to a scratch environment and running a verification
  suite. An untested backup is a hypothesis.
- Backups are encrypted at rest, access-controlled separately from the database, and **retention
  respects data-protection obligations** — a backup containing data a user asked to have erased is
  still a copy of that data ([`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md)).

## 7. Access control

- Application roles are **least privilege**, per deployment profile.
- No human uses an application role. Human access is individually attributed, time-boxed, approved,
  and audited.
- Production database access from developer machines is prohibited; diagnosis goes through
  observability and, where genuinely necessary, an audited break-glass procedure.
- Migrations run under a dedicated migration role with DDL rights that application roles lack.

## 8. Conventions

- `snake_case` for tables and columns; plural table names.
- Timestamps are `timestamptz`, **always UTC**. Broadcast-local times are stored with an explicit IANA
  timezone alongside — an EPG that stores wall-clock times without a zone breaks twice a year, in a
  way that is very visible to viewers.
- Monetary amounts are `bigint` minor units plus an ISO 4217 currency column. **No floats, ever.**
- Enumerations are constrained (`CHECK` or lookup table), never free text.
- Soft deletion is used only where the domain genuinely requires recovery; elsewhere, delete and rely
  on the audit trail. Soft-delete everywhere silently corrupts every subsequent query that forgets
  the filter.
- Every table has `created_at`; mutable tables have `updated_at`.
- Every index exists for a known query; unexplained indexes are removed at review.
