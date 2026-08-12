# KMS TV — Architecture

**Status:** Phase 0 — foundation. No application code exists yet.
**Last updated:** 2026-08-10 (revised after the Phase 0 architecture review)

> This document has been through one full review. The 25 findings and their corrections are recorded
> in [`docs/architecture/10-architecture-review.md`](docs/architecture/10-architecture-review.md) —
> read it before assuming a design here is arbitrary, because several choices are the *second*
> answer, and the first one is documented alongside why it failed.

This is the entry point. It summarises the architecture and links to the detail. Every claim here is
either verifiable in this repository, was verified against a package registry on 2026-08-10, or is
explicitly marked as undecided or unverified.

---

## 1. What KMS TV is

An OTT/IPTV platform: it acquires video (live channels and on-demand titles), attaches metadata and
commercial rules to it, sells access to it, and delivers it to consumer devices under contractual and
technical protection.

The system separates into **four planes**, and keeping them separate is the most important structural
idea here. They differ in traffic profile, failure impact, cost driver and change rate by one to
three orders of magnitude.

| Plane | Contains | Traffic | If it fails |
|---|---|---|---|
| **Control** | Accounts, catalog, EPG, rights, commerce, admin | Low RPS, transactional | Sign-ups and admin stop; playback continues |
| **Decision** | Playback authorization, entitlements, DRM policy | High RPS, latency-critical | **Nobody can start a stream** |
| **Media** | Ingest, transcode, package, origin, CDN | Bandwidth-dominant | Playback fails or degrades for everyone |
| **Insight** | Telemetry, QoE, analytics, licensor reporting | Write-heavy | No visibility; playback unaffected |

Detail: [`docs/architecture/01-system-context.md`](docs/architecture/01-system-context.md)

## 2. Bounded contexts

Seventeen contexts in five groups. Contexts are **logical**; how they map to deployment units is a
separate decision. **17 contexts, 16 `core-api` modules, 16 database schemas** — E2 Analytics &
Telemetry deliberately has neither, because it lives in `telemetry-collector` and the analytics store.

| Group | Contexts |
|---|---|
| **Customer** | Identity & Access · Profiles & Personalization · Device Management |
| **Content** | Catalog · Channels & Schedule (EPG) · Media Assets & Processing · Discovery & Merchandising |
| **Commercial** | Product & Packaging · Subscriptions & Billing · Entitlements |
| **Access** | Rights & Availability · Playback Authorization · Content Protection (DRM) · Delivery Control |
| **Platform** | Administration & Audit · Analytics & Telemetry · Notifications |

Four rules hold across the map:

1. **Rights is upstream of everything that shows or plays content.** If Rights says no, nothing else
   may say yes.
2. **Playback Authorization depends on many contexts and is depended on by none** — which is what
   makes it independently deployable and scalable.
3. **Analytics is never synchronous.** Nothing blocks on it.
4. **Cross-context references are by identifier only.** No foreign keys across context boundaries.

Detail and context map: [`docs/architecture/02-bounded-contexts.md`](docs/architecture/02-bounded-contexts.md)

## 3. Repository structure

```
kms/
├── apps/             # client applications      (none yet — created per phase)
├── services/         # backend services         (none yet — core-api in Phase 1)
├── packages/         # shared libraries         (none yet — contracts in Phase 1)
├── infrastructure/   # containers, IaC, edge, observability
├── docs/             # architecture · database · api · security · streaming · operations
├── ARCHITECTURE.md
├── CLAUDE.md         # engineering rules for every contributor
└── README.md
```

`core-api` is a **modular monolith**: one Laravel codebase with one module per bounded context, and a
boundary rule enforced by CI —

> A module may import from another module **only** through that module's `Contracts/` namespace.

Detail: [`docs/architecture/03-repository-structure.md`](docs/architecture/03-repository-structure.md)

## 4. Service boundaries

One codebase, several **runtime profiles**, plus separate services only where the reason is technical
and specific.

| Deployment unit | Why separate | Phase |
|---|---|---|
| core-api (web / admin / workers) | Same artifact, different configuration. Admin load must never contend with subscriber traffic | 1–2 |
| **playback-authorizer** | Latency-critical, high-RPS, isolated failure domain. Same artifact initially | 4 |
| **drm-license-proxy** | **Security isolation** — the only component that can resolve a key by id | 6 |
| media-pipeline | Different runtime: FFmpeg, large disk, possibly GPU | 5 |
| epg-ingest | A malformed provider feed must not exhaust web-tier workers | 2 |
| telemetry-collector | Write volume orders of magnitude higher; droppable under load | 10 |

Detail: [`docs/architecture/04-service-boundaries.md`](docs/architecture/04-service-boundaries.md)

## 5. The critical path

```
client ──► playback-authorizer
              │  rights ∧ entitlement ∧ parental ∧ device/concurrency ∧ territory
              │  (first failure wins, with a SPECIFIC reason code)
              ▼
        decision + audit record
              │
      ┌───────┴────────┐
      ▼                ▼
 delivery token   licence token
 (CDN, minutes)   (content-, session- and device-bound)
                        │
   player ──────────────► drm-license-proxy ──► DRM vendor
```

Constraints, enforced in review and load tests:

- **No synchronous call to Billing.** It reads a materialised entitlement snapshot, so billing being
  down does not stop existing subscribers watching.
- **No synchronous third-party call** in the authorization path.
- **Every dependency has a defined, tested degraded behaviour.** Fail closed on rights and
  entitlement, always.
- **If the audit record cannot be written, playback does not start.** An unauditable decision is
  indefensible to a licensor.

Detail: [`docs/security/playback-authorization.md`](docs/security/playback-authorization.md)

## 6. Rights vs DRM vs entitlements

The distinction most platforms get wrong, and the one with the largest commercial consequence:

```
CAN PLAY = Rights allow it        (contractual — licence windows, territories, platforms)
         ∧ Entitlement grants it  (commercial — what the customer bought)
         ∧ Parental permits it    (household)
         ∧ Device/concurrency OK  (plan + licensor limits)
         ∧ Territory matches      (contractual + technical)
```

**DRM enforces only a subset of this.** DRM has no concept of a licence window, a territory, or a
subscription. A platform relying on DRM for rights compliance is not compliant — it is encrypted.

Detail: [`docs/architecture/06-rights-management.md`](docs/architecture/06-rights-management.md)

## 7. Data

- **PostgreSQL is the system of record.** One cluster, one database, **one schema per bounded
  context**, **no foreign keys across schemas**, and a database role per deployment profile.
- **Redis is never a system of record.** Everything in it is reconstructible or acceptable to lose.
- **Telemetry never touches the transactional database** — it is the largest dataset and the least
  valuable per row.
- High-volume time-based tables are **partitioned from their first migration**.
- Public identifiers are UUIDv7; sequential integers are never exposed.
- **A connection pooler sits in front of PostgreSQL from Phase 1**, with a separate pool per
  deployment profile — PHP-FPM is process-per-request, and connection exhaustion presents as a total
  outage rather than as gradual slowdown.
- Schema changes use **expand/contract**; migrations are forward-only in production.

Detail: [`docs/database/`](docs/database/)

## 8. API

Four versioned surfaces, because their consumers have irreconcilable change rates:

```
/api/client/v1     viewer apps      ≥ 24 months support after a successor ships
/api/admin/v1      Control Center   3 months
/api/partner/v1    B2B              contractual, ≥ 12 months
internal           service-to-service, never routed at the edge
```

**Within a major version, the client API is append-only** — structurally, with breaking-change
detection enforced in CI. A Smart TV application reaches its installed base over months to years, and
some devices effectively stop updating. The contract must outlive the client.

The OpenAPI spec is **the contract**, not a description generated from the implementation.

**A personalised response is never `public`-cacheable.** Catalog metadata (varies by locale and
territory only) and the personalised availability overlay are therefore **separate responses** —
merging them means either leaking one viewer's entitlements to another through a shared cache, or
giving up caching on the largest payload in the product.

Detail: [`docs/api/`](docs/api/)

## 9. Security

Four trust zones, with the last one carrying the whole design:

```
untrusted (clients) → edge → application → PROTECTED (licence proxy + key vault)
```

**The licence proxy is the only component that can resolve a content key by identifier.** The
packager also handles keys — it must, in order to encrypt — but only ones **pushed** to it per job,
with no ability to query the vault, so it cannot be used as a key oracle. `core-api` cannot reach key
material at all: if compromising it yielded content keys, every other control would be decoration.

**Key material is backed up under split control, and the restore is rehearsed.** Losing the vault
makes the entire encrypted library permanently unplayable — a larger single-event risk than losing
the application database, and one usually missed because key management is filed under security
rather than availability.

Other load-bearing positions: default deny everywhere; the client is never a security boundary; fail
closed on the decision path; no secret in the repository, ever; no production data outside production.

Detail: [`docs/security/`](docs/security/)

## 10. Streaming

```
contribution → transcode → CMAF fMP4 (encrypted once) → origin+shield → CDN → player
                                │                                        │
                          content key (vault)                    delivery token
```

- **One CMAF encode with `cbcs` encryption serving HLS, DASH and all three DRM systems** — an
  economic decision as much as a technical one, since separate segment sets fragment the CDN cache
  and cache offload is the dominant lever on origin cost. **Proposed, pending device verification**
  ([ADR-0005](docs/architecture/adr/ADR-0005-cmaf-multi-drm.md)).
- **Origin is ours; the CDN is a cache.** Deleting the CDN loses performance, never content.
- **Delivery targets are a list from day one**, so multi-CDN needs no client update later.
- **Two independent fallback axes** — encryption scheme (`cbcs`/`cenc`) *and* container (fMP4/TS).
  A device that cannot play fMP4 under HLS is not helped by re-encrypting it.
- **The delivery token is validated at the edge but excluded from the CDN cache key.** Leaving it in
  gives every viewer a private copy of every segment and collapses the offload the single-encode
  decision exists to protect.
- **Catch-up and restart are rights, not features** — including content that must never be written to
  the recording buffer at all.

Detail: [`docs/streaming/`](docs/streaming/)

---

## 11. Major technical decisions

| # | Decision | Status | Record |
|---|---|---|---|
| 1 | Modular monolith first; extraction requires evidence | Accepted | [ADR-0001](docs/architecture/adr/ADR-0001-modular-monolith-first.md) |
| 2 | Monorepo with per-workspace native tooling | Accepted | [ADR-0002](docs/architecture/adr/ADR-0002-monorepo-layout-and-tooling.md) |
| 3 | PostgreSQL system of record; schema per context; no cross-schema FKs | Accepted | [ADR-0003](docs/architecture/adr/ADR-0003-postgresql-system-of-record.md) |
| 4 | Versioned API surfaces; append-only within a major | Accepted | [ADR-0004](docs/architecture/adr/ADR-0004-api-versioning.md) |
| 5 | CMAF + `cbcs` single-encode multi-DRM | **Proposed** — needs device verification | [ADR-0005](docs/architecture/adr/ADR-0005-cmaf-multi-drm.md) |
| 6 | Two-token playback authorization (delivery + licence) | Accepted | [ADR-0006](docs/architecture/adr/ADR-0006-playback-authorization-tokens.md) |
| 7 | **Laravel major version** | **Proposed — owner decision required** | [ADR-0007](docs/architecture/adr/ADR-0007-laravel-major-version.md) |
| 8 | CDN and origin behind a delivery port | Accepted | [ADR-0008](docs/architecture/adr/ADR-0008-cdn-and-origin-abstraction.md) |
| 9 | PSP-agnostic payments; no card data on our infrastructure | Accepted | [ADR-0009](docs/architecture/adr/ADR-0009-payments-boundary.md) |
| 10 | Contract-first OpenAPI as the source of truth | Accepted | [ADR-0010](docs/architecture/adr/ADR-0010-contract-first-openapi.md) |
| 11 | Factorised availability projection (not a full cross-product) | Accepted | [ADR-0011](docs/architecture/adr/ADR-0011-availability-projection-shape.md) |
| 12 | Manifests generated by the control plane, one per quality class | Accepted | [ADR-0012](docs/architecture/adr/ADR-0012-manifest-generation-and-quality-classes.md) |
| 13 | Operator panel is a static SPA; staff token held in memory only | Accepted | [ADR-0013](docs/architecture/adr/ADR-0013-admin-panel-stack.md) |

---

## 12. Risks

Ordered by expected impact. Each has an owner-facing action, not just a description.

| # | Risk | Impact | Likelihood | Response |
|---|---|---|---|---|
| **R1** | **DRM agreements (Widevine, PlayReady, FairPlay) are three separate commercial processes and are almost certainly the longest-lead items in the programme** | Blocks launch entirely | High | **Start all three during Phase 1**, in parallel with engineering. The code is short; the agreements are not |
| **R2** | Vendor capabilities are **unverified** — the initialisation environment cannot reach vendor documentation | Design built on assumptions | Certain today | Every such claim is marked `[UNVERIFIED]` with a checklist. **No adapter is built before its checklist is complete** |
| **R3** | Six client platforms is a very large surface for an unknown team | Schedule and quality | High | Shared `*-player-core` and `ts-ui-tv` packages; phased rollout; **OQ-18 must be answered before scheduling anything** |
| **R4** | Rights misconfiguration serves content where it is not licensed | **Contract breach** — commercially the most serious non-key risk | Medium | Availability preview before commit; 4-eyes approval; append-only effective-dated data; every decision records the rules applied |
| **R5** | Content key compromise | Catastrophic: loss of content deals | Low | Protected zone; licence proxy as sole key reader; rotation; immediate paging on any out-of-band key read |
| **R6** | Delivery cost is unmodelled and is usually the largest running cost | Financial viability | Certain until OQ-1/2/6 are answered | Cost per viewing hour tracked from Phase 4; CMAF single-encode protects cache offload; nDVR retention treated as a commercial decision |
| **R7** | Laravel 12 is ~18 months into its lifecycle; a framework upgrade will likely land mid-build | Rework during delivery | Medium-high | **Decide ADR-0007 before Phase 1.** Cheapest moment to be current is before code exists |
| **R8** | SSAI/advertising deferred; retrofitting it into packaging and manifests is expensive | Significant rework | Medium | **OQ-11 must be answered before Phase 4** |
| **R9** | Smart TV certification and propagation add months, and a shipped TV app cannot be rolled back | Release risk | High | Server contract is append-only; device matrix is data; `/config` carries per-version behaviour so bugs are fixable server-side |
| **R10** | Rights data arrives late, incomplete, and in spreadsheets | Content unavailable or wrongly available | High | Bulk import with validation, dry-run and diff; default deny; expiry reporting |
| **R11** | nDVR storage growth is the largest single storage line and compounds silently | Cost | High | Per-channel retention policy as data; tiering by age; automatic expiry with monitoring |
| **R12** | The module boundary check is the load-bearing part of ADR-0001. If it is not built in Phase 1, the monolith degrades within weeks | Architecture erosion | Medium | It is a Phase 1 exit criterion, verified by deliberately introducing a violation and confirming CI rejects it |
| **R13** | Payment and data-protection obligations vary by market, and markets are unknown | Compliance, rework | Medium | PSP behind a port; PCI SAQ A by design; **OQ-1 gates the compliance analysis** |
| **R14** | This repository contains an unrelated, non-building starter with a Supabase dependency | Confusion; conflicting direction | Certain | Quarantined in `legacy/` (Phase 4) and excluded from the workspace, lint, formatting and type-checking. Deletion remains **OQ-17** |
| **R15** | Single-CDN dependency at launch | Regional outage = regional blackout | Medium | Delivery port and target list from Phase 4; steering in Phase 10 |
| **R16** | Development environment cannot run containers, databases, FFmpeg or device toolchains | Unverifiable work | Certain in this container | Recorded in the inspection report; CI runners and developer machines must provide them |
| **R17** | **Loss of the content key vault makes the entire encrypted library permanently unplayable** — a larger single-event loss than the application database | Catastrophic | Low | Backup under a separate root of trust in a separate failure domain; unseal material escrowed under split control; RPO ≈ 0; **rehearsed** restore ([`docs/security/secrets-and-key-management.md`](docs/security/secrets-and-key-management.md) §3a) |
| **R18** | Rights recompute lag: a broad rights correction must propagate in seconds, not hours, or the platform serves content it is not licensed to serve | Contract breach | Medium | Factorised, interned projection so recompute is proportional to the change ([ADR-0011](docs/architecture/adr/ADR-0011-availability-projection-shape.md)); projection lag is an alerted SLO |
| **R19** | Old televisions cannot negotiate current TLS, or stop trusting our certificate chain's root — devices simply never connect, with no server-side error | Silent loss of a device population | Medium | TLS 1.2 floor at the edge; chain and root treated as a device-compatibility decision, verified against the oldest devices and before any CA change |
| **R20** | Connection exhaustion from process-per-request workers presents as a total outage, not as slowdown | Outage | Medium-high | Pooler from Phase 1, separate pool per deployment profile, saturation alerting |
| **R21** | A CDN cache-key misconfiguration collapses offload to zero and silently multiplies origin egress — the largest cost line | Financial | Medium | Everything per-viewer is packed into **one** opaque query parameter, so an edge has exactly one thing to exclude — validated but not keyed on. Proven against a running origin: a second viewer's token hits the same cached segment. Offload ratio alerted as a cost incident |
| **R22** | A resolution cap enforced only by the client is not enforced at all — a licensor asking how a cap is applied cannot be answered with "the app respects it" | Contract breach | Medium | Caps are quantised to a small closed set of quality classes and served as **different manifests**; a capped viewer's manifest does not mention the rungs they may not have ([ADR-0012](docs/architecture/adr/ADR-0012-manifest-generation-and-quality-classes.md)) |
| **R23** | A blackout beginning mid-event stops only *new* sessions, so the viewers a licensor is actually asking about keep watching | Contract breach | High | Live sessions are revalidated on heartbeat at a configured interval, which is the stated maximum enforcement lag; a mid-session denial is recorded exactly like an initial one |
| **R24** | ABR renditions drift out of alignment — misalignment produces artefacts and stalls **only at switch points**, so it looks perfect on a fast connection and fails for viewers on variable networks | Quality | High | GOP/segment divisibility, whole-frame GOPs at the ladder's rate, and integer-divisor frame rates are all refused at configuration time rather than discovered in the field |
| **R25** | An encoder is run without a cleared licence, changing the licence of the deployed binary | Legal | Medium | The permitted-encoder allow-list is **empty by default and permits nothing**; no ladder can be created until a clearance is recorded ([`docs/architecture/09-dependency-policy.md`](docs/architecture/09-dependency-policy.md)) |

---

## 13. Open questions

**Blocking Phase 1** are marked ⛔. Others are marked with the phase by which they must be answered.

### Product and commercial
| # | Question | Needed by |
|---|---|---|
| **OQ-1** | Which launch markets/territories? Drives compliance, payment methods, rights, data residency, CDN | ⛔ |
| **OQ-2** | Expected audience: subscribers, peak concurrent streams, growth curve | ⛔ |
| **OQ-6** | How many channels? Which are catch-up enabled? | P2 |
| **OQ-11** | **Advertising / SSAI in scope?** Changes packaging and manifests fundamentally | P4 |
| **OQ-12** | Offline downloads in scope? Changes DRM, rights and every client | P6 |
| **OQ-14** | 4K / HDR in scope? Changes ladder, cost, DRM security levels, device matrix | P4 |
| **OQ-13** | **Multi-tenant / white-label ever likely?** Tenancy must be in the data model from the first migration | ⛔ |
| **OQ-25** | Account-sharing enforcement thresholds — a product decision, not an engineering default | P4 |
| **OQ-27** | nDVR retention window per channel — the largest storage cost decision | P7 |
| **OQ-21** | Target launch date | ⛔ (for any scheduling) |

### Content and delivery
| # | Question | Needed by |
|---|---|---|
| **OQ-3** | Device matrix: which platforms, which model years, oldest supported | P4 |
| **OQ-4** | **Content sources**: playout, satellite, contribution, mezzanine delivery? Handover format? Is as-run data available? | ⛔ |
| **OQ-5** | EPG provider, and redistribution rights for its data | P2 |
| **OQ-7** | DRM: direct integration or a multi-DRM vendor | P4 (start now — R1) |
| **OQ-8** | CDN vendor(s) | P4 |
| **OQ-28** | Live captioning required? | P4 |

### Commercial systems
| # | Question | Needed by |
|---|---|---|
| **OQ-9** | Payment provider / operator billing — market-dependent | P3 |
| **OQ-10** | Are app-store billing rules mandatory for iOS/Android sign-ups in our model? Major revenue impact | P3 |
| **OQ-22** | Tax, invoicing and revenue-recognition requirements per market | P3 |

### Platform and governance
| # | Question | Needed by |
|---|---|---|
| **OQ-16** | Hosting: cloud provider, regions, orchestration | ⛔ (shapes Phase 1 infrastructure) |
| **OQ-17** | **What happens to the existing Vite/React/Supabase starter?** Moved to `legacy/bolt-starter/` in Phase 4 so the repository root could become a real workspace; nothing was deleted. It does not build (no `src/`) and its Supabase dependency conflicts with the stated direction. Deleting it is `git rm -r legacy/` — **still the product owner's call** | ◐ |
| **OQ-18** | Team size and composition | ⛔ (for any scheduling) |
| **OQ-15** | Does the iOS app live in this repository or its own? | P8 |
| **OQ-19** | Playback decision retention period — **contractual**, not an engineering preference | P4 |
| **OQ-20** | Territory determination: which signal wins when IP, billing address and SIM country disagree? Commercial decision | P4 |
| **OQ-23** | Entitlement snapshot staleness budget: how long may playback continue after cancellation? | P4 |
| **OQ-24** | RPO / RTO targets per data class | P3 |
| **OQ-26** | Social login, operator SSO, MSISDN identification — required in the launch markets? | P3 |
| **OQ-29** | **What licence and copyright apply to this repository itself?** There is no `LICENSE` file. Affects contributor terms, any open-sourcing of shared packages, and what may ship inside a client application | P1 |
| **OQ-30** | **Does telemetry ever feed licensor reporting?** If yes, fabricated client events corrupt contractual numbers rather than just our metrics, and reporting must derive exclusively from server-side decision records | P4 |

---

## 14. What happens next

Phase 1 builds the platform skeleton: monorepo tooling, `core-api` with CI-enforced module
boundaries, PostgreSQL and Redis, the OpenAPI contract pipeline, the local Docker stack, an
observability baseline, and one genuinely complete feature — authentication, including TV device
activation.

Full scope and exit criteria:
[`docs/architecture/08-development-phases.md`](docs/architecture/08-development-phases.md)

**Phase 1 is blocked on the ⛔ open questions above and on ADR-0007.**
