# Scaling, Growth and Retention

## 1. Where the volume actually is

Data volume in an OTT platform is wildly uneven. Ordering by expected row count, largest first:

| Rank | Data | Driver | Where it lives |
|---|---|---|---|
| 1 | **Playback/QoE telemetry** | viewers × minutes × event rate | **Not in PostgreSQL.** Collector → object storage → analytics store |
| 2 | **Playback decisions** (audit) | play requests + heartbeats | PostgreSQL, partitioned, archived |
| 3 | **EPG programmes** | channels × events/day × retention | PostgreSQL, partitioned by `starts_at` |
| 4 | **Playback sessions** | concurrent viewers | PostgreSQL, partitioned, archived |
| 5 | **Admin audit log** | staff activity | PostgreSQL, partitioned |
| 6 | Resume points / watchlist | profiles × titles watched | PostgreSQL, ordinary |
| 7 | Billing records | subscribers × cycles | PostgreSQL, ordinary, **never deleted** |
| 8 | Catalog / rights | catalog size | PostgreSQL, small |

The important structural point: **the two biggest datasets are the two least valuable per row**.
Telemetry never enters the transactional database, and decisions are partitioned and archived. This
keeps the OLTP database in a size range where it stays fast and operationally simple, and it is a
decision that is very hard to reverse later.

### A worked estimate

Real figures need OQ-1/OQ-2. As an illustration of the shape, at 100,000 daily active viewers
averaging 2 hours/day:

- Playback sessions: ~150k–300k/day (sessions exceed viewers: channel changes, app restarts)
- Heartbeats at one per 30 s: ~24 million/day
- Decision records at start + significant heartbeat re-evaluations: **millions/day**
- QoE events at ~10/minute of viewing: **~120 million/day**

Two consequences follow immediately: telemetry must never touch PostgreSQL, and heartbeat frequency
is a **cost parameter**, not an implementation detail. Halving it halves the largest control-plane
write stream.

## 2. Partitioned tables (partitioned from their first migration)

| Table | Partition by | Interval | Hot window | Retention |
|---|---|---|---|---|
| `schedule.programmes` | `starts_at` | Monthly | Now ± 1 month | Past: per catch-up window + reporting. Future: as provided |
| `playback.sessions` | `started_at` | Weekly or monthly | Last 7 days | Archive after 90 days *(to confirm)* |
| `playback.decisions` | `decided_at` | Weekly or monthly | Last 24 hours | **Contractual — OQ-19** |
| `admin.audit_log` | `occurred_at` | Monthly | Last 30 days | Compliance-driven, typically years |
| `billing.provider_events` | `received_at` | Monthly | Last 7 days | Long — financial evidence |
| `notification.deliveries` | `created_at` | Monthly | Last 7 days | 90 days |

Partition management is automated with alerting on partition runway. A missing future partition
causes insert failures — an abrupt outage of the affected write path with no gradual degradation to
warn you.

## 3. Read scaling

Order of application, cheapest and least risky first:

1. **Index and query correctness.** Most "we need replicas" moments are one missing index.
2. **Cache what is hot and slow-changing** — catalog, lineups, availability projection, entitlement
   snapshot. Explicit invalidation on domain events, never blind TTLs on data that must be correct.
3. **Materialised read models** for expensive cross-context queries (discovery rails, availability).
4. **Read replicas**, used explicitly per query, never by a global "read from replica" switch.
   Replica lag is real; a read-your-own-writes bug in a subscription flow is a support incident.
5. **Separate the decision plane's reads** onto their own replica so an admin report cannot slow
   playback authorization.

Rule: **never read from a replica in a flow that just wrote and depends on that write.** This is a
code-review check, and it is the failure that most often reaches production from an otherwise correct
replica setup.

## 4. Write scaling

- Batch inserts for ingest (EPG, media processing events).
- Keep the outbox relay efficient: index on unprocessed rows, delete or archive processed rows
  promptly — an unbounded outbox table becomes the platform's slowest write path.
- Heartbeats update Redis, not PostgreSQL. Durable session state is written on start, on significant
  change, and on end.
- Decision records are **append-only inserts** with no indexes beyond what queries genuinely need;
  every extra index on the highest-volume table is a permanent write tax.

## 5. Retention and archival

| Data | Retention driver | Note |
|---|---|---|
| Playback decisions | **Contract** (licensor audit) | Longer than analytics; **OQ-19** |
| Playback telemetry | Product analytics value | Raw archived to object storage; aggregates kept longer |
| EPG history | Catch-up window + reporting | Beyond that, only what reporting needs |
| Billing records | Legal/tax (commonly years) | **Never deleted while the obligation stands** |
| Personal data | Data protection law + product need | Erasure requests must reach backups and archives — [`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md) |
| Admin audit | Compliance | Immutable |
| Media (mezzanine) | Rights window + re-encode need | Largest storage cost by far; lifecycle to cold storage |

Retention conflicts are resolved **explicitly**, not by whoever wrote the last policy: an erasure
request against a record with a legal retention obligation needs a documented resolution (usually
pseudonymisation rather than deletion), agreed with legal, and implemented as a specific code path —
not left to interpretation at incident time.

## 6. Media storage growth

Media dominates storage cost and is easy to underestimate. The multipliers:

```
per title:  mezzanine (1×, large)
          + renditions (ladder rungs × ~1.5–2× mezzanine in aggregate, codec-dependent)
          + packaging (CMAF: ~1× renditions; add ~1× more if a cenc fallback set is needed — ADR-0005)
          + subtitles/audio tracks per language
```

- Retain mezzanines only while re-encoding is plausible, then move to cold storage.
- nDVR/catch-up buffers grow as `channels × hours retained × bitrate` and are usually the **largest
  single storage line** in the platform. Modelled in
  [`../streaming/catchup-restart-npvr.md`](../streaming/catchup-restart-npvr.md).
- Storage lifecycle policies are set per class from the first upload. Retrofitting lifecycle rules
  onto petabytes is expensive and slow.

## 7. What to monitor from day one

Alert on trends, not just thresholds — the value of these metrics is seeing the curve before it
becomes an incident:

- Database size by schema and by table; growth rate per week
- Partition runway (days of future partitions remaining)
- Replica lag, p50 and p99
- Slow query log, with a budget per endpoint
- Cache hit ratio and invalidation rate
- **Entitlement snapshot staleness** (p99) — a correctness signal, not a performance one
- **Availability projection recompute lag** — the same
- Outbox depth and relay latency
- Queue depth per queue, and job failure rate
- Object storage growth by class, against the cost model
