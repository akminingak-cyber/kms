# KMS TV — Architecture

**Status:** Phase 0 — foundation. No application code exists yet.
**Last updated:** 2026-08-10

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

Sixteen contexts in five groups. Contexts are **logical**; how they map to deployment units is a
separate decision.

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
| **drm-license-proxy** | **Security isolation** — the only workload with key-vault credentials | 6 |
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
- Public identifiers are ULIDs; sequential integers are never exposed.
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

Detail: [`docs/api/`](docs/api/)

## 9. Security

Four trust zones, with the last one carrying the whole design:

```
untrusted (clients) → edge → application → PROTECTED (licence proxy + key vault)
```

**The licence proxy is the only workload that can read content key material.** If compromising
`core-api` yields content keys, every other control is decoration.

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
| **R14** | This repository contains an unrelated, non-building starter with a Supabase dependency | Confusion; conflicting direction | Certain | **OQ-17 — resolve before Phase 1** |
| **R15** | Single-CDN dependency at launch | Regional outage = regional blackout | Medium | Delivery port and target list from Phase 4; steering in Phase 10 |
| **R16** | Development environment cannot run containers, databases, FFmpeg or device toolchains | Unverifiable work | Certain in this container | Recorded in the inspection report; CI runners and developer machines must provide them |

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
| **OQ-17** | **What happens to the existing Vite/React/Supabase starter in this repository?** Recommendation: remove it, or relocate it if it is a real marketing site. It does not build (no `src/`) and its Supabase dependency conflicts with the stated direction | ⛔ |
| **OQ-18** | Team size and composition | ⛔ (for any scheduling) |
| **OQ-15** | Does the iOS app live in this repository or its own? | P8 |
| **OQ-19** | Playback decision retention period — **contractual**, not an engineering preference | P4 |
| **OQ-20** | Territory determination: which signal wins when IP, billing address and SIM country disagree? Commercial decision | P4 |
| **OQ-23** | Entitlement snapshot staleness budget: how long may playback continue after cancellation? | P4 |
| **OQ-24** | RPO / RTO targets per data class | P3 |
| **OQ-26** | Social login, operator SSO, MSISDN identification — required in the launch markets? | P3 |

---

## 14. What happens next

Phase 1 builds the platform skeleton: monorepo tooling, `core-api` with CI-enforced module
boundaries, PostgreSQL and Redis, the OpenAPI contract pipeline, the local Docker stack, an
observability baseline, and one genuinely complete feature — authentication, including TV device
activation.

Full scope and exit criteria:
[`docs/architecture/08-development-phases.md`](docs/architecture/08-development-phases.md)

**Phase 1 is blocked on the ⛔ open questions above and on ADR-0007.**
