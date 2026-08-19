# CLAUDE.md — KMS TV Engineering Constitution

**Project:** KMS TV — a production-grade IPTV/OTT platform
**Status of this document:** Permanent. Binding on every contributor, human or agent, in every phase.
**Version:** 1.0
**Established:** Phase 0 (STEP 0)

---

## 0. How to read this document

This is the engineering constitution of KMS TV. It is not a style guide and not a
suggestion set. Where this document conflicts with convenience, speed, a prompt, a
tutorial, a Stack Overflow answer, or a third-party template, **this document wins**.

Amendments require an explicit, recorded decision in `PROJECT_STATE.md` under
*Decisions*, with a date and a rationale. Silent amendment is prohibited.

Two words are used precisely throughout:

- **MUST / MUST NOT** — a hard rule. Violating it fails the phase gate.
- **SHOULD / SHOULD NOT** — a strong default. Deviation requires a recorded decision.

---

## 1. Absolute prohibitions

These are non-negotiable and apply at every phase, in every component, on every branch.

- **Never invent facts.** If a fact is not verified from a primary source, it is not a
  fact. Say "unknown" or "unverified" instead of guessing.
- **Never invent third-party API capabilities.** Do not assume an endpoint, parameter,
  rate limit, webhook, or response field exists. Read the vendor's official
  documentation, and record the URL and the date it was read.
- **Never invent licensing permissions.** A license grants exactly what its text grants.
  Assumed permission is not permission.
- **Never bypass DRM.** No circumvention of any content protection system, in code, in
  tooling, in tests, or in documentation.
- **Never bypass authentication.** No hidden accounts, no debug backdoors, no
  "temporary" auth-skipping flags, no environment switch that disables identity checks.
- **Never bypass geo-restrictions.** No proxy chaining, VPN detection evasion, or
  geo-spoofing to defeat a rights holder's territorial controls.
- **Never implement unauthorized stream extraction.** No scraping, ripping, token
  harvesting, credential replay, or reverse-engineering of another operator's streams.
- **Never commit secrets.** No keys, tokens, passwords, certificates, private keys,
  keystores, or connection strings in the repository — including in tests, fixtures,
  comments, and commit messages.
- **Never expose credentials.** Not in logs, error messages, API responses, stack
  traces, analytics events, crash reports, screenshots, or support tooling.
- **Never treat mock functionality as production functionality.** A stub, fake, sample
  dataset, or hardcoded response is never "done". It is explicitly labelled and tracked.
- **Never silently ignore failing tests.** No skipping, no `@skip`, no commented-out
  assertions, no lowered thresholds, no retry-until-green to hide flakiness.
- **Never skip security checks.** Not for a deadline, not for a demo, not for a hotfix.
- **Never skip acceptance criteria.** Partial delivery is reported as partial delivery.
- **Never proceed to the next phase automatically.** Phase transitions require an
  explicit human decision after the gate passes.

If a request conflicts with any prohibition above, **stop and report the conflict**.
Do not deliver a workaround that achieves the prohibited outcome by another route.

---

## 2. Legal and content policy

KMS TV is designed to distribute **only** content for which the operator holds
appropriate rights or permission. The architecture assumes a licensed operator serving
licensed content to entitled subscribers.

### 2.1 Rights are a first-class data model concern

- Every distributable asset (channel, VOD title, series, episode, catch-up window,
  restart window) MUST carry rights metadata: rights holder, contract reference,
  territories, start date, end date, permitted device classes, permitted
  distribution modes (live / catch-up / restart / VOD / download), and concurrency limits.
- Rights MUST be enforced at **playback authorization time**, not only in the UI.
  Hiding a tile is presentation. Denying a playback token is enforcement.
- Content whose rights window has expired MUST be automatically and verifiably disabled.
  Expiry MUST be enforced by a scheduled job **and** re-checked at each authorization
  request. A single mechanism is not sufficient.
- Rights changes MUST be auditable: who changed what, when, and against which contract.

### 2.2 Prohibited feature classes

The platform MUST NOT contain features whose purpose or primary effect is to:

- bypass DRM or any content protection system
- bypass authentication or authorization
- bypass geo-restrictions or territorial licensing
- steal, harvest, or replay stream credentials
- extract, restream, or re-host protected streams from third parties
- evade content-provider controls, watermarking, or audit mechanisms
- redistribute unauthorized copyrighted content

This applies to admin tooling and internal diagnostics as strongly as to end-user
features. "Internal only" is not an exemption.

### 2.3 Content ingestion

- Every content source MUST have a recorded provenance: who supplied it, under what
  agreement, with what identifier.
- Ingesting a stream URL or playlist that has no recorded rights basis is prohibited.
- The system MUST be able to answer, for any asset in the catalogue: *why are we
  allowed to serve this, to whom, where, and until when?*

---

## 3. Third-party dependencies and open-source licenses

### 3.1 Mandatory intake procedure

Before **any** third-party source code, library, container image, model, font, icon set,
or media asset is incorporated:

1. **Identify the repository** — canonical upstream URL and exact version/commit.
2. **Identify the license** — read the actual `LICENSE` file, not the badge, not the
   package-registry metadata field.
3. **Identify the dependencies** — the transitive tree, and the licenses within it.
4. **Determine compatibility** with the KMS TV distribution model (proprietary
   server-side product, proprietary client applications distributed through app stores).
5. **Record the decision** in `docs/legal/THIRD_PARTY_LICENSES.md` with date and reviewer.
6. **Prefer adapter/integration over copying source code.** Depend on it, do not absorb it.

### 3.2 License rules

- **Never copy copyleft source into proprietary components without explicit legal review.**
  This includes GPL, AGPL, and SSPL-family licenses, and includes partial copying of
  functions, algorithms in source form, or files.
- AGPL-licensed code MUST NOT be linked into or served by KMS TV backend services
  without explicit written legal approval recorded in `docs/legal/`.
- GPL-licensed **binaries invoked as separate processes** (notably FFmpeg builds) are a
  distinct case from GPL source linking. The specific FFmpeg build and its enabled
  codecs/licensing MUST be reviewed and recorded before production use — including
  whether the build is LGPL or GPL, and whether any `--enable-nonfree` component is
  present. Nonfree builds MUST NOT be distributed.
- Codec, container, and streaming-technology **patent licensing** (for example
  H.264/AVC, H.265/HEVC, AAC) is a separate question from software licensing and MUST be
  reviewed separately before commercial launch. Do not assume software freedom implies
  patent freedom.
- Client-side dependencies bundled into distributed applications require attribution.
  An attribution/notices screen MUST exist in every client application before release.

### 3.3 Supply chain

- Lockfiles MUST be committed and MUST be the source of truth for installed versions.
- Dependency additions MUST be justified in the pull request description.
- Automated vulnerability scanning MUST run in CI and MUST block on known critical and
  high severity findings in production dependencies.
- Unmaintained dependencies (no release or security fix within a defined window) MUST be
  flagged for replacement in `PROJECT_STATE.md` under *Known issues*.

---

## 4. Architecture

### 4.1 Principles

- **Modular monolith first.** The backend starts as one well-partitioned Laravel
  application with strict internal module boundaries. Services are extracted only when a
  measured constraint (scaling, deployment independence, team boundary) demands it, and
  only with a recorded decision.
- **Domain boundaries are explicit.** Identity, Catalogue, EPG, Rights, Entitlements,
  Playback Authorization, Billing, Delivery, Analytics, and Administration are separate
  modules. Cross-module access goes through a published interface, never through a
  foreign module's internals or a foreign module's database tables.
- **The control plane and the data plane are separate.** Business logic, entitlement
  decisions, and catalogue management (control plane) MUST NOT sit in the request path
  of segment delivery (data plane). Segment delivery is edge/CDN work.
- **Stateless application tier.** No session state, no uploads, no cache-of-record on
  application instances. State lives in PostgreSQL, Redis, or object storage.
- **Everything time-based is UTC.** Storage, computation, and APIs use UTC. Localization
  to a user's timezone is a presentation concern only. EPG correctness depends on this.
- **Idempotency by default** for any operation that can be retried: payments, webhooks,
  ingestion, provisioning, and device registration.

### 4.2 Constraints

- Vendor-specific concerns (CDN, payment provider, DRM provider, push provider, SMS
  provider) MUST sit behind an internal abstraction. No vendor SDK type may leak into
  domain code.
- The abstraction MUST be validated against **at least two** real candidate providers on
  paper before it is finalized, so it does not encode one vendor's model as universal.
- Client applications MUST NOT contain business rules that determine entitlement. Clients
  render what the backend authorizes. A client is never the authority on access.

### 4.3 Prohibited architecture patterns

- Business logic in controllers, migrations, seeders, views, or client applications.
- Direct cross-module database queries.
- Shared mutable global state.
- Synchronous calls to third parties inside a user-facing request path without a timeout,
  a circuit breaker, and a defined degraded behaviour.
- Any component that cannot be restarted at any moment without data loss.

---

## 5. Code quality

- **Static analysis MUST pass at the project's declared maximum practical level.** For
  PHP this means PHPStan/Larastan at a level recorded in `PROJECT_STATE.md`, which may
  only increase over the life of the project, never decrease.
- **Strict typing everywhere it exists.** `declare(strict_types=1)` in PHP.
  TypeScript `strict: true` with no `any` in domain code and no `@ts-ignore` without a
  recorded justification comment. Kotlin and Swift null-safety honoured, no force
  unwraps in production paths.
- **Formatting is automated and non-negotiable.** Formatter output is the correct output.
  Formatting MUST run in CI.
- **No dead code.** No commented-out blocks, no unreachable branches, no unused exports
  left "for later".
- **No TODO without a tracked issue reference.** A bare `TODO` is a defect.
- **Errors are handled explicitly.** No empty catch blocks. No swallowing exceptions. No
  returning `null` to mean failure where a typed result or exception is available.
- **Functions do one thing.** If a description needs "and", split it.
- **Naming reflects the domain**, not the framework. `EntitlementDecision`, not `DataObj`.
- **Comments explain why, not what.** The code states what.
- **Match the surrounding code.** Consistency within a module outranks personal
  preference.

---

## 6. Security

Security is a gate, not a phase. It applies from Phase 0.

### 6.1 Baseline requirements

- **Deny by default.** Every endpoint, every route, every queue consumer, every admin
  action requires an explicit grant. A missing policy means denied, never allowed.
- **Validate all input at the boundary.** Type, range, length, format, and authorization
  context. Never trust a client-supplied identifier without an ownership check.
- **Parameterized queries only.** String-concatenated SQL is prohibited without exception.
- **Output encoding by context.** HTML, attribute, URL, JSON, and shell contexts each
  have distinct escaping rules.
- **No secrets in the repository.** Secrets come from the environment or a secret manager
  at runtime. `.env` files are never committed. `.env.example` contains keys with empty
  or obviously fake values only.
- **Secret rotation MUST be possible without a code change.**
- **Least privilege** for database users, service accounts, cloud roles, and API keys.
  The application's runtime database user MUST NOT own the schema or hold DDL rights in
  production.
- **All transport is TLS.** Internal service-to-service traffic included. No plaintext
  credentials on any wire.
- **Passwords hashed with a memory-hard algorithm** (Argon2id preferred, bcrypt
  acceptable). Never MD5, SHA-1, SHA-256-without-KDF, or reversible encryption.
- **Rate limiting and lockout** on authentication, password reset, device registration,
  playback authorization, and payment endpoints.
- **Audit logging** for every privileged action: who, what, when, from where, and the
  before/after state. Audit logs MUST be append-only and MUST NOT contain credentials
  or full payment data.
- **Dependency and container scanning** run in CI on every build.
- **Security headers** on all web responses: HSTS, CSP, `X-Content-Type-Options`,
  `Referrer-Policy`, and frame protections.

### 6.2 Data protection

- Personally identifiable information MUST be enumerated in `docs/` and classified.
- Payment card data MUST NOT touch KMS TV servers. Use a provider-hosted or tokenized
  flow. PAN, CVV, and full track data MUST never be stored, logged, or transmitted
  through our systems.
- Encryption at rest for databases, backups, and object storage.
- Backups are encrypted, access-controlled, and restore-tested (see §17).

### 6.3 Reporting

- A discovered vulnerability MUST be recorded in `PROJECT_STATE.md` under *Known issues*
  with a severity, even when fixed in the same change.
- Suspected compromise stops feature work. Containment first.

---

## 7. Authentication

- Authentication is centralized in one identity module. No component implements its own.
- Credentials are verified server-side only.
- Tokens are short-lived; refresh tokens are rotated on use and revocable server-side.
- Every issued token MUST be bound to a device identity and a session record that an
  administrator or the user can terminate.
- Token revocation MUST take effect for playback within a bounded, documented interval.
  "Eventually" is not an interval.
- Multi-factor authentication MUST be supported for administrative accounts before any
  production deployment.
- Account enumeration MUST NOT be possible through response differences or timing on
  login, registration, or password reset.
- Session fixation, replay, and CSRF protections MUST be present and tested.
- **There is no bypass mode.** No environment flag, seeded superuser, or debug route
  that grants access without authentication may exist in any branch that can reach
  production.

---

## 8. Authorization and RBAC

- Authorization decisions are centralized, explicit, and testable. Scattered inline
  permission checks are prohibited.
- The model separates:
  - **Roles** — administrative capability within the operator organization.
  - **Entitlements** — what a subscriber may watch, derived from packages,
    subscriptions, rights, and device class.
  These are different systems and MUST NOT be conflated.
- Every administrative capability MUST be a named permission. Role definitions are data,
  not code constants scattered across the codebase.
- Privilege escalation paths MUST be explicitly tested: a user MUST NOT be able to grant
  themselves a role, alter their own entitlements, or act on another tenant's data.
- Authorization MUST be enforced server-side on every request, including requests the UI
  believes it has already filtered.

---

## 9. Database

- **PostgreSQL is the system of record.** Redis is a cache, a queue backend, and a
  short-lived-state store. Redis is never the system of record for anything that matters.
- **All schema changes go through versioned, reviewed migrations.** No manual production
  DDL, ever.
- **Every migration MUST be reversible** or MUST document explicitly why it cannot be,
  with an approved forward-fix plan.
- **Destructive migrations require a recorded decision and a verified backup** taken
  immediately before execution.
- **Foreign keys, unique constraints, check constraints, and NOT NULL are used.**
  Integrity is enforced by the database, not by hope or by application code alone.
- **Money is never a float.** Use integer minor units or `NUMERIC`. Currency is stored
  alongside every amount.
- **Timestamps are `timestamptz` in UTC.**
- **Indexes are justified.** Every index has a query that needs it. Every slow query has
  an index or a recorded reason it does not.
- **No N+1 queries** in any request path. Detection MUST be automated in the test suite.
- **Soft deletes are deliberate, not default.** Rights, entitlement, and audit records
  MUST be retained; transient records need not be.
- **Personal data MUST be deletable** in a way that satisfies the privacy commitments in
  §18, including from backups within the documented retention window.
- Seed data is for development and testing only, and is clearly separated from
  production reference data.

---

## 10. API

- The public API is **REST over HTTPS**, versioned in the path, and specified in
  **OpenAPI**.
- **The OpenAPI document is authoritative and is updated in the same change as the code.**
  A route that is not in the specification does not exist. A specification that does not
  match the implementation is a defect that fails the gate.
- Breaking changes require a new version. Removing a field, narrowing a type, changing an
  error code, or tightening validation on an existing endpoint is a breaking change.
- Responses use consistent envelopes, consistent error shapes, and stable machine-readable
  error codes. Clients MUST NOT need to parse human-readable messages.
- Pagination, filtering, and sorting are consistent across all collection endpoints.
- Every endpoint declares its authentication requirement, its required permission, and its
  rate limit in the specification.
- **Error responses MUST NOT leak internals** — no stack traces, no SQL, no file paths,
  no upstream vendor errors passed through verbatim.
- Every request carries a correlation ID that flows into logs and traces.
- Contract tests MUST verify implementation against the specification in CI.

---

## 11. Streaming

- **HLS is the baseline.** DASH/CMAF is added where it earns its place, with a recorded
  decision. Where both are served, they SHOULD share CMAF segments rather than duplicate
  storage and packaging.
- **FFmpeg is invoked as an external process** behind an internal abstraction. FFmpeg
  types, command strings, and error text MUST NOT leak into domain code.
- FFmpeg command construction MUST NOT interpolate unvalidated user or operator input.
  Arguments are passed as an argument vector, never through a shell string.
- Every transcode, packaging, and ingest job MUST have a timeout, a bounded retry policy,
  a failure state, and an operator-visible status.
- Media processing runs on dedicated capacity, never on the API request path.
- Playlist and manifest generation MUST be deterministic and testable.
- Segment delivery is served from the edge. The application tier MUST NOT proxy segments
  in production.
- The origin MUST be inaccessible to the public internet. Only the CDN reaches it, and
  that path MUST be authenticated.
- Every stream input MUST have recorded provenance and a rights basis (§2).

---

## 12. Playback security

- **Playback requires an authorization decision from the backend.** A client that
  possesses a manifest URL MUST NOT thereby be able to play.
- Playback tokens MUST be:
  - short-lived
  - single-purpose (bound to one asset or one session)
  - bound to the requesting session and device
  - revocable
  - validated at the edge, not only at issuance
- Manifest and segment URLs MUST NOT be guessable or long-lived. Signed URLs or signed
  cookies with short expiry are the baseline.
- Concurrency limits MUST be enforced server-side, per subscription and per rights
  agreement, with a defined and tested behaviour when the limit is reached.
- Geo-enforcement, where a rights agreement requires it, MUST be enforced server-side at
  authorization and at the edge. Client-reported location is never authoritative.
- Playback authorization decisions MUST be logged with enough detail to prove compliance
  to a rights holder, without logging credentials or tokens.
- **DRM-ready architecture** means: key delivery is abstracted, license acquisition is a
  defined integration point, encrypted and clear workflows are distinguished in the data
  model, and adding a DRM provider does not require redesign. It does **not** mean
  implementing, weakening, or working around any protection system (§1).

---

## 13. Testing

- **Every change ships with tests.** A bug fix ships with a test that fails before the fix.
- Test layers, all of which MUST exist by the phase that introduces the relevant surface:
  - **Unit** — domain logic in isolation.
  - **Integration** — real database, real cache, real queue. Not mocked.
  - **Contract** — API implementation against the OpenAPI specification.
  - **End-to-end** — critical user journeys, per client platform.
  - **Security** — authorization, entitlement, and playback-authorization negatives.
  - **Load** — before any production deployment (Phase 32).
- **Negative testing is mandatory** for entitlements and playback authorization. Proving
  that an entitled user can watch is half the test. Proving that an unentitled, expired,
  out-of-territory, over-concurrency, or revoked user **cannot** watch is the other half,
  and it is the half that protects the business.
- Tests MUST be deterministic. A flaky test is a defect and MUST be fixed or removed —
  never retried into silence.
- Tests MUST NOT depend on live third-party services. Integration with a real vendor
  happens in a separately-tagged suite that is allowed to be excluded from the fast path
  but MUST run before a release.
- **Coverage is a signal, not a goal.** A minimum threshold is enforced in CI and may
  only increase. Meeting it does not by itself satisfy any acceptance criterion.
- **Never silently ignore failing tests.** A red suite blocks the gate. Disabling a test
  to pass a gate is a constitution violation.

---

## 14. Observability

- **Structured logging** — JSON, with correlation ID, actor, module, and severity.
  Never log credentials, tokens, session identifiers, playback keys, payment data, or
  full personal records.
- **Metrics** — RED (rate, errors, duration) for every service; plus domain metrics:
  concurrent streams, playback authorization decisions by outcome, entitlement denials by
  reason, transcode queue depth, EPG ingest freshness, rights expiring within 24 hours.
- **Tracing** — distributed traces across API, queue workers, and external calls.
- **Alerting is actionable.** Every alert has an owner, a runbook, and a defined response.
  An alert with no runbook is deleted or given one.
- **Health and readiness endpoints** distinguish "process is alive" from "dependencies are
  reachable and the instance can serve traffic".
- Dashboards MUST exist for: platform health, playback success rate, entitlement denial
  reasons, ingest pipeline status, and payment outcomes.
- Client applications MUST report playback quality-of-experience: startup time, rebuffer
  ratio, bitrate distribution, and failure reasons with error codes.

---

## 15. CI/CD

- CI runs on every push and every pull request. There is no "skip CI" for anything that
  can reach a protected branch.
- The pipeline MUST include, as blocking steps: format check, static analysis, unit tests,
  integration tests, contract tests, dependency vulnerability scan, secret scan, and
  license compliance check.
- Builds are reproducible and produce versioned, immutable artifacts.
- **The same artifact is promoted** through environments. Rebuilding per environment is
  prohibited — configuration differs, the build does not.
- Deployments are automated, logged, and reversible. A rollback path MUST be tested
  before the first production deployment, not after the first incident.
- Database migrations run as a distinct, observable deployment step with a defined
  failure behaviour.
- Production deployment requires: a passing pipeline, a passed phase gate, and an explicit
  human approval.
- Secrets are injected by the pipeline from a secret manager. Secrets MUST NOT appear in
  pipeline definitions, build logs, or artifact contents.

---

## 16. Git

- **Trunk is `main`.** `main` is always releasable.
- Work happens on branches. Branch names describe the work.
- **Never force-push a shared branch.**
- Commits are atomic, with messages explaining *why*, not just *what*.
- **Never commit secrets** (§6). A secret that reaches history requires rotation of the
  secret and a recorded incident — removing the commit is not sufficient remediation.
- Lockfiles, migrations, and the OpenAPI specification are committed with the code that
  requires them.
- Generated artifacts, dependencies, build output, and local environment files are
  ignored, never committed.
- Every change is reviewed before merge to `main` once the project has more than one
  contributor. Self-merge without review is permitted only in Phase 0–2.
- `.gitattributes` MUST define line-ending and diff behaviour before multi-platform client
  work begins (Phase 20+).

---

## 17. Disaster recovery

- **RPO and RTO MUST be defined numerically** and recorded in `PROJECT_STATE.md` before
  production deployment. An undefined target cannot be met.
- Automated, encrypted backups of PostgreSQL with point-in-time recovery.
- **A backup that has not been restored is not a backup.** Restore drills are scheduled,
  performed, timed, and recorded. A drill that exceeds RTO is an incident.
- Configuration and infrastructure definitions are in version control and are
  reproducible from scratch.
- Runbooks MUST exist for: database loss, origin loss, CDN failure, queue backlog,
  credential compromise, and provider outage — before production deployment.
- A documented, rehearsed incident response process, including a communications path, is
  required before launch.

---

## 18. Privacy

- **Collect the minimum.** Every field of personal data MUST have a stated purpose. Data
  collected "in case it's useful" is prohibited.
- Retention periods MUST be defined per data category and MUST be enforced automatically.
- Users MUST be able to obtain their data and to request deletion. The deletion path MUST
  be implemented and tested, including its effect on backups and analytics.
- Viewing history is sensitive. It MUST be access-controlled, MUST NOT be exposed across
  profiles within an account without explicit design, and MUST NOT be shared with third
  parties without a lawful basis and a recorded decision.
- Children's profiles, where offered, receive stricter defaults.
- Analytics MUST default to pseudonymous identifiers. Joining analytics to identity
  requires a recorded decision and a stated purpose.
- Third-party SDKs in client applications MUST be reviewed for data collection before
  inclusion, and their collection disclosed.
- Data processing locations and cross-border transfers MUST be documented before launch.

---

## 19. Performance

- **Performance budgets are defined before optimization and before launch**, and recorded.
  At minimum: API p95 and p99 latency, playback authorization latency, time-to-first-frame,
  rebuffer ratio, EPG query latency, and admin page load.
- Performance is measured, not asserted. A claim of "fast" without a number is not a claim.
- Caching strategy MUST be explicit: what is cached, where, for how long, and how it is
  invalidated. Cache invalidation for rights and entitlement changes MUST be immediate —
  a stale entitlement is a compliance failure, not a performance trade-off.
- Database queries in hot paths MUST be reviewed with real query plans against
  realistic data volumes, not empty tables.
- Client applications MUST meet startup and navigation budgets on the **lowest** supported
  device class, especially on TV platforms, which are the constraint — not desktop.

---

## 20. Scalability

- Horizontal scaling is the default for the application tier. Anything that prevents
  adding an instance is a defect.
- Long-running and bursty work goes to queues with bounded concurrency, dead-letter
  handling, and visible backlog metrics.
- Load testing (Phase 32) MUST model realistic patterns, including the concurrency spike
  at the start of popular live events — the defining load characteristic of live TV, and
  the one that ordinary average-load testing will miss entirely.
- Capacity planning MUST be documented with headroom targets before production.
- Every external dependency MUST have a defined behaviour when it is slow or unavailable,
  and that behaviour MUST be tested.

---

## 21. Documentation

- Documentation lives in the repository, in version control, next to the code.
- **A change is not complete until its documentation is updated in the same change.**
- Required and continuously maintained:
  - `CLAUDE.md` — this constitution
  - `PROJECT_STATE.md` — current state, updated at the end of every phase
  - `IMPLEMENTATION_PLAN.md` — roadmap and gates
  - `docs/architecture/` — decisions, diagrams, module boundaries
  - `docs/api/` — the OpenAPI specification and integration guides
  - `docs/security/` — threat model, controls, incident response
  - `docs/legal/` — license register, rights policy, compliance decisions
  - `docs/operations/` — runbooks, deployment, disaster recovery
- **Architecture Decision Records** are required for any decision that is expensive to
  reverse: datastore choice, DRM provider, CDN strategy, payment provider, client
  framework, packaging format.
- Documentation MUST state what is **not** built yet, as clearly as what is.

---

## 22. Development phases

- Work proceeds in the phases defined in `IMPLEMENTATION_PLAN.md`.
- **Each phase has a hard completion gate.**
- **A phase cannot be marked COMPLETE unless all of the following hold:**
  1. Implementation is complete — no stubs presented as finished work.
  2. Tests pass — all of them, with no skips added to achieve it.
  3. Static analysis passes at the declared level.
  4. Security checks pass.
  5. Documentation is updated.
  6. Acceptance criteria pass, demonstrably.
  7. No known critical defect remains.
  8. `PROJECT_STATE.md` is updated.
- **If a gate fails: STOP.** Do not continue to the next phase. Record the failure in
  `PROJECT_STATE.md` under *Blockers*, and fix it.
- **Never proceed to the next phase automatically.** A gate passing is a report to a
  human, not a licence to continue.
- Phases may be re-entered. A completed phase whose acceptance criteria later break is
  reopened, and dependent phases are re-evaluated.

---

## 23. Definition of Done

A unit of work is **DONE** only when every one of these is true:

1. It does what was specified — the whole specified scope, not a convenient subset.
2. It is implemented in production-quality code, with no mock, stub, or hardcoded value
   presented as functional.
3. Tests exist at the appropriate layers, including negative and authorization cases, and
   they pass.
4. Static analysis and formatting pass.
5. Security requirements for the touched area are met, including authorization checks and
   input validation.
6. No secret, credential, or personal data is exposed in code, logs, or responses.
7. Errors, timeouts, and failure modes are handled with defined behaviour.
8. Observability exists — the work can be seen working and seen failing in production.
9. Documentation and the OpenAPI specification are updated in the same change.
10. Any third-party addition has passed the §3 license procedure and is recorded.
11. Performance is within the defined budget for the touched path.
12. Migrations are reversible, or a forward-fix plan is recorded.
13. `PROJECT_STATE.md` reflects the new state.
14. Anything incomplete, assumed, deferred, or uncertain is **explicitly reported**, not
    quietly left.

**Anything less is reported as incomplete.** Reporting partial work as done is a
constitution violation, and it is the one that does the most damage, because it removes
the ability to trust every other report.

---

## 24. Reporting standard

- Report what is true, including failures, skipped steps, and uncertainty.
- If something was not verified, say it was not verified.
- If a result is unexpected, investigate before reporting it as fine.
- If a task cannot be completed as specified, deliver everything that can be completed,
  and state precisely what was not done and why.
- Do not claim a tool, dependency, service, or capability exists without checking.
- Distinguish clearly between: *implemented and verified*, *implemented and untested*,
  *stubbed*, and *not started*.
