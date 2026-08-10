# Development Phases

Each phase has **exit criteria** — objective, demonstrable conditions. A phase is not complete
because its tickets are closed; it is complete when its criteria hold. Phases overlap where noted,
but the dependency ordering is real: skipping ahead means building on unverified assumptions.

No durations are given. Estimating without knowing team size (**OQ-18**) or launch date (**OQ-21**)
would be fabrication.

---

## Phase 0 — Foundation *(this phase)*

Repository inspection, architecture, and engineering rules. No application code.

**Exit criteria**
- [x] Repository and environment inspected and recorded
- [x] Architecture, bounded contexts, and boundaries documented
- [x] Database, API, security, streaming, rights, testing, CI/CD strategies documented
- [x] Dependencies and licences verified where verifiable; unverifiable claims marked as such
- [x] Risks and open questions raised explicitly
- [ ] **Open questions OQ-1 … OQ-4, OQ-13, OQ-16, OQ-17 answered by the product owner** ← blocks Phase 1

---

## Phase 1 — Platform skeleton

The minimum real system: one Laravel service, one database, CI that actually verifies things, and
one genuine end-to-end feature (authentication). Nothing simulated.

**Scope**
- Monorepo tooling: pnpm workspace, root `Makefile`, shared configs
- `services/core-api`: Laravel, PHP 8.3+, modular structure with **CI-enforced** boundaries
- Modules with real implementations: `Identity`, `Profile`, `Device` (registration only),
  `Administration` (staff auth + audit skeleton)
- PostgreSQL schema-per-context, migration conventions, seed dataset
- Redis: cache, queue, Horizon
- `packages/api-contracts`: OpenAPI for client API v1 (auth surface only) + generated TS client
- `infrastructure/docker`: local compose stack (PostgreSQL, Redis, PHP-FPM, Nginx, Mailpit)
- CI: lint, static analysis, unit + integration tests against real services, spec conformance,
  dependency audit, secret scanning
- Observability baseline: structured logs with `correlation_id`, health/readiness endpoints, metrics
- Secret handling: no secrets in repo, documented injection path
- `CODEOWNERS`, PR template, branch protection

**Explicitly not in Phase 1:** catalog, EPG, playback, payments, streaming, any client application
beyond what is needed to exercise the auth API.

**Exit criteria**
- [ ] `make up` gives a working stack on a clean machine from a fresh clone
- [ ] A user can register, verify email, sign in on a device, refresh a token, sign out — against the
      real database, exercised by an end-to-end test
- [ ] TV **device activation (pairing code) flow** works end to end — proves the hardest auth
      interaction before any TV app exists
- [ ] CI fails on: boundary violation, spec drift, failing test, known-vulnerable dependency,
      committed secret
- [ ] A deliberately introduced cross-module import is rejected by CI (verified, not assumed)
- [ ] Every architecture decision made during the phase is recorded as an ADR

---

## Phase 2 — Content control plane

**Scope:** `Catalog`, `Schedule` (EPG), channels and lineups, `epg-ingest` service with a real
provider feed, `apps/admin` (Next.js) for content and schedule management, metadata enrichment
behind port P7, image handling and derivatives.

**Exit criteria**
- [ ] A real EPG feed ingests on a schedule, with revision handling and reconciliation of corrections
- [ ] Content operators manage the catalog through the admin UI, with audit trail
- [ ] Client API v1 serves channels, lineup, and now/next with cursor pagination
- [ ] Schedule ingestion survives a malformed feed without data loss or manual repair

---

## Phase 3 — Commercial control plane

**Scope:** `Product` (packages, plans, prices, promotions), `Billing` (subscription lifecycle,
invoices, dunning), `Entitlement` (grants, materialised entitlement snapshot), `PaymentGateway`
adapter against a **sandbox only**.

**Exit criteria**
- [ ] Full subscription lifecycle exercised in tests including trials, renewals, failures, dunning,
      cancellation, and reactivation
- [ ] Entitlement snapshot correctly reflects every lifecycle transition, verified by property tests
- [ ] Every payment operation is idempotent under replay; webhooks are signature-verified, stored
      raw, and processed asynchronously
- [ ] P1 verification checklist complete and the PSP decision recorded as an ADR
- [ ] **No production payment credentials exist anywhere in the system yet**

---

## Phase 4 — Playback authorization and live MVP

The first end-to-end stream. Deliberately narrow: one channel, clear or test-key protected.

**Scope:** `Rights` + availability projection, `Playback` authorization, concurrency accounting,
`Delivery` control with one CDN adapter, `playback-authorizer` deployment profile, live ingest →
transcode → package → origin → CDN for **one** channel, geo determination (P11).

**Exit criteria**
- [ ] One live channel plays end to end on a browser, from a real CDN, through a real origin
- [ ] Authorization enforces entitlement, rights window, territory, device class and concurrency —
      each verified by a test that proves the **denial** path with a specific reason code
- [ ] Every decision is recorded and reproducible from its recorded inputs
- [ ] Load test at the target concurrency meets the p99 latency objective
- [ ] Every degraded mode has defined, tested behaviour
- [ ] **OQ-11 (advertising/SSAI) answered** — after this phase, retrofitting SSAI is expensive

---

## Phase 5 — VOD pipeline and web application

**Scope:** `MediaAsset` context, `media-pipeline` service (FFmpeg ladder, packaging, QC),
mezzanine ingest and storage, VOD availability, `apps/web` (Next.js) with `packages/ts-player-core`,
`Discovery` context with server-driven rails.

**Exit criteria**
- [ ] A mezzanine file ingests, encodes, packages, and plays on the web app without manual steps
- [ ] Manifests pass golden-file tests and independent validation
- [ ] Resume points, watchlist, and continue-watching work per profile
- [ ] The web app degrades correctly on authorization failure with actionable messages

---

## Phase 6 — DRM in production

Started early because the agreements are the long pole; the engineering is comparatively short.

**Scope:** `Protection` context, `drm-license-proxy` service in its own network zone, content key
lifecycle and rotation, licence policy driven by rights usage rules, encrypted packaging for
Widevine/PlayReady/FairPlay.

**Exit criteria**
- [ ] Encrypted playback works on all three DRM systems with real licence servers
- [ ] Licence policy (security level, HDCP, max resolution, duration) is derived from rights usage
      rules, not hardcoded — verified by a test that changes a right and observes the policy change
- [ ] Content keys exist only in the key vault and the licence proxy; no other component can read
      them, verified by an access review
- [ ] Key rotation exercised without interrupting active sessions

---

## Phase 7 — Catch-up, restart, nDVR

**Scope:** time-shift buffer, restart from programme start, catch-up windows relative to
transmission, recording rights enforcement including **exclusion from the buffer**, storage
lifecycle and retention.

**Exit criteria**
- [ ] Restart and catch-up honour their own rights independently of live rights
- [ ] Content without recording rights is never written to the buffer, verified by an audit
- [ ] Storage growth is bounded, measured, and forecast against the cost model

---

## Phase 8 — Mobile applications

**Scope:** `apps/android` (Kotlin, Media3), `apps/ios` (Swift, AVFoundation),
`packages/kotlin-player-core`, `packages/swift-player-core`, push notifications, store presence.

**Exit criteria**
- [ ] Both apps pass store review
- [ ] DRM playback verified on the supported OS-version matrix on real devices
- [ ] Client API v1 unchanged — or extended additively only

---

## Phase 9 — Television applications

**Scope:** `apps/androidtv`, `apps/tizen`, `apps/webos`, `packages/ts-ui-tv` (focus/spatial
navigation, remote key handling), device activation UX at scale.

**Exit criteria**
- [ ] Certification passed for each platform
- [ ] Verified on the agreed model-year matrix on **real hardware** — emulators are not sufficient
- [ ] Performance acceptable on the **lowest** target device, not the newest
- [ ] Forced-upgrade and minimum-version signalling proven to work

---

## Phase 10 — Scale, insight, launch readiness

**Scope:** `telemetry-collector`, QoE dashboards, licensor usage reporting, multi-CDN steering,
search, recommendations groundwork, capacity and cost modelling, penetration test, DR drills,
runbooks, on-call.

**Exit criteria**
- [ ] Load tested at projected launch peak with headroom, at every tier
- [ ] Licensor reports reconcile against recorded playback decisions
- [ ] External penetration test findings resolved or formally accepted
- [ ] DR drill meets the agreed RPO/RTO
- [ ] Every alert has a runbook; every runbook has been walked through

---

## Cross-phase invariants

These hold from Phase 1 and are never traded away for schedule:

1. **Default deny.** Absence of a right or an entitlement is a prohibition.
2. **No secret in the repository.** Ever.
3. **The API contract is append-only within a major version.**
4. **No production data outside production.**
5. **Every third-party capability is verified before it is depended upon.**
6. **Nothing is marked done that is stubbed.**
