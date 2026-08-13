# IMPLEMENTATION_PLAN.md — KMS TV

**Project:** KMS TV — production-grade IPTV/OTT platform
**Scope:** Zero to production, Phases 0–37
**Governing document:** `CLAUDE.md` (binding — see §22 for gate rules)
**Live status:** `PROJECT_STATE.md`

---

## How this plan works

Every phase below defines eight things: **objective**, **dependencies**, **major tasks**,
**acceptance criteria**, **tests**, **security checks**, **documentation requirements**,
and a **completion gate**.

### The universal gate

In addition to its own gate, **no phase may be marked COMPLETE** unless all eight
conditions of `CLAUDE.md` §22 hold:

1. Implementation complete — no stub presented as finished
2. All tests pass — with no skips added to achieve it
3. Static analysis passes at the declared level
4. Security checks pass
5. Documentation updated in the same change
6. Acceptance criteria demonstrably pass
7. No known critical defect remains
8. `PROJECT_STATE.md` updated

**If a gate fails: STOP.** Record it under *Blockers*, fix it, re-run the gate.
**Never proceed to the next phase automatically** — a passing gate is a report to a
human, not permission to continue.

### Sequencing note

Phases are numbered by dependency, not by calendar. Phases 19–26 (clients and content
features) may run in parallel **only** after Phase 15 is COMPLETE, because every client
depends on a settled playback-authorization contract. Parallelism before that point
produces rework, not speed.

### The critical spine

`3 → 4 → 6 → 7 → 8 → 13 → 14 → 15 → 16 → 17`

Everything else consumes that spine. Rights (13), entitlements (14), and playback
authorization (15) are where the commercial and legal integrity of this product lives.
They are not features. Treat any pressure to defer them as a serious risk.

---

# PHASE 0 — Environment and project foundation

**Objective.** Establish a verified picture of the development environment and install
the project's permanent governance documents, without writing application code.

**Dependencies.** None.

**Major tasks.**
- Audit OS, CPU, RAM, disk, and every required tool, distinguishing *installed binary*
  from *running service*.
- Audit git repository state, branches, remotes, configuration, and pre-existing content.
- Verify network egress to package registries.
- Verify secret hygiene by name only; read no secret values.
- Author `CLAUDE.md`, `PROJECT_STATE.md`, `IMPLEMENTATION_PLAN.md`.
- Create `docs/legal/` with the license register, content-rights policy, and decision log.
- Classify every tool as INSTALLED / MISSING / RECOMMENDED / OPTIONAL with justification.

**Acceptance criteria.**
- All 25 requested inspection points reported, each with a version or an explicit
  "missing" and a reason it will eventually be needed.
- Running-service status established for Docker, PostgreSQL, and Redis — not merely
  binary presence.
- Pre-existing repository content identified and assessed.
- The three governance documents exist and are internally consistent.
- No application code, no `package.json`, no `composer.json`, no schema, no Docker
  services, no dependencies installed, no OS modification.

**Tests.** None (no code). Verification is by re-running the audit commands and matching
the report.

**Security checks.** No secret value read, printed, or committed. No credential-bearing
file tracked in git. Confirm `.gitignore` excludes `.env`.

**Documentation requirements.** The three governance documents plus `docs/legal/`.

**Completion gate.** Audit report delivered; three documents committed and pushed;
`PROJECT_STATE.md` reflects reality including the pre-existing scaffold; **human
confirmation obtained**. Then STOP.

---

# PHASE 1 — Product specification

**Objective.** Define precisely what KMS TV is, for whom, and — equally important — what
it is not.

**Dependencies.** Phase 0 COMPLETE.

**Major tasks.**
- Resolve the open Phase 0 decisions that gate design: fate of the pre-existing scaffold
  (D-006) and local runtime strategy (D-007).
- Define the operator model: single-tenant or multi-tenant, and the territories served.
- Define subscriber-facing product surface: Live TV, EPG, VOD, movies, series, catch-up,
  restart, profiles, devices, search, recommendations.
- Define the commercial model: packages, tiers, trials, promotions, concurrency
  allowances, device limits.
- Define supported platforms and, for each, the **minimum** supported device and OS
  version — the constraint that drives all client engineering.
- Define languages, locales, subtitles, and audio tracks.
- Write an explicit non-goals list.
- Draft the content-rights operating model with the business: what agreements look like,
  who supplies metadata, how expiry is communicated.

**Acceptance criteria.**
- Every feature named in the product brief is either specified or explicitly deferred
  with a reason.
- Minimum supported device is named per platform — no "modern devices".
- The commercial model is expressed unambiguously enough to build a data model from.
- Non-goals list exists and includes the prohibited feature classes from `CLAUDE.md` §2.2.

**Tests.** Not applicable. Review-based: each stakeholder question must be answerable
from the document alone.

**Security checks.** Confirm no specified feature falls into a prohibited class.
Confirm the specification assumes licensed content only.

**Documentation requirements.** `docs/product/specification.md`,
`docs/product/non-goals.md`, `docs/product/platforms.md`.

**Completion gate.** Specification reviewed and signed off; D-006 and D-007 resolved and
recorded; no feature in the plan is prohibited by `CLAUDE.md` §2.

---

# PHASE 2 — Requirements and acceptance criteria

**Objective.** Convert the specification into testable requirements with measurable
acceptance criteria.

**Dependencies.** Phase 1 COMPLETE.

**Major tasks.**
- Write functional requirements, each atomic, uniquely identified, and testable.
- Write non-functional requirements with numbers: availability target, API p95/p99
  latency, time-to-first-frame, rebuffer ratio, concurrent-stream capacity, EPG freshness.
- Define acceptance criteria per requirement, in Given/When/Then form.
- Define the compliance requirements: rights enforcement, geo enforcement, concurrency
  enforcement, audit retention, privacy obligations.
- Build the requirement → phase traceability matrix.
- Define the release criteria for launch.

**Acceptance criteria.**
- Every requirement has an ID, an owner phase, and testable acceptance criteria.
- Every non-functional requirement has a number and a measurement method. No adjectives.
- Traceability matrix covers 100% of requirements with no orphans.

**Tests.** Requirement review: each is checked for testability. Any requirement that
cannot be falsified is rewritten.

**Security checks.** Security and privacy requirements are first-class entries in the
matrix, not an appendix.

**Documentation requirements.** `docs/requirements/functional.md`,
`docs/requirements/non-functional.md`, `docs/requirements/traceability.md`,
`docs/requirements/release-criteria.md`.

**Completion gate.** Matrix complete; all NFRs numeric; sign-off recorded.

---

# PHASE 3 — System architecture

**Objective.** Design the system: modules, boundaries, data flows, deployment topology,
and the decisions that are expensive to reverse.

**Dependencies.** Phase 2 COMPLETE.

**Major tasks.**
- Define modules and their boundaries: Identity, Catalogue, EPG, Rights, Entitlements,
  Playback Authorization, Billing, Delivery, Analytics, Administration.
- Define the control-plane / data-plane split and prove segment delivery never traverses
  the application tier.
- Define synchronous vs. asynchronous flows, queue topology, and idempotency strategy.
- Design vendor abstractions: CDN, DRM, payments, notifications — each validated on paper
  against **two** real candidate providers.
- Define environments (local, CI, staging, production) and deployment topology.
- Define the caching strategy, including immediate invalidation for rights and
  entitlement changes.
- Write ADRs for every expensive-to-reverse decision.

**Acceptance criteria.**
- Module boundary diagram exists; every cross-module interaction is a named interface.
- No design permits a client to be the authority on entitlement.
- Every third-party integration point sits behind an abstraction with no vendor type in
  domain code.
- Failure behaviour is defined for every external dependency.
- ADRs exist for datastore, packaging format, CDN strategy, DRM approach, payment
  provider, and client frameworks.

**Tests.** Architecture review against the requirements matrix; a walkthrough of at least
five end-to-end flows including one failure path per external dependency.

**Security checks.** Trust boundaries drawn. Every boundary crossing lists its
authentication and authorization. Origin is confirmed unreachable from the public
internet by design.

**Documentation requirements.** `docs/architecture/overview.md`,
`docs/architecture/modules.md`, `docs/architecture/data-flows.md`,
`docs/architecture/decisions/ADR-*.md`.

**Completion gate.** Architecture reviewed; ADRs recorded; every Phase 2 requirement maps
to at least one module.

---

# PHASE 4 — Database and ERD

**Objective.** Design the PostgreSQL schema that the platform's correctness rests on.

**Dependencies.** Phase 3 COMPLETE.

**Major tasks.**
- Model all domains: identity, accounts, profiles, devices, sessions, roles, permissions,
  channels, streams, EPG, VOD, series, packages, subscriptions, **rights**, entitlements,
  playback sessions, payments, notifications, audit.
- Design the rights model to carry holder, contract reference, territories, windows,
  device classes, distribution modes, and concurrency limits.
- Define constraints, indexes, and partitioning strategy for the high-volume tables
  (EPG, playback events, audit).
- Define data classification: which columns are personal data, which are sensitive.
- Define retention and deletion rules per table.
- Plan the migration approach and the seed/reference-data separation.

**Acceptance criteria.**
- ERD complete and reviewed; every entity traceable to a Phase 2 requirement.
- Rights model can answer, for any asset: *why may we serve this, to whom, where, until
  when?*
- Money columns are integer minor units or `NUMERIC` with an accompanying currency.
  No floats anywhere near money.
- All timestamps `timestamptz`, UTC.
- Every foreign key, unique, check, and NOT NULL constraint identified.
- High-volume tables have a growth estimate and an index/partition plan.

**Tests.** Schema review against realistic volume estimates; query plans sketched for the
top 20 expected queries against realistic — not empty — data.

**Security checks.** Personal data columns classified. Runtime database user holds no DDL
rights in production. Audit tables designed append-only.

**Documentation requirements.** `docs/database/erd.md`, `docs/database/data-dictionary.md`,
`docs/database/retention.md`.

**Completion gate.** ERD approved; data classification complete; retention rules defined
for every table holding personal data.

---

# PHASE 5 — API and OpenAPI

**Objective.** Specify the complete public and administrative API before implementing it.

**Dependencies.** Phase 4 COMPLETE.

**Major tasks.**
- Write the OpenAPI document covering every endpoint for subscriber clients and the
  Admin Control Center.
- Define versioning, pagination, filtering, sorting, and the standard error envelope with
  stable machine-readable codes.
- Define authentication, required permission, and rate limit per endpoint.
- Define the playback-authorization API contract precisely — it is the contract every
  client depends on and the hardest to change later.
- Define webhook contracts (payments, ingest) with idempotency and signature verification.
- Set up specification linting and contract-test tooling.

**Acceptance criteria.**
- OpenAPI document validates and lints clean.
- Every Phase 2 functional requirement maps to at least one endpoint.
- Every endpoint declares auth, permission, rate limit, and error codes.
- Error shapes are uniform; no endpoint invents its own.
- No endpoint returns personal data beyond what its purpose requires.

**Tests.** Specification validation in CI; contract-test harness scaffolded and proven
against a stub server.

**Security checks.** No endpoint exposes internal identifiers that enable enumeration.
Every collection endpoint is scoped by the caller's authorization. Admin and subscriber
surfaces are separated.

**Documentation requirements.** `docs/api/openapi.yaml`, `docs/api/conventions.md`,
`docs/api/errors.md`.

**Completion gate.** Specification validates, lints clean, covers 100% of requirements,
and is reviewed. It becomes authoritative from this point (`CLAUDE.md` §10).

---

# PHASE 6 — Security and threat model

**Objective.** Model the threats and define the controls **before** writing code, not
after an incident.

**Dependencies.** Phases 3–5 COMPLETE.

**Major tasks.**
- Build the threat model over the Phase 3 trust boundaries (STRIDE or equivalent).
- Model the domain-specific threats explicitly: credential sharing, session hijacking,
  token replay, concurrency-limit evasion, geo-restriction evasion, manifest URL sharing,
  stream leeching, subscription fraud, chargeback abuse, admin privilege escalation.
- Define the control set and map every threat to at least one control.
- Define secret management, key rotation, and the certificate strategy.
- Define the security testing strategy and CI security gates.
- Define incident response and the vulnerability disclosure process.

**Acceptance criteria.**
- Every trust boundary has an entry in the threat model.
- Every identified threat maps to a control, an accepted risk with rationale, or a
  deferral with a date.
- Secret management strategy allows rotation with no code change.
- CI security gates defined and agreed as blocking.

**Tests.** Threat model review; each control has a planned verification test named and
assigned to a phase.

**Security checks.** This phase *is* the security check. Its output must be reviewed by
someone who did not write it.

**Documentation requirements.** `docs/security/threat-model.md`,
`docs/security/controls.md`, `docs/security/secret-management.md`,
`docs/security/incident-response.md`.

**Completion gate.** Threat model reviewed and signed off; no threat left unmapped;
CI security gates specified.

---

# PHASE 7 — Backend foundation

**Objective.** Stand up the Laravel application skeleton, local infrastructure, and the
full CI pipeline — the machinery every later phase depends on.

**Dependencies.** Phases 3–6 COMPLETE. **Resolve blockers B-001/002/003** (Docker daemon,
PostgreSQL, Redis) and decisions D-007, D-008, D-010.

**Major tasks.**
- Verify the PHP version supported by Laravel 12 and every intended package **against
  official documentation** before pinning (`CLAUDE.md` §1 — do not assume).
- Install missing PHP extensions, including `bcmath`.
- Create the Laravel 12 application with the Phase 3 module structure enforced by
  directory boundaries and an automated boundary check.
- Configure PostgreSQL and Redis; establish the migration framework and base migrations.
- Establish configuration and secret loading; commit `.env.example` with no real values.
- Configure structured logging, correlation IDs, health and readiness endpoints.
- Configure the queue system with dead-letter handling.
- Stand up the full CI pipeline: format, static analysis, unit, integration, contract,
  dependency scan, secret scan, license check — all blocking.
- Add `.gitattributes`; set `origin/HEAD`; record static analysis level and coverage
  threshold in `PROJECT_STATE.md`.

**Acceptance criteria.**
- Application boots, connects to PostgreSQL and Redis, serves health and readiness.
- Migrations run forward and roll back cleanly.
- Module boundary violations fail the build automatically.
- CI runs green with every gate enabled and blocking.
- No secret in the repository; `.env.example` contains no real values.

**Tests.** Unit and integration harnesses operational against a **real** database and
cache. Contract-test harness runs against the OpenAPI specification. A deliberate failure
is verified to fail the build.

**Security checks.** Secret scan and dependency scan pass. Debug mode off outside local.
Error responses leak no internals. Security headers present. Database user least-privileged.

**Documentation requirements.** `docs/operations/local-setup.md`,
`docs/operations/ci.md`, `docs/architecture/module-boundaries.md`.

**Completion gate.** Green CI with all gates blocking; boundary enforcement automated;
static analysis level and coverage threshold recorded; local environment reproducible
from documentation by a second person.

---

# PHASE 8 — Authentication and identity

**Objective.** Implement centralized authentication with no bypass, anywhere, ever.

**Dependencies.** Phase 7 COMPLETE.

**Major tasks.**
- Implement registration, login, logout, password reset, and email/phone verification.
- Implement token issuance with short-lived access tokens and rotating, revocable
  refresh tokens bound to device and session.
- Implement server-side revocation with a bounded, documented propagation interval.
- Implement MFA for administrative accounts.
- Implement rate limiting, lockout, and anti-enumeration on all identity endpoints.
- Implement Argon2id password hashing.
- Implement identity audit logging.

**Acceptance criteria.**
- All identity flows work per the OpenAPI specification.
- Tokens expire, rotate, and revoke as specified; revocation propagates within the
  documented interval, measured.
- Admin MFA enforced and cannot be disabled by a non-admin.
- No response or timing difference reveals whether an account exists.
- **No bypass mechanism exists on any branch** — verified by code search and review.

**Tests.** Unit: token lifecycle, hashing, validation. Integration: every flow end to end.
Security: expired token rejected, revoked token rejected, replayed refresh token rejected
and the chain invalidated, brute force locked out, enumeration attempts indistinguishable,
CSRF and fixation blocked.

**Security checks.** Threat model authentication controls verified individually. No
credential in any log. Reset tokens single-use and time-limited.

**Documentation requirements.** `docs/security/authentication.md`; OpenAPI updated;
`docs/operations/runbooks/account-recovery.md`.

**Completion gate.** All identity tests pass including the full negative suite; no bypass
path exists; audit logging verified; propagation interval measured and recorded.

---

# PHASE 9 — Users, profiles, devices and sessions

**Objective.** Implement the account model, RBAC, and the device/session control surface
that concurrency and revocation depend on.

**Dependencies.** Phase 8 COMPLETE.

**Major tasks.**
- Implement accounts, profiles (including children's profiles with stricter defaults),
  and per-profile preferences.
- Implement device registration with device class, fingerprinting policy, and limits.
- Implement session management: list, inspect, terminate — for both user and admin.
- Implement RBAC: named permissions, roles as data, centralized policy evaluation.
- Implement the privacy surface: data export and deletion (`CLAUDE.md` §18).

**Acceptance criteria.**
- Device limits enforced server-side with defined behaviour at the limit.
- Terminating a session invalidates its tokens within the documented interval.
- Every admin capability is a named permission; no scattered inline checks.
- A user cannot grant themselves a role, read another account's data, or alter another
  account's devices.
- Data export and deletion work and are tested, including their effect on analytics.

**Tests.** Unit: policy evaluation, device-limit logic. Integration: full lifecycle per
entity. Security: horizontal privilege escalation (other users' data), vertical
escalation (self-promotion), device-limit bypass attempts, session-termination
effectiveness. Privacy: deletion completeness verified.

**Security checks.** Every endpoint authorization-tested with an unauthorized caller.
Device fingerprints treated as personal data. Audit logging on all privileged actions.

**Documentation requirements.** `docs/architecture/identity-model.md`,
`docs/security/rbac.md`, `docs/privacy/data-subject-requests.md`.

**Completion gate.** Full authorization negative suite passes; escalation tests fail to
escalate; privacy paths verified.

---

# PHASE 10 — Channels

**Objective.** Implement the live channel catalogue and its stream references.

**Dependencies.** Phase 9 COMPLETE.

**Major tasks.**
- Implement channels, channel groups, logos, numbering, and ordering.
- Implement stream sources with **recorded provenance and rights basis** — ingestion
  without a rights basis is rejected by design (`CLAUDE.md` §2.3).
- Implement channel availability by region, device class, and package.
- Implement channel administration and bulk operations with validation.
- Implement channel listing and search endpoints with caching.

**Acceptance criteria.**
- Channels are creatable, editable, orderable, and retrievable per the specification.
- **A stream source cannot be created without provenance and a rights reference.**
- Channel listings are filtered by the caller's context server-side.
- Listing endpoints meet their latency budget with a realistic catalogue size.

**Tests.** Unit: ordering, availability rules. Integration: CRUD and listing. Contract:
against OpenAPI. Performance: listing at target catalogue size. Security: a caller sees
only channels they may see, verified negatively.

**Security checks.** Stream URLs and source credentials never returned to subscriber
clients. Admin operations permission-gated and audited.

**Documentation requirements.** `docs/architecture/catalogue.md`;
`docs/operations/runbooks/channel-onboarding.md`; OpenAPI updated.

**Completion gate.** Provenance enforcement proven by a failing-creation test; no stream
credential reachable from a subscriber-facing response.

---

# PHASE 11 — EPG

**Objective.** Implement electronic programme guide ingestion, storage, and query — the
foundation catch-up and restart depend on.

**Dependencies.** Phase 10 COMPLETE.

**Major tasks.**
- Implement EPG ingestion from agreed sources with format adapters behind an abstraction.
- Implement normalization: timezones to UTC, DST handling, overlap and gap resolution,
  deduplication.
- Implement programme storage with partitioning and retention.
- Implement now/next, grid, and programme-detail queries with caching.
- Implement freshness monitoring and stale-data alerting.
- Implement backfill and correction handling for retrospectively amended schedules.

**Acceptance criteria.**
- Ingestion is idempotent — re-ingesting the same source changes nothing.
- All times stored in UTC; DST transitions handled correctly, proven by test.
- Overlaps and gaps are detected and resolved by a defined, documented rule.
- Grid queries meet their latency budget at realistic channel × day volume.
- Ingest freshness is monitored and alerts when stale.

**Tests.** Unit: normalization, DST boundaries, overlap resolution. Integration:
ingestion, re-ingestion idempotency, correction handling. Performance: grid query at
target volume. Data quality: gap and overlap detection on real-shaped data.

**Security checks.** Ingested EPG data is untrusted input — validated and sanitized
before storage and before rendering. Ingestion endpoints authenticated. Malformed feeds
fail safely without corrupting existing data.

**Documentation requirements.** `docs/architecture/epg.md`,
`docs/operations/runbooks/epg-ingest-failure.md`.

**Completion gate.** Idempotency proven; DST tests pass; freshness monitoring live;
a corrupt feed is proven not to damage stored data.

---

# PHASE 12 — Packages and subscriptions

**Objective.** Implement the commercial product model — the input to entitlement.

**Dependencies.** Phase 11 COMPLETE.

**Major tasks.**
- Implement packages, tiers, and package-to-content mapping.
- Implement subscription lifecycle: trial, active, past-due, suspended, cancelled,
  expired — with explicit, tested state transitions.
- Implement per-package concurrency and device-limit allowances.
- Implement pricing, currency, and tax representation (integer minor units or `NUMERIC`).
- Implement upgrade, downgrade, and proration rules.
- Implement subscription history as an append-only record.

**Acceptance criteria.**
- The subscription state machine is explicit; every transition is tested, including
  invalid transitions which must be rejected.
- Money handling is exact — no floating point anywhere in the path.
- Package changes take effect per the defined rules and are fully auditable.
- Concurrency allowances are readable by the entitlement engine.

**Tests.** Unit: state machine including every invalid transition; proration arithmetic
against worked examples. Integration: full lifecycle. Property/edge: boundary dates,
leap years, month-end anniversaries, timezone boundaries.

**Security checks.** A subscriber cannot alter their own package, price, or state.
All commercial changes audited with actor and before/after state.

**Documentation requirements.** `docs/architecture/commercial-model.md`,
`docs/architecture/subscription-states.md`.

**Completion gate.** State machine fully tested including negatives; monetary arithmetic
verified against worked examples; no self-service privilege escalation.

---

# PHASE 13 — Rights management

**Objective.** Implement the rights system that makes lawful distribution enforceable and
provable. **This is a compliance system, not a feature.**

**Dependencies.** Phase 12 COMPLETE.

**Major tasks.**
- Implement rights agreements: holder, contract reference, territories, windows,
  device classes, distribution modes (live / catch-up / restart / VOD / download),
  concurrency limits.
- Implement rights attachment to channels, VOD titles, series, and episodes.
- Implement automatic activation at window start and **automatic disabling at window
  end**, by scheduled job **and** by re-check at authorization — both mechanisms
  required (`CLAUDE.md` §2.1).
- Implement rights conflict detection and overlap validation.
- Implement the expiry-warning surface and operator alerting.
- Implement the rights audit trail.

**Acceptance criteria.**
- Every distributable asset carries rights metadata or is not distributable.
- **Expired rights make content unavailable automatically**, proven by test at the
  window boundary.
- Both enforcement mechanisms are independently verified — disabling the scheduled job
  must not permit expired content to play.
- The system answers, for any asset: rights holder, basis, territories, window, modes.
- All rights changes are audited with contract reference.

**Tests.** Unit: window evaluation at boundaries, territory matching, mode matching.
Integration: activation and expiry via both mechanisms independently. Negative: expired
content unavailable; out-of-territory unavailable; wrong-mode unavailable; wrong device
class unavailable. Time-travel: correct behaviour at, immediately before, and immediately
after every boundary.

**Security checks.** Rights cannot be modified without an audited privileged action.
No code path serves content lacking a rights basis — verified by review and by test.

**Documentation requirements.** `docs/legal/rights-model.md`,
`docs/operations/runbooks/rights-expiry.md`, `docs/legal/compliance-evidence.md`.

**Completion gate.** Dual-mechanism expiry proven independently; every negative rights
test passes; compliance evidence can be produced for any asset on demand.

---

# PHASE 14 — Entitlement engine

**Objective.** Implement the single authority that decides what a given subscriber may
watch, right now.

**Dependencies.** Phase 13 COMPLETE.

**Major tasks.**
- Implement entitlement evaluation combining subscription state, package mapping, rights
  windows, territory, device class, profile restrictions, and parental controls.
- Implement deny reasons as stable, machine-readable codes.
- Implement entitlement caching with **immediate** invalidation on any input change —
  a stale entitlement is a compliance failure, not a performance trade-off
  (`CLAUDE.md` §19).
- Implement bulk evaluation for catalogue listings.
- Implement entitlement decision logging for compliance evidence.

**Acceptance criteria.**
- One engine; no component makes its own entitlement decision.
- Every denial returns a specific, stable reason code.
- Changing any input (subscription lapse, rights expiry, territory change, device
  change) is reflected **immediately**, proven by test.
- Bulk evaluation meets the catalogue-listing latency budget.
- Decisions are logged with enough detail to prove compliance, and no credentials.

**Tests.** Unit: the full decision matrix across every input combination, including all
denial paths. Integration: cache invalidation on every input change. Performance: bulk
evaluation at catalogue scale. **Negative suite is the primary deliverable** —
unentitled, expired, out-of-territory, wrong-device, suspended, over-limit, and
parentally-blocked all denied with the correct code.

**Security checks.** Entitlement cannot be influenced by client-supplied data.
Client-reported territory and device class are never authoritative. No default-allow
branch exists anywhere in the engine.

**Documentation requirements.** `docs/architecture/entitlements.md`,
`docs/api/deny-reason-codes.md`.

**Completion gate.** Full decision matrix tested; immediate invalidation proven for every
input; no default-allow path exists, verified by review and mutation testing.

---

# PHASE 15 — Playback authorization

**Objective.** Implement the gate between a subscriber and a stream. **The most
security-critical component in the platform.**

**Dependencies.** Phase 14 COMPLETE.

**Major tasks.**
- Implement the playback authorization endpoint: entitlement evaluation, concurrency
  check, device validation, session binding, token issuance.
- Implement short-lived, single-purpose, session- and device-bound, revocable playback
  tokens.
- Implement server-side concurrency enforcement per subscription and per rights
  agreement, with defined and tested behaviour at the limit.
- Implement heartbeat/keepalive with server-side session expiry for abandoned sessions.
- Implement signed manifest and segment URL generation with short expiry.
- Implement edge-side token validation (`CLAUDE.md` §12 — validation at the edge, not
  only at issuance).
- Implement server-side geo-enforcement where rights require it.
- Implement playback authorization audit logging.

**Acceptance criteria.**
- **Possessing a manifest URL does not permit playback** — proven by test.
- Tokens are short-lived, bound, single-purpose, and revocable; revocation stops playback
  within the documented interval, measured.
- Concurrency limits enforced server-side with defined behaviour at the limit.
- Abandoned sessions release their concurrency slot within a bounded interval.
- Geo-enforcement is server-side; client-reported location is ignored.
- Every decision is logged with sufficient compliance detail and zero credentials.

**Tests.** Unit: token generation, binding, expiry. Integration: full authorization flow;
heartbeat and abandonment. **Security (the core deliverable):** token replay from another
device rejected; token replay from another session rejected; expired token rejected;
revoked token rejected; concurrency limit not exceedable including by race; manifest URL
sharing does not enable playback; signed URL tampering rejected; expired signed URL
rejected; geo-spoofing via client input has no effect. Load: authorization endpoint at
peak-event concurrency.

**Security checks.** Every threat-model playback threat verified individually.
No token or key in any log. Rate limiting on the authorization endpoint. Race conditions
on concurrency counting tested under genuine parallelism, not sequentially.

**Documentation requirements.** `docs/security/playback-authorization.md`,
`docs/api/playback-contract.md`, `docs/operations/runbooks/playback-failures.md`.

**Completion gate.** **Every playback security test passes.** Concurrency proven safe
under race conditions. Revocation interval measured and within specification. This gate
does not bend — clients depend on this contract and cannot be safely built before it
settles.

---

# PHASE 16 — Streaming infrastructure

**Objective.** Implement ingest, transcoding, and packaging.

**Dependencies.** Phase 15 COMPLETE. **Resolve blocker B-008** (FFmpeg, with license
review per `CLAUDE.md` §3.2 **before** installation).

**Major tasks.**
- Review and record the FFmpeg build's license and enabled codecs; reject nonfree builds.
- Implement the FFmpeg abstraction with argument-vector invocation — never shell strings.
- Implement live ingest with input validation, monitoring, and failover.
- Implement transcoding ladders per device class, with a recorded rationale per rung.
- Implement HLS packaging; implement DASH/CMAF where the Phase 3 ADR requires it,
  sharing CMAF segments rather than duplicating storage.
- Implement job orchestration: timeouts, bounded retries, failure states, operator
  visibility.
- Implement segment storage lifecycle and cleanup.
- Implement stream health monitoring and alerting.

**Acceptance criteria.**
- Ingest, transcode, and package produce playable output on every target platform.
- Manifest generation is deterministic and unit-testable.
- Every job has a timeout, a bounded retry policy, a terminal failure state, and
  operator-visible status.
- Media processing never runs on the API request path.
- FFmpeg licensing reviewed and recorded before production use.

**Tests.** Unit: ladder selection, manifest generation, argument construction. Integration:
end-to-end ingest → package → playable output. Failure: input loss, encoder crash, disk
exhaustion, job timeout. Security: command injection attempts through every operator- and
metadata-supplied field.

**Security checks.** **No unvalidated input reaches an FFmpeg argument.** No shell string
construction anywhere. Ingest endpoints authenticated. Media workers run least-privileged
and isolated. Storage not publicly listable.

**Documentation requirements.** `docs/architecture/streaming.md`,
`docs/legal/ffmpeg-license-review.md`, `docs/operations/runbooks/ingest-failure.md`.

**Completion gate.** Playable output verified on every target platform; command-injection
suite passes; FFmpeg license reviewed and recorded; no media work on the request path.

---

# PHASE 17 — Origin and CDN

**Objective.** Implement delivery: a protected origin behind a CDN abstraction.

**Dependencies.** Phase 16 COMPLETE.

**Major tasks.**
- Implement the origin with authenticated CDN-only access and no public reachability.
- Implement the CDN abstraction, validated against two real candidate providers.
- Implement signed URL / signed cookie generation and edge validation.
- Implement cache-control strategy per asset type and purge/invalidation.
- Implement origin shielding and failover.
- Implement delivery monitoring: cache hit ratio, origin egress, error rates.

**Acceptance criteria.**
- **Origin is unreachable from the public internet**, verified by test.
- Segment delivery never traverses the application tier.
- Signed URLs expire and tamper-detect correctly.
- CDN provider can be swapped without touching domain code.
- Cache invalidation is verified for content and rights changes.

**Tests.** Integration: delivery via CDN; purge effectiveness. Security: direct origin
access blocked; signed URL tampering rejected; expired signature rejected; hotlinking
blocked. Performance: cache hit ratio at target; time-to-first-byte within budget.
Failure: origin unavailable, CDN degraded.

**Security checks.** Origin access controls verified from an external network position.
No credential in a CDN configuration committed to the repository. Signing keys rotatable
without redeployment.

**Documentation requirements.** `docs/architecture/delivery.md`,
`docs/operations/runbooks/cdn-failure.md`, `docs/operations/runbooks/origin-failure.md`.

**Completion gate.** External test confirms origin is unreachable; signed URL security
suite passes; provider swap demonstrated at the abstraction level.

---

# PHASE 18 — Admin Control Center

**Objective.** Build the operator's control surface for content, rights, subscribers,
and platform health.

**Dependencies.** Phase 17 COMPLETE.

**Major tasks.**
- Implement admin authentication with enforced MFA and RBAC-gated navigation.
- Implement content management: channels, EPG, VOD, series, packages.
- Implement **rights management UI** including expiry warnings and conflict surfacing.
- Implement subscriber management: accounts, subscriptions, devices, sessions, support
  actions.
- Implement platform health dashboards and the audit log viewer.
- Implement bulk operations with validation, preview, and confirmation.

**Acceptance criteria.**
- Every admin action is permission-gated server-side, never only in the UI.
- Every admin action is audited with actor, timestamp, and before/after state.
- Destructive and bulk operations require explicit confirmation and are reversible or
  clearly marked irreversible.
- Rights expiry is visible early enough for the operator to act.
- No subscriber credential or payment detail is ever displayed.

**Tests.** Unit: permission gating. Integration: every admin workflow. Security: each
admin endpoint attempted with insufficient permission and with no permission; audit
completeness verified. E2E: critical operator journeys.

**Security checks.** Admin surface is network- and auth-separated from subscriber APIs.
MFA cannot be bypassed. Session timeout enforced. No mass-export of personal data without
a specific permission and an audit record.

**Documentation requirements.** `docs/operations/admin-guide.md`,
`docs/security/admin-access.md`.

**Completion gate.** Full admin authorization negative suite passes; audit completeness
verified; no credential or payment data reachable through any admin view.

---

# PHASE 19 — Web TV

**Objective.** Deliver the browser client.

**Dependencies.** Phase 15 COMPLETE (Phase 18 recommended). **Resolve D-009** (web
framework), driven by TV-browser constraints, not desktop convenience.

**Major tasks.**
- Implement the application shell, routing, authentication, and profile selection.
- Implement Live TV with the EPG grid, VOD browse, search, and detail pages.
- Implement the player: adaptive playback, subtitles, audio tracks, quality selection.
- Implement playback authorization integration and error handling by deny-reason code.
- Implement responsive layout, keyboard navigation, and accessibility.
- Implement client-side QoE telemetry.

**Acceptance criteria.**
- All specified journeys work on every supported browser at its minimum version.
- **The client renders only what the backend authorizes** — no client-side entitlement
  logic.
- Every deny-reason code maps to a clear, non-technical user message.
- Startup and navigation meet their performance budgets on the minimum device class.
- Accessibility requirements met; keyboard navigation complete.

**Tests.** Unit: components and state. Integration: API contract adherence. E2E: critical
journeys per supported browser. Performance: startup, navigation, time-to-first-frame.
Security: no token in `localStorage` where a more secure option exists; no sensitive data
in client logs; XSS-resistant rendering.

**Security checks.** CSP enforced. No secret in the bundle. Tokens stored per the Phase 6
strategy. Third-party scripts reviewed and minimized.

**Documentation requirements.** `docs/clients/web.md`; attribution/notices screen
implemented per `CLAUDE.md` §3.2.

**Completion gate.** E2E green on every supported browser at minimum version; performance
budgets met on the minimum device; no entitlement logic in the client, verified by review.

---

# PHASE 20 — Android

**Objective.** Deliver the Android phone/tablet client.

**Dependencies.** Phase 15 COMPLETE. **Resolve blocker B-005** (Android SDK).

**Major tasks.**
- Install and configure the Android SDK; establish the Kotlin + Gradle build.
- Implement authentication, profiles, catalogue browse, search, and detail.
- Implement the player on AndroidX Media3 with adaptive streaming, subtitles, and audio
  tracks.
- Implement playback authorization integration, background/foreground handling, and
  audio focus.
- Implement offline-capable UI states and network-change resilience.
- Implement QoE telemetry and crash reporting.
- Establish signing, versioning, and release pipeline.

**Acceptance criteria.**
- Works on the minimum supported Android version and device class.
- Playback survives network transitions, interruptions, and backgrounding.
- Deny-reason codes surface as clear user messages.
- No entitlement logic on the client.
- Startup and playback budgets met on the minimum device.

**Tests.** Unit: view models and domain logic. Integration: API contract. Instrumented:
critical journeys. Playback: adaptive switching, seek, subtitle and audio switching,
interruption recovery. Security: no secret in the APK; certificate pinning per the
Phase 6 strategy; tokens in secure storage.

**Security checks.** APK inspected for embedded secrets. Debug logging stripped from
release builds. Signing keys stored outside the repository. Third-party SDK data
collection reviewed (`CLAUDE.md` §18).

**Documentation requirements.** `docs/clients/android.md`,
`docs/operations/release-android.md`; attribution screen.

**Completion gate.** Instrumented tests green on the minimum device; APK secret scan
clean; release pipeline produces a signed, installable build.

---

# PHASE 21 — Android TV

**Objective.** Deliver the Android TV / Google TV client — a distinct product, not a
resized phone app.

**Dependencies.** Phase 20 COMPLETE.

**Major tasks.**
- Implement the leanback/TV UI with D-pad navigation and focus management.
- Implement the TV-optimized EPG grid, channel surfing, and zapping.
- Implement the Media3 player tuned for TV, including surface handling and HDMI events.
- Implement TV-specific integration: recommendations channel, voice search where
  specified.
- Optimize aggressively for low-memory TV devices.

**Acceptance criteria.**
- Fully navigable by D-pad alone; focus is never lost or trapped.
- Channel-change time meets its budget on the minimum TV device.
- Memory footprint stays within the minimum device's constraints under sustained use.
- Overscan-safe layout; readable at typical viewing distance.

**Tests.** Instrumented on TV device profiles. Navigation: exhaustive D-pad traversal.
Performance: channel change, EPG scroll, memory under sustained playback. Playback:
long-duration soak test with resolution changes.

**Security checks.** As Phase 20. Additionally: no sensitive data on a shared-screen
device without explicit action.

**Documentation requirements.** `docs/clients/android-tv.md`.

**Completion gate.** D-pad traversal complete with no focus traps; memory and
channel-change budgets met on the **minimum** TV device, not a development box.

---

# PHASE 22 — iOS/iPadOS

**Objective.** Deliver the Apple client.

**Dependencies.** Phase 15 COMPLETE. **Resolve blocker B-004 — structural.** Swift and
Xcode cannot run on this Linux environment. macOS hardware or a hosted macOS CI runner
must be procured **before this phase can begin**.

**Major tasks.**
- Procure and configure the macOS build environment and CI runner.
- Implement authentication, profiles, catalogue, search, and detail in Swift.
- Implement the player on AVFoundation with adaptive playback, subtitles, and audio
  tracks.
- Implement playback authorization integration, backgrounding, PiP, and AirPlay per the
  rights model — **AirPlay and casting availability must respect rights metadata**.
- Implement QoE telemetry and crash reporting.
- Establish signing, provisioning, and App Store release pipeline.

**Acceptance criteria.**
- Works on the minimum supported iOS/iPadOS version and device.
- Playback survives interruptions, backgrounding, and route changes.
- PiP and AirPlay behave per the rights model, including being disabled where rights
  forbid.
- No entitlement logic on the client.
- App Store review guidelines satisfied.

**Tests.** Unit: view models and domain logic. UI: critical journeys. Playback: adaptive
switching, interruption, route change, PiP transitions. Security: no secret in the
bundle; Keychain used for tokens; pinning per Phase 6.

**Security checks.** Bundle inspected for embedded secrets. Debug logging stripped from
release. Signing assets outside the repository. Third-party SDK collection reviewed.

**Documentation requirements.** `docs/clients/ios.md`,
`docs/operations/release-ios.md`; attribution screen.

**Completion gate.** macOS build environment operational and reproducible; UI tests green
on the minimum device; bundle secret scan clean; signed build produced by CI, not by hand.

---

# PHASE 23 — Samsung Tizen

**Objective.** Deliver the Samsung Smart TV client.

**Dependencies.** Phase 19 COMPLETE (shares web technology). **Resolve blocker B-006** —
Tizen Studio plus Samsung developer registration, which has lead time and must be started
early.

**Major tasks.**
- Install Tizen Studio; complete Samsung developer registration and certificates.
- Implement the Tizen application shell, remote-control key handling, and focus management.
- Implement playback using the platform AVPlay/media APIs.
- Implement platform integration: app lifecycle, deep links, exit behaviour.
- Optimize for constrained TV hardware across the supported model-year range.
- Establish the Samsung Seller Office submission pipeline.

**Acceptance criteria.**
- Works across the specified Tizen version and model-year range.
- Remote-control navigation complete; no focus traps; correct back/exit behaviour.
- Playback, subtitles, and audio-track switching work on real hardware.
- Performance budgets met on the **oldest** supported model — the binding constraint.
- Platform certification requirements satisfied.

**Tests.** Manual and automated on real Samsung hardware across model years — emulator
results are not sufficient evidence for this platform. Navigation, playback, lifecycle,
and memory under sustained use.

**Security checks.** No secret in the package. Platform-appropriate secure token storage.
Content-protection requirements of the platform respected.

**Documentation requirements.** `docs/clients/tizen.md`,
`docs/operations/release-tizen.md`; attribution screen.

**Completion gate.** Verified on real hardware including the oldest supported model;
certification requirements met; submission pipeline proven.

---

# PHASE 24 — LG webOS

**Objective.** Deliver the LG Smart TV client.

**Dependencies.** Phase 19 COMPLETE. **Resolve blocker B-006** — webOS TV SDK plus LG
developer registration.

**Major tasks.**
- Install the webOS TV SDK; complete LG developer registration.
- Implement the webOS application shell, Magic Remote **and** directional-key handling —
  both input models must work.
- Implement playback using the platform media APIs.
- Implement platform integration: lifecycle, deep links, exit behaviour.
- Optimize across the supported webOS version and model-year range.
- Establish the LG Content Store submission pipeline.

**Acceptance criteria.**
- Works across the specified webOS version and model-year range.
- Both Magic Remote pointer and directional-key navigation are fully supported.
- Playback, subtitles, and audio-track switching work on real hardware.
- Performance budgets met on the oldest supported model.
- Platform certification requirements satisfied.

**Tests.** Manual and automated on real LG hardware across model years. Navigation under
both input models, playback, lifecycle, and memory under sustained use.

**Security checks.** No secret in the package. Platform-appropriate secure token storage.
Content-protection requirements respected.

**Documentation requirements.** `docs/clients/webos.md`,
`docs/operations/release-webos.md`; attribution screen.

**Completion gate.** Verified on real hardware including the oldest supported model; both
input models complete; certification requirements met.

---

# PHASE 25 — VOD

**Objective.** Implement video on demand: movies, series, and their delivery.

**Dependencies.** Phases 16, 17 COMPLETE; clients per platform.

**Major tasks.**
- Implement the VOD catalogue: movies, series, seasons, episodes, collections, genres.
- Implement VOD ingest, transcode, and packaging with rights metadata attached.
- Implement metadata, artwork, trailers, and cast/crew.
- Implement resume points, watchlist, and continue-watching across profiles and devices.
- Implement search and browse over the VOD catalogue.
- Implement per-title availability windows via the rights system.

**Acceptance criteria.**
- VOD titles are ingestable, playable, and searchable across all supported clients.
- **Availability windows are enforced by the rights system**, not by catalogue flags.
- Resume points sync across devices with defined conflict resolution.
- Series navigation (next episode, season selection) works per specification.
- Catalogue browse meets its latency budget at target catalogue size.

**Tests.** Unit: series ordering, resume-point logic and conflict resolution. Integration:
ingest to playback. Performance: browse and search at target scale. Negative: title
outside its window is not playable; out-of-territory title is not playable.

**Security checks.** VOD assets not directly reachable without playback authorization.
Resume points and watchlists scoped to the profile and not readable across accounts.

**Documentation requirements.** `docs/architecture/vod.md`,
`docs/operations/runbooks/vod-ingest.md`.

**Completion gate.** Rights-based window enforcement proven for VOD; cross-device resume
verified; negative availability tests pass.

---

# PHASE 26 — Catch-up and Restart TV

**Objective.** Implement time-shifted viewing of live programming.

**Dependencies.** Phases 11, 13, 16 COMPLETE.

**Major tasks.**
- Implement rolling DVR recording with a retention window per channel.
- Implement EPG-to-recording alignment, including handling of schedule inaccuracy and
  retrospective corrections.
- Implement catch-up playback with programme-boundary trimming and padding.
- Implement restart-from-start for in-progress live programmes.
- Implement **per-programme catch-up and restart rights**, which are frequently narrower
  than the live rights for the same channel.
- Implement storage lifecycle and automatic cleanup at retention expiry.

**Acceptance criteria.**
- Catch-up availability is governed by rights, per programme, and per distribution mode.
- **A programme with live rights but without catch-up rights is not available on
  catch-up** — proven by test. This is the single most common compliance failure in
  time-shift features.
- Restart works for in-progress programmes where rights permit.
- Retention is enforced automatically; expired recordings are removed and unplayable.
- EPG inaccuracy degrades gracefully — the programme boundary is approximate, never
  a playback failure.

**Tests.** Unit: boundary alignment, padding, retention. Integration: record → catch-up
playback; restart during live. Negative: no catch-up rights → denied; expired retention →
denied; out-of-window → denied. Data quality: behaviour under inaccurate and corrected EPG.

**Security checks.** Recordings not reachable without playback authorization. Retention
deletion verified as actual deletion, including from any cache or CDN.

**Documentation requirements.** `docs/architecture/timeshift.md`,
`docs/legal/timeshift-rights.md`.

**Completion gate.** Mode-specific rights enforcement proven; retention deletion verified
end to end including CDN purge.

---

# PHASE 27 — Payments

**Objective.** Implement billing through a provider, without payment data touching KMS TV
servers.

**Dependencies.** Phase 12 COMPLETE. Requires PHP `bcmath` (K-004).

**Major tasks.**
- Select the payment provider and record the ADR.
- Implement the payment abstraction validated against two candidate providers.
- Implement provider-hosted or tokenized checkout — **PAN, CVV, and track data never
  reach our systems** (`CLAUDE.md` §6.2).
- Implement subscription billing: initial, recurring, proration, retries, dunning.
- Implement webhook handling with signature verification and idempotency.
- Implement refunds, chargebacks, and failed-payment subscription state transitions.
- Implement in-app purchase integration where app store policy requires it.
- Implement financial reconciliation and reporting.

**Acceptance criteria.**
- No card data is stored, logged, or transmitted through KMS TV systems — verified.
- Webhooks are signature-verified and idempotent; replay causes no double effect.
- Failed payments drive subscription state per the Phase 12 state machine.
- Monetary arithmetic is exact; reconciliation balances against provider records.
- Refunds and chargebacks are handled with correct entitlement consequences.

**Tests.** Unit: proration, retry schedules, state transitions. Integration: full billing
lifecycle against provider sandbox. Idempotency: duplicate and out-of-order webhooks.
Security: forged webhook rejected; replayed webhook has no effect; amount tampering
rejected. Reconciliation: ledger balances against provider reports.

**Security checks.** PCI scope confirmed minimal and documented. No card data in any log
or error path. Webhook endpoints authenticated by signature, not by obscurity. Refund
capability permission-gated and audited.

**Documentation requirements.** `docs/architecture/payments.md`,
`docs/security/pci-scope.md`, `docs/operations/runbooks/payment-failures.md`.

**Completion gate.** Card data absence verified by audit; webhook security suite passes;
reconciliation balances; PCI scope documented.

---

# PHASE 28 — Notifications

**Objective.** Implement subscriber and operator notifications.

**Dependencies.** Phases 9, 12 COMPLETE.

**Major tasks.**
- Implement the notification abstraction across push, email, and in-app.
- Implement templating, localization, and per-channel preferences with opt-out.
- Implement transactional notifications: account, subscription, payment, security alerts.
- Implement operator/editorial notifications with targeting and scheduling.
- Implement delivery tracking and bounce/failure handling.

**Acceptance criteria.**
- Preferences and opt-outs are honoured — for marketing notifications absolutely, with
  security-critical notifications explicitly classified as non-optional and documented
  as such.
- Templates are localized for every supported locale.
- Delivery failures are visible and retried within a bounded policy.
- No personal or sensitive data appears in a push payload.

**Tests.** Unit: templating, targeting, preference resolution. Integration: delivery per
channel against sandbox providers. Negative: opted-out user receives no marketing message.
Localization: every template renders in every locale without truncation or missing keys.

**Security checks.** Push payloads carry no sensitive data — device lock screens are
public surfaces. Notification endpoints rate-limited. No mass-send capability without a
specific permission, confirmation, and audit record.

**Documentation requirements.** `docs/architecture/notifications.md`,
`docs/privacy/communication-preferences.md`.

**Completion gate.** Opt-out enforcement proven; no sensitive data in push payloads
verified; all locales render correctly.

---

# PHASE 29 — Analytics

**Objective.** Implement product and QoE analytics under the privacy constraints.

**Dependencies.** Phases 19–26 COMPLETE (clients emit the events).

**Major tasks.**
- Define the event taxonomy with an explicit purpose per event.
- Implement client and server event collection with **pseudonymous identifiers by
  default** (`CLAUDE.md` §18).
- Implement the ingestion pipeline, storage, and retention enforcement.
- Implement QoE analytics: startup time, rebuffer ratio, bitrate distribution, error
  rates by platform.
- Implement content analytics: viewership, concurrency, completion, churn signals.
- Implement operator dashboards and reporting.

**Acceptance criteria.**
- Every event has a documented purpose; events without one are not collected.
- Identifiers are pseudonymous by default; identity joins require a recorded decision.
- Retention is enforced automatically per category.
- QoE metrics are available per platform and per content type.
- Analytics deletion honours data-subject deletion requests.

**Tests.** Unit: event construction and validation. Integration: end-to-end ingestion.
Privacy: no personal data in event payloads beyond what is declared; deletion propagates
to analytics. Volume: pipeline sustains peak event rate.

**Security checks.** Analytics access permission-gated. No credential or token in an event
payload. Third-party analytics SDKs reviewed for collection scope and disclosed.

**Documentation requirements.** `docs/architecture/analytics.md`,
`docs/privacy/analytics-data.md`, `docs/analytics/event-taxonomy.md`.

**Completion gate.** Privacy review passed; deletion propagation verified; pipeline
sustains peak rate.

---

# PHASE 30 — DRM

**Objective.** Integrate a DRM provider into the DRM-ready architecture. **Integration
only — never circumvention** (`CLAUDE.md` §1, §12).

**Dependencies.** Phases 16, 17 COMPLETE and all client phases for the platforms in scope.

**Major tasks.**
- Select the DRM provider(s) per platform requirements; record the ADR.
- Implement the key-management and license-delivery abstraction.
- Implement content encryption in the packaging pipeline.
- Implement license acquisition in each client, per platform DRM system.
- Implement DRM policy driven by rights metadata: output protection, security level,
  offline licenses where permitted.
- Implement key rotation and license revocation.

**Acceptance criteria.**
- Protected content plays on every supported platform through legitimate license
  acquisition.
- **License policy is derived from rights metadata**, not hardcoded.
- Key rotation works without service interruption.
- License revocation takes effect within a documented interval.
- Unlicensed playback attempts fail cleanly with a clear user message.

**Tests.** Integration: license acquisition per platform. Negative: playback without a
license fails; expired license fails; revoked license fails; policy violations blocked.
Rotation: key rotation during active sessions.

**Security checks.** Keys never logged, never returned to clients outside the license
flow, never stored unencrypted. License endpoints authenticated and bound to playback
authorization. **No circumvention capability exists anywhere in the codebase or tooling** —
verified by review.

**Documentation requirements.** `docs/architecture/drm.md`,
`docs/legal/drm-compliance.md`, `docs/operations/runbooks/drm-failures.md`.

**Completion gate.** Licensed playback verified on every in-scope platform; all negative
tests pass; key handling audited; no circumvention path exists.

---

# PHASE 31 — Monitoring and observability

**Objective.** Make the platform observable enough to operate and to prove compliance.

**Dependencies.** Phases 7–30 as delivered; consolidated here.

**Major tasks.**
- Consolidate structured logging with correlation IDs across all services and clients.
- Implement metrics: RED per service plus domain metrics (concurrent streams,
  authorization outcomes, entitlement denials by reason, transcode queue depth, EPG
  freshness, rights expiring within 24 hours).
- Implement distributed tracing across API, workers, and external calls.
- Implement dashboards: platform health, playback success, entitlement denials, ingest
  status, payment outcomes.
- Implement alerting with an owner and a runbook per alert.
- Implement log retention and access control.

**Acceptance criteria.**
- Every service emits structured logs with correlation IDs that trace a request
  end to end.
- All defined domain metrics are collected and dashboarded.
- **Every alert has an owner and a runbook** — no exceptions; an alert without one is
  deleted or given one.
- No credential, token, or personal record appears in any log — verified by scan.
- An incident can be diagnosed from telemetry alone, proven by a game-day exercise.

**Tests.** Verification that each metric moves as expected under induced conditions.
Alert testing: each alert triggered deliberately and its runbook followed. Log scanning
for sensitive data.

**Security checks.** Log access permission-gated and audited. Sensitive-data scanning
automated in CI. Retention enforced.

**Documentation requirements.** `docs/operations/observability.md`,
`docs/operations/runbooks/` (complete set), `docs/operations/alerts.md`.

**Completion gate.** All alerts have runbooks; log sensitive-data scan clean; a game-day
exercise diagnoses an induced incident from telemetry alone.

---

# PHASE 32 — Load and performance testing

**Objective.** Prove the platform meets its Phase 2 non-functional requirements under
realistic load.

**Dependencies.** Phases 7–31 COMPLETE.

**Major tasks.**
- Build load models from the Phase 1 product model, including **the concurrency spike at
  the start of a popular live event** — the defining load characteristic of live TV, and
  the one average-load testing misses entirely.
- Load test the API, the playback authorization path, and the delivery path.
- Test the EPG grid, catalogue browse, and search at target scale.
- Test the ingest and transcode pipeline at target channel count.
- Perform soak testing for memory leaks and resource exhaustion.
- Perform capacity planning with documented headroom.
- Identify and fix bottlenecks; re-test.

**Acceptance criteria.**
- Every Phase 2 non-functional requirement is met under load, measured — not estimated.
- The live-event spike scenario passes at the specified concurrency.
- No memory leak or resource exhaustion under soak.
- Capacity plan documented with headroom targets and scaling triggers.
- Degradation under overload is graceful and defined, not collapse.

**Tests.** Load, stress, spike, soak, and breakpoint tests. Chaos: dependency failure
under load.

**Security checks.** Rate limiting holds under load. No information disclosure in
overload error paths. Authorization is not weakened or bypassed by any caching or
fast-path added for performance — **the most dangerous class of performance optimization**,
and it must be explicitly re-tested here.

**Documentation requirements.** `docs/performance/load-test-results.md`,
`docs/operations/capacity-plan.md`, `docs/performance/budgets.md`.

**Completion gate.** All NFRs met under load with evidence; spike scenario passes;
authorization correctness re-verified after every performance change.

---

# PHASE 33 — Security audit

**Objective.** Independently verify the security posture before production.

**Dependencies.** Phases 7–32 COMPLETE.

**Major tasks.**
- Conduct an internal security review against the Phase 6 threat model, control by control.
- Commission an **independent external penetration test** covering API, web, mobile, TV
  clients, and delivery.
- Audit authentication, authorization, entitlement, and playback authorization end to end.
- Audit secret management, key handling, and rotation.
- Audit third-party dependencies and licenses (`CLAUDE.md` §3).
- Audit logging and telemetry for sensitive-data leakage.
- Remediate findings; re-test.

**Acceptance criteria.**
- Every Phase 6 control is verified present and effective.
- All critical and high findings remediated and re-tested. Medium findings remediated or
  formally risk-accepted with a named owner and a date.
- No secret in any repository, artifact, image, or log.
- License compliance verified for every dependency; attribution complete in every client.
- Playback authorization holds against a determined attacker in the pen-test report.

**Tests.** Full security regression suite. Penetration test with a written report.
Automated scanning across code, dependencies, containers, and infrastructure.

**Security checks.** This phase *is* the security check. Its findings are not negotiable
against schedule.

**Documentation requirements.** `docs/security/audit-report.md`,
`docs/security/remediation-log.md`, `docs/legal/license-compliance-report.md`.

**Completion gate.** **Zero unremediated critical or high findings.** Every medium finding
has a decision with an owner and a date. External report received and reviewed.

---

# PHASE 34 — Disaster recovery

**Objective.** Prove the platform can be recovered, not merely that backups exist.

**Dependencies.** Phase 33 COMPLETE. **Resolve D-011** (numeric RPO/RTO).

**Major tasks.**
- Define and record numeric RPO and RTO targets.
- Implement automated encrypted backups with point-in-time recovery.
- Implement infrastructure as code so the environment is reproducible from scratch.
- **Perform restore drills** — full database restore, point-in-time restore, and full
  environment rebuild — and time each one.
- Write and rehearse runbooks: database loss, origin loss, CDN failure, queue backlog,
  credential compromise, provider outage.
- Define and rehearse the incident communication path.

**Acceptance criteria.**
- RPO and RTO are numeric, recorded, and **met in a timed drill**.
- A full environment rebuild from version control succeeds.
- Every runbook has been executed at least once by someone who did not write it.
- Backups are encrypted, access-controlled, and verified restorable.
- Incident communication path is defined and rehearsed.

**Tests.** Restore drills, timed. Failover tests. Runbook execution. Chaos exercises
against the failure scenarios.

**Security checks.** Backup access permission-gated and audited. Backup encryption keys
managed and rotatable. Restored environments contain no production secret that should not
be there.

**Documentation requirements.** `docs/operations/disaster-recovery.md`,
`docs/operations/backup-restore.md`, `docs/operations/drill-results.md`.

**Completion gate.** **A timed drill meets RPO and RTO.** A backup that has not been
restored does not satisfy this gate (`CLAUDE.md` §17).

---

# PHASE 35 — Staging

**Objective.** Operate a production-equivalent environment and validate the full system
in it.

**Dependencies.** Phase 34 COMPLETE.

**Major tasks.**
- Provision staging from the same infrastructure code as production.
- Deploy the same build artifact that will be promoted to production.
- Load realistic (non-production, non-personal) data.
- Execute the full test suite, all client E2E suites, and the acceptance criteria of
  every phase.
- Run an operational readiness exercise: alerts, runbooks, on-call, deployment, rollback.
- Conduct user acceptance testing.

**Acceptance criteria.**
- Staging is production-equivalent in configuration, differing only in scale and data.
- The full acceptance suite for **every** phase passes in staging.
- Deployment and rollback both demonstrated in staging.
- Monitoring, alerting, and runbooks verified operationally.
- UAT sign-off obtained.

**Tests.** Full regression across all suites. All client E2E on real devices.
Deployment and rollback rehearsal. Alert verification.

**Security checks.** Staging contains **no production personal data**. Staging credentials
are distinct from production. Staging is not publicly indexable or reachable by an
unintended audience.

**Documentation requirements.** `docs/operations/staging.md`,
`docs/operations/deployment.md`, `docs/operations/uat-results.md`.

**Completion gate.** Every phase's acceptance criteria pass in staging; rollback
demonstrated; UAT signed off; no production data present in staging.

---

# PHASE 36 — Production deployment

**Objective.** Deploy to production, safely and reversibly.

**Dependencies.** Phase 35 COMPLETE. Release criteria from Phase 2 met.

**Major tasks.**
- Provision production infrastructure from the same code as staging.
- Configure production secrets through the secret manager — never through the repository.
- Promote the **same artifact** validated in staging (`CLAUDE.md` §15).
- Execute the production database migration with a tested rollback path.
- Configure production monitoring, alerting, and on-call.
- Execute a phased rollout with defined rollback triggers.
- Verify the platform end to end in production.
- Complete app store submissions per platform.

**Acceptance criteria.**
- The identical staging-validated artifact is what runs in production.
- All production secrets come from the secret manager; none from the repository.
- Migrations complete successfully with a tested rollback available.
- Monitoring and alerting are live before traffic arrives, not after.
- End-to-end verification passes in production: registration, subscription, payment,
  playback, on every platform.
- Rollback triggers are defined and the rollback path is tested.

**Tests.** Production smoke tests. End-to-end verification on every platform. Rollback
rehearsal before traffic ramp.

**Security checks.** Production security configuration verified: TLS, headers, rate
limits, origin protection, least-privilege database access, MFA on all admin accounts,
debug mode off. Final secret scan across all artifacts and images.

**Documentation requirements.** `docs/operations/production.md`,
`docs/operations/on-call.md`, `docs/operations/release-notes.md`.

**Completion gate.** End-to-end verification passes in production on every platform;
monitoring live; rollback tested; **explicit human go/no-go decision recorded**.

---

# PHASE 37 — Final production audit

**Objective.** Verify that what was built is what was specified, and that it is
operationally, legally, and commercially sound.

**Dependencies.** Phase 36 COMPLETE, plus a defined period of production operation.

**Major tasks.**
- Audit implementation against every Phase 2 requirement via the traceability matrix.
- Audit production performance against the Phase 2 non-functional requirements using
  real traffic data.
- Audit rights enforcement: sample assets and prove the compliance chain end to end.
- Audit security posture in production.
- Audit privacy compliance: retention enforcement, data-subject request handling.
- Audit license compliance and client attribution.
- Audit operational readiness: incidents handled, runbooks used, alerts actioned.
- Review all documentation for accuracy against the deployed system.
- Record technical debt and the post-launch backlog.

**Acceptance criteria.**
- 100% of requirements traced to implementation and verified, or explicitly and
  knowingly deferred.
- Non-functional requirements met by **real production measurements**, not test-lab
  results.
- Rights compliance provable for any sampled asset on demand.
- No unremediated critical or high security finding.
- Privacy obligations verified in production.
- Documentation matches the deployed system.
- Every mock, stub, and temporary measure is either removed or explicitly recorded as
  outstanding technical debt with an owner (`CLAUDE.md` §1).

**Tests.** Full regression in production-equivalent conditions. Compliance sampling.
Security regression. Privacy verification.

**Security checks.** Complete security posture review. Confirm no bypass, backdoor, debug
route, or test credential exists in production.

**Documentation requirements.** `docs/audit/final-production-audit.md`,
`docs/audit/technical-debt.md`, `PROJECT_STATE.md` updated to reflect the launched state.

**Completion gate.** All requirements verified; all audits passed; technical debt recorded
with owners; **final sign-off recorded**. The project transitions from delivery to
operation — and this plan is superseded by an operational roadmap.

---

## Appendix A — Cross-phase standing obligations

These apply in **every** phase and are checked at every gate:

| Obligation | Reference |
|---|---|
| No secret committed, logged, or exposed | `CLAUDE.md` §1, §6 |
| No mock presented as production functionality | `CLAUDE.md` §1, §23 |
| No failing or skipped test hidden | `CLAUDE.md` §1, §13 |
| Documentation updated in the same change | `CLAUDE.md` §21 |
| OpenAPI updated in the same change | `CLAUDE.md` §10 |
| Third-party license procedure followed before adoption | `CLAUDE.md` §3 |
| Rights basis recorded for every content source | `CLAUDE.md` §2.3 |
| Authorization enforced server-side, never by the client | `CLAUDE.md` §4.3, §8 |
| `PROJECT_STATE.md` updated at phase end | `CLAUDE.md` §22 |
| No automatic phase advancement | `CLAUDE.md` §22 |

## Appendix B — Structural risks to manage from the start

| Risk | Phase | Why it must be handled early |
|---|---|---|
| Apple toolchain unavailable on Linux | 22 | Cannot be resolved by installation. Requires hardware or hosted macOS CI procurement — a purchasing lead time, not an engineering task. |
| Samsung and LG developer registration | 23, 24 | Registration, certificates, and store approval have external lead times measured in weeks. Start during Phase 3, not Phase 23. |
| Ephemeral 30 GB environment | 16+ | Will not hold a multi-platform toolchain plus build caches. A persistent build environment decision is needed well before the client phases. |
| Rights model designed too late | 13 | Retrofitting rights onto a catalogue built without it is a near-total rewrite of the entitlement and playback paths. It is modelled in Phase 4 for this reason. |
| Codec and patent licensing | 16, 30 | Separate from software licensing, with real commercial cost. Must be resolved before launch, not discovered at it. |
| Live-event concurrency spike | 32 | The defining load pattern of live TV. Average-load testing will pass while the real launch fails. |
