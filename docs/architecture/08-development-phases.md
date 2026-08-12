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
- [x] **Architecture reviewed and corrected** — 25 findings recorded in
      [`10-architecture-review.md`](10-architecture-review.md); the documents are internally
      consistent
- [ ] **Open questions OQ-1 … OQ-4, OQ-13, OQ-16, OQ-17 answered by the product owner** ← blocks Phase 1
- [ ] **ADR-0007 decided** (Laravel major version, and the PHP pin that follows from it) ← blocks Phase 1

---

## Phase 1 — Platform skeleton  *(delivered 2026-08-11)*

The minimum real system: one Laravel service, one database, CI that actually verifies things, and
one genuine end-to-end feature (authentication). Nothing simulated.

**Scope**
- Monorepo tooling: pnpm workspace, root `Makefile`, shared configs
- `services/core-api`: Laravel, PHP 8.3+, modular structure with **CI-enforced** boundaries
- Modules with real implementations: `Identity`, `Profile`, `Device` (registration only),
  `Administration` (staff auth + audit skeleton)
- PostgreSQL schema-per-context, migration conventions, seed dataset
- **Connection pooler with a separate pool per deployment profile** — not deferred; retrofitting it
  means revisiting every transaction-scoped assumption in the codebase
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
- [~] `make up` gives a working stack on a clean machine from a fresh clone —
      *stack authored and its compose definition validated; **not started**, because the
      initialisation container has no Docker daemon (E1). Must be confirmed on a developer
      machine or a CI runner before this box is ticked.*
- [x] A user can register, verify email, sign in on a device, refresh a token, sign out — against the
      real database, exercised by an end-to-end test
- [x] TV **device activation (pairing code) flow** works end to end — proves the hardest auth
      interaction before any TV app exists
- [~] CI fails on: boundary violation, spec drift, failing test, known-vulnerable dependency,
      committed secret — *pipeline authored; the boundary, spec-drift, formatting and test gates
      were executed locally. The PHPStan gate is configured but **could not be installed here**
      (the egress policy blocks GitHub zipball downloads), so it has run in CI only.*
- [x] A deliberately introduced cross-module import is rejected by CI — **verified**: a temporary
      `Modules\Profile` → `Modules\Identity\Infrastructure\Eloquent` import was added and the
      checker failed the build. It also caught two real violations in the delivered code, both
      fixed by moving code rather than by relaxing the rule
- [x] Every architecture decision made during the phase is recorded as an ADR

---

## Phases 2–4 — delivered together as "Phase 2" *(2026-08-11)*

> **Scope note.** The product owner combined what this plan had as Phase 2
> (content), Phase 3 (commercial) and Phase 4 (playback authorization) into a
> single phase. The headings below are kept so the exit criteria stay traceable.
>
> **Rights & Availability was added to the scope by engineering**, because it was
> not separable: playback authorization without rights is entitlement-only, which
> §1 of the rights document calls "encrypted, not compliant".
>
> Three things in the combined phase are deliberately **not** built, because the
> vendor decisions they depend on are still open. None is stubbed:
>
> - **Payments.** No PSP is selected (OQ-9). Subscriptions and the entitlement
>   they produce are real; no money moves, and the provider columns are null.
> - **DRM.** No vendor agreements exist (OQ-7). `protection` is `null` in the
>   playback response — absent, not a plausible-looking placeholder.
> - **IP geolocation.** No vendor (P11). Territory resolves from configuration
>   and the *method* is recorded on every decision, so the vendor adapter drops
>   in without changing the decision record's shape.

## Phase 2 — Content control plane

**Scope:** `Catalog`, `Schedule` (EPG), channels and lineups, `epg-ingest` service with a real
provider feed, `apps/admin` (Next.js) for content and schedule management, metadata enrichment
behind port P7, image handling and derivatives.

**Exit criteria**
- [~] A real EPG feed ingests on a schedule, with revision handling and reconciliation of corrections —
      *ingest endpoint, revision handling and `(source, source_ref)` reconciliation are built and
      tested; there is no scheduled pull, because no provider is selected (OQ-5)*
- [~] Content operators manage the catalog through the admin UI, with audit trail —
      *the admin **API** and the audit trail are built; `apps/admin` is not*
- [x] Client API v1 serves channels, lineup, and now/next — *time-range windows rather than cursors
      for EPG, which is a deliberate correction recorded in the API conventions*
- [x] Schedule ingestion survives a malformed feed without data loss or manual repair

---

## Phase 3 — Commercial control plane

**Scope:** `Product` (packages, plans, prices, promotions), `Billing` (subscription lifecycle,
invoices, dunning), `Entitlement` (grants, materialised entitlement snapshot), `PaymentGateway`
adapter against a **sandbox only**.

**Exit criteria**
- [~] Full subscription lifecycle exercised in tests including trials, renewals, cancellation and
      reactivation — *built and tested. Dunning and payment failure are **not**: both require a PSP*
- [x] Entitlement snapshot correctly reflects every lifecycle transition
- [ ] Every payment operation is idempotent under replay; webhooks signature-verified —
      *blocked on OQ-9; no payment code exists to be idempotent yet*
- [ ] P1 verification checklist complete and the PSP decision recorded as an ADR — *blocked on OQ-9*
- [x] **No production payment credentials exist anywhere in the system**

---

## Phase 4 — Playback authorization and live MVP

The first end-to-end stream. Deliberately narrow: one channel, clear or test-key protected.

**Scope:** `Rights` + availability projection, `Playback` authorization, concurrency accounting,
`Delivery` control with one CDN adapter, `playback-authorizer` deployment profile, live ingest →
transcode → package → origin → CDN for **one** channel, geo determination (P11).

**Exit criteria**
- [ ] One live channel plays end to end on a browser, from a real CDN, through a real origin —
      *the authorization half is complete and returns signed origin-addressed delivery targets.
      There is no media plane yet: no ingest, no packager, no origin, no CDN (OQ-8)*
- [x] Authorization enforces entitlement, rights window, territory, device class and concurrency —
      each verified by a test that proves the **denial** path with a specific reason code
- [x] Every decision is recorded with its inputs — availability version, rights rule ids, entitlement
      grant ids, territory and the method by which it was determined
- [ ] Load test at the target concurrency meets the p99 latency objective — *not run; needs OQ-2*
- [ ] Every degraded mode has defined, tested behaviour — including the **denial** paths, and
      including the distinction between a plan concurrency limit (may fail open) and a
      licensor-mandated cap (must not)
- [ ] **Synthetic playback probes** running against the live channel from more than one region,
      exercising both success and denial ([`../operations/observability.md`](../operations/observability.md) §4a)
- [ ] CDN cache offload ratio measured and above the modelled threshold — proves the delivery token
      is not in the cache key
- [ ] **OQ-11 (advertising/SSAI) answered** — after this phase, retrofitting SSAI is expensive

---

## Media plane, part one — delivered as "Phase 3" *(2026-08-12)*

> **Scope note.** The product owner asked for FFmpeg, HLS, origin, CDN and
> streaming sessions as one phase. That cuts across Phase 4 (delivery,
> streaming sessions) and Phase 5 (`MediaAsset`, the encoding ladder) in the
> plan above, so the exit criteria are recorded against both rather than
> renumbered.
>
> Four things in this scope are deliberately **not** built, because the
> decisions they depend on are open. None is stubbed:
>
> - **No CDN vendor adapter.** No vendor is selected (OQ-8) and the P3
>   verification checklist is not complete, so no adapter exists — and neither
>   does a speculative interface shaped around an imagined vendor. The
>   `CdnProvider` port carries only what the platform needs today. Delivery is
>   origin-direct, which is a real delivery mode rather than a placeholder.
> - **No FFmpeg process runner.** The encode *arguments* are built and
>   golden-tested; nothing executes them. This environment has no FFmpeg
>   binary, so a runner written here could not be exercised even on its failure
>   path — and a component with no tested failure path is not done (§8).
> - **No muxer stage.** Packager selection is open with a licence gate on it,
>   so the generated command stops before the output stage and says so, rather
>   than emitting flags for an unchosen packager.
> - **No encoder is licensed.** The allow-list is empty by default and an empty
>   list permits nothing, so a fresh deployment cannot create a ladder until a
>   clearance is recorded. That is the intended first-run experience.

**Scope delivered:** `Media` context (encoding ladders, packaging profiles,
publications, device profiles, generated manifests); HLS multivariant and DASH
MPD generation per quality class ([ADR-0012](adr/ADR-0012-manifest-generation-and-quality-classes.md));
FFmpeg encode-argument construction; origin addressing, delivery tokens and the
origin authorization sub-request; `CdnProvider` port with an origin-direct
adapter and ordered multi-edge planning; mid-session revalidation, delivery
renewal on heartbeat, session listing and a stale-session reaper.

**Exit criteria**
- [x] Manifests pass golden-file tests — *four committed golden files, compared
      byte-for-byte; the DASH output is additionally parsed rather than only
      diffed, because a golden file would happily record malformed XML*
- [ ] Manifests validated by an **independent** tool as well as our own —
      *not done; no validator is reachable from this environment*
- [x] A resolution cap is enforced where a client cannot override it — the
      capped manifest does not mention the rungs the viewer may not have
- [x] **Cache offload is provable, not asserted** — the origin was run against a
      live `core-api` and a second viewer's token confirmed to hit the same
      cached segment. This is the property the whole delivery cost model rests
      on, and it is the one thing here that cannot be established by reasoning
- [x] The origin authorizer does **no database work**, asserted by a test —
      without that it could not run at segment request rate
- [x] A blackout beginning mid-event stops sessions already playing, with the
      specific reason code, within a bounded and configured lag
- [x] A crashed client's session and concurrency slot are reclaimed
- [x] Every ladder defect that breaks ABR switching invisibly — GOP/segment
      misalignment, non-divisor frame rates, inconsistent aspect ratios — is
      refused at configuration time
- [ ] A real encode runs and its output plays — *blocked: no FFmpeg binary and
      no cleared encoder licence. The argument vector is the contract, and it is
      tested; that a binary accepts it is not*
- [ ] One live channel plays end to end from a real CDN — *blocked on OQ-8 and
      on there being a packager*
- [ ] CDN cache offload ratio measured in production against the modelled
      threshold — *the mechanism is proven locally; the ratio needs traffic*

---

## Operator panel — delivered as "Phase 4" *(2026-08-12)*

> **Scope note.** The product owner asked for an admin panel. Two things had to
> exist before one could be built honestly, and both were done first:
>
> - **The admin API had no OpenAPI contract.** The client surface has been
>   conformance-tested since Phase 1; the admin surface had never been. Building
>   a panel against an unspecified API would breach the rule that the spec *is*
>   the contract, and would make the generated-client rule unsatisfiable.
> - **The admin API was write-only** — 20 routes, almost all `POST`. A panel over
>   that is a set of forms with no way to see what they produced.
>
> The Vite/React/Supabase starter at the repository root (**OQ-17**, risk R14)
> was also moved to `legacy/`, because Phase 4 needed the root to become a real
> workspace and could not leave a second, broken front-end root in it. Nothing
> was deleted; deletion remains the product owner's decision.

**Scope delivered:** 14 admin read endpoints with role gating; the admin
OpenAPI contract, conformance-tested in both directions alongside the client
one; `packages/ts-api-client` generated from both specs with a CI drift gate;
`apps/admin` — a static SPA with staff sign-in, role-aware navigation, and
screens for channels, categories, plans, rights, media, playback decisions,
sessions, audit and staff.

**Exit criteria**
- [x] Every admin endpoint is in the contract, verified in **both** directions —
      an endpoint that ships without contract review is how a spec becomes
      fiction
- [x] Every admin operation states the roles that may reach it, checked by a
      test — an operation whose access rules were never written down is very
      likely open to every signed-in staff member
- [x] Read access is wider than write access, and a role that was not named is
      refused — proved per route
- [x] The panel makes **no hand-written call** to our own API; a contract change
      that nobody regenerated for fails CI
- [x] A staff token is never written to any browser storage, asserted by a test
      ([ADR-0013](adr/ADR-0013-admin-panel-stack.md))
- [x] Failures show the stable error code and the correlation id, so a
      screenshot is enough to find the request
- [x] The decision log is reachable by support without exposing personal data —
      identifiers only, and no search by name or email
- [ ] Used by real operators against real data — *not yet; the panel has been
      built and tested but nobody has run a shift on it, and that is the only
      test that finds what an admin tool actually lacks*
- [ ] `apps/web` — *out of scope here, and not bound by ADR-0013*

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
