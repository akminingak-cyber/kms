# CI/CD Strategy

## 1. Branching

**Trunk-based.** `main` is always releasable.

- Short-lived branches, merged within days. Long-lived branches accumulate merge risk and hide work.
- `main` is protected: no direct pushes, PR required, CI green required, `CODEOWNERS` review required.
- Squash merges — one commit per change on `main`, so history is bisectable.
- Incomplete work ships behind **feature flags**, not on a branch. A flag is testable in production;
  a branch is not.

Release branches exist only for client applications that require them (a Tizen submission awaiting
certification while `main` moves on), never for backend services.

## 2. Pipeline

Every PR, with the affected workspace set computed from the diff:

```
┌─ 1 STATIC ────────────────────────────────────────────────────┐
│ formatting · lint · type check · PHPStan/Larastan            │
│ ★ module boundary check     ★ secret scanning                 │
│ dependency audit            IaC scanning                      │
└───────────────────────────────────────────────────────────────┘
┌─ 2 CONTRACT ──────────────────────────────────────────────────┐
│ OpenAPI style (Spectral)                                      │
│ ★ BREAKING-CHANGE DETECTION vs the released spec              │
│ generated clients build · examples validate                   │
└───────────────────────────────────────────────────────────────┘
┌─ 3 TEST ──────────────────────────────────────────────────────┐
│ unit  ·  integration against REAL PostgreSQL + Redis          │
│ ★ migration check (see §4)  ·  spec conformance, both ways    │
└───────────────────────────────────────────────────────────────┘
┌─ 4 BUILD ─────────────────────────────────────────────────────┐
│ immutable, digest-pinned artifacts · SBOM · signing           │
└───────────────────────────────────────────────────────────────┘
┌─ 5 VERIFY ────────────────────────────────────────────────────┐
│ ephemeral preview environment · end-to-end API + UI journeys  │
└───────────────────────────────────────────────────────────────┘
```

The two starred items in stages 1 and 2 are the highest-value gates in the pipeline:

- **Module boundary check** — without mechanical enforcement, the modular monolith degrades into a
  monolith within weeks, and [ADR-0001](../architecture/adr/ADR-0001-modular-monolith-first.md) is
  void.
- **Breaking-change detection** — the only thing standing between a routine PR and a population of
  televisions that stops working. It can be bypassed **only** by a PR that also introduces the new
  major version. There is no approved-exception path, because the exception always looks reasonable
  in the moment and the cost lands on someone else years later.

Integration tests run against **real PostgreSQL and Redis**, never SQLite or in-memory substitutes —
SQLite silently accepts SQL PostgreSQL rejects, and vice versa, which converts a caught bug into a
production incident.

### Scheduled, not per-PR

| Cadence | Job | Why |
|---|---|---|
| Nightly | **External contract tests against vendor sandboxes** | Keeps our fakes honest; too slow and too flaky for per-PR |
| Nightly | Full test suite across all workspaces | Catches cross-workspace breakage that targeting missed |
| Nightly | Dependency vulnerability scan | |
| Weekly | Load test on the playback authorization path | Detects gradual regression before it becomes an incident |
| Weekly | Golden-file manifest verification | |
| Quarterly | Backup restore drill; DR exercise | An untested backup is a hypothesis |

## 3. Deployment

**Same artifact, promoted.** One build flows through environments; only configuration changes.

```
merge to main
   └─► build once ─► staging (automatic) ─► verify ─► production (gated) ─► canary ─► full
```

- **Production requires a human gate.** Not because automation is untrustworthy, but because someone
  should decide whether now is a good moment — mid-event, mid-billing-run, and Friday evening are all
  real considerations.
- **Canary first**, with automatic rollback on SLO breach. The canary must run long enough to see
  real traffic patterns; a 30-second canary proves the process started.
- **Migrations are a separate, explicit step** from code deployment, so either can be paused
  ([`../database/migrations-and-change-management.md`](../database/migrations-and-change-management.md)).
- **Rollback is a code rollback**, always possible because expand/contract keeps the schema
  compatible with the previous release.
- Deployments are recorded: what, when, by whom, which commit — and correlated with monitoring so
  "what changed?" is answerable in seconds during an incident.

### Deployment profiles

`core-api` deploys as several profiles from one artifact (web, admin, workers, playback-authorizer),
each with its own replica count, resource limits, database role and rate limits
([`../architecture/04-service-boundaries.md`](../architecture/04-service-boundaries.md)). They deploy
independently: an admin-only change need not restart the playback tier.

## 4. Migration gate

Blocking checks on any PR touching migrations:

- [ ] No foreign key crossing a schema boundary ([ADR-0003](../architecture/adr/ADR-0003-postgresql-system-of-record.md))
- [ ] No `CREATE INDEX` without `CONCURRENTLY` above a size threshold
- [ ] No column drop or rename in the same PR as the code change that stops using it
- [ ] `lock_timeout` and `statement_timeout` set
- [ ] Runs clean against a realistically sized database
- [ ] Time-partitioned tables include partitioning at creation

## 5. Client release trains

Client applications have a fundamentally different release cadence, and the pipeline must respect it
rather than pretend otherwise:

| Client | Cadence | Constraint |
|---|---|---|
| Web, admin | Continuous | We control the deploy |
| Android, iOS | Regular releases | Store review latency |
| **Android TV, Tizen, webOS** | **Planned releases** | **Certification adds real calendar time; propagation takes months** |

Consequences:

- **Backend releases never assume a client version.** New capability is additive and negotiated
  through `/config` and client version headers ([`../api/versioning.md`](../api/versioning.md)).
- Client release candidates are verified on the **device matrix** before submission
  ([`../streaming/player-and-device-matrix.md`](../streaming/player-and-device-matrix.md)).
- A client bug that cannot be fixed by a release must be fixable by **server configuration** — which
  is why the device matrix is data and why `/config` carries per-version behaviour.

## 6. Runners

- Require a **Docker daemon** for service containers. The session container used to initialise this
  repository has none ([`local-development.md`](local-development.md) §2), so CI runs on hosted or
  self-hosted runners that provide one.
- macOS runners are required for iOS from Phase 8.
- Build caching for Composer, pnpm and Docker layers — CI wall-clock time on a typical PR should stay
  under ~15 minutes, or people start working around it, which is worse than a slow pipeline.
- Runner credentials are least-privilege and scoped per pipeline; PRs from forks never receive
  secrets.

## 7. What CI must never do

- Deploy to production without the human gate
- Run against production data
- Hold long-lived production credentials
- Be bypassable with an "urgent" override — urgency is when the gates matter most
- Pass with skipped tests. A skipped test is a failing test with a nicer colour
