# System Context and Scope

## 1. What KMS TV is

KMS TV is an OTT/IPTV platform: it acquires video (live channels and on-demand titles), attaches
metadata and commercial rules to it, sells access to it, and delivers it to consumer devices under
contractual and technical protection.

The system has four planes. Keeping them separate is the single most important structural idea in
this architecture, because they differ in traffic profile, failure impact, cost driver, and change
rate by one to three orders of magnitude.

| Plane | What it does | Traffic profile | If it fails |
|---|---|---|---|
| **Control plane** | Accounts, catalog, EPG, rights, commerce, admin. The system of record. | Low RPS, transactional, strongly consistent | New sign-ups and purchases stop; existing playback largely continues |
| **Decision plane** | Playback authorization, entitlement resolution, DRM licence policy. | High RPS, latency-critical, read-mostly | **Nobody can start a stream.** Highest-criticality path in the platform |
| **Media plane** | Ingest, transcode, package, origin, CDN. Bytes. | Sustained high bandwidth, cost-dominant | Playback fails or degrades for everyone |
| **Insight plane** | Telemetry, QoE, business analytics, licensor reporting. | Very high write volume, eventually consistent | No visibility; royalty reporting at risk. Playback unaffected |

A design that mixes these planes — for example, putting playback authorization in the same
deployment unit and database connection pool as the admin control centre — will fail at the worst
possible moment, because an admin bulk import will take out playback. The architecture keeps them
separable from day one even while they share a codebase (see
[`04-service-boundaries.md`](04-service-boundaries.md)).

## 2. Actors

### Human actors

| Actor | Uses | Primary needs |
|---|---|---|
| **Viewer** | Web, Android, Android TV, iOS/iPadOS, Tizen, webOS | Sign in on a TV without a keyboard; find something to watch; press play and have it work |
| **Account holder** | Web, apps | Billing, plan changes, devices, profiles, parental controls |
| **Content operator** | Admin Control Center | Ingest titles, schedule channels, fix metadata, manage images |
| **Rights manager** | Admin Control Center | Enter licence windows, territories, platform restrictions, blackouts |
| **Commercial operator** | Admin Control Center | Packages, pricing, promotions, vouchers |
| **Customer support** | Admin Control Center | See a subscriber's state, devices, payments; act on their behalf under audit |
| **Broadcast operations** | Ops tooling, monitoring | Channel health, ingest failures, origin/CDN state |
| **Platform engineer** | CI/CD, IaC, observability | Deploy safely, diagnose, roll back |
| **Finance / licensor reporting** | Reports | Revenue, royalty and usage reports that stand up to audit |

### External systems

| External system | Direction | Notes |
|---|---|---|
| Content sources (playout, satellite IRD, contribution feeds, mezzanine file delivery) | In | Format and handover are **unknown** — OQ-4 |
| EPG / schedule data provider | In | Provider unknown — OQ-5 |
| Metadata enrichment provider (artwork, synopses, cast) | In | Licence terms must be checked; many are not redistributable |
| DRM licence servers (Widevine, PlayReady, FairPlay) | Out | Require commercial agreements — long lead time, OQ-7 |
| CDN(s) | Out | Vendor(s) unknown — OQ-8 |
| Payment service provider / operator billing | Both | Unknown, and market-dependent — OQ-9 |
| Push/notification services (APNs, FCM), email/SMS | Out | |
| App stores (Google Play, App Store, Samsung Seller Office, LG Content Store) | Out | Certification gates the release train |
| Identity providers (social, operator SSO/MSISDN) | In | Optional, market-dependent |

## 3. Product scope

The full product scope from the brief, grouped by the bounded context that owns it
(see [`02-bounded-contexts.md`](02-bounded-contexts.md)):

- **Viewing:** Live TV, EPG, VOD, Movies, Series, Catch-up TV, Restart TV
- **Customer:** User accounts, Profiles, Devices
- **Commercial:** Subscriptions, Packages, Payments, Entitlements
- **Access:** Playback authorization, DRM, Rights management
- **Operations:** Admin Control Center, Analytics, Monitoring, Security, CI/CD
- **Clients:** Web, Android, Android TV, iOS/iPadOS, Samsung Tizen, LG webOS
- **Delivery:** Streaming infrastructure, CDN, Origin

### Explicitly out of scope for now

Not because they are unimportant, but because committing to them without answers would distort the
architecture. Each is an open question, not an omission:

- **Advertising / SSAI.** Ad-supported tiers change packaging, manifest handling, and the player
  contract fundamentally. Retrofitting SSAI is expensive. **OQ-11 must be answered before Phase 4.**
- **Offline downloads.** Requires persistent DRM licences, a separate rights dimension, and device
  storage management. **OQ-12.**
- **Multi-tenant / white-label.** If a second brand is ever likely, tenancy must be in the data model
  from the first migration, not added later. **OQ-13.**
- **4K / HDR / Dolby.** Changes the encoding ladder, the CDN cost model, and the DRM security-level
  requirements. **OQ-14.**
- **Recommendations.** Deliberately deferred until there is behavioural data to learn from.

## 4. Quality attributes that drive the design

These are the properties the architecture is optimised for, in priority order. They are stated as
targets to be ratified — the numbers are placeholders until OQ-1/OQ-2 give real audience figures.

| # | Attribute | Target (to ratify) | Where it is addressed |
|---|---|---|---|
| 1 | **Playback start reliability** | ≥ 99.95% of authorized play requests result in a playing stream | Decision plane isolation, CDN abstraction, [`docs/streaming/`](../streaming/) |
| 2 | **Playback authorization latency** | p99 ≤ 150 ms server-side | [`04-service-boundaries.md`](04-service-boundaries.md), [`docs/security/playback-authorization.md`](../security/playback-authorization.md) |
| 3 | **Rights correctness** | Zero unauthorized plays; every decision auditable and explainable | [`06-rights-management.md`](06-rights-management.md) |
| 4 | **Revenue integrity** | No double charging, no free access after cancellation, idempotent payment handling | [`docs/database/`](../database/), [`docs/api/conventions.md`](../api/conventions.md) |
| 5 | **Client longevity** | A Tizen/webOS app shipped today keeps working for ≥ 3 years without a forced update | [`docs/api/versioning.md`](../api/versioning.md) |
| 6 | **Delivery cost per viewing hour** | Modelled and monitored from Phase 4 | [`docs/streaming/packaging-and-delivery.md`](../streaming/packaging-and-delivery.md) |
| 7 | **Time to diagnose** | Any playback failure traceable end-to-end from a session ID | [`docs/operations/observability.md`](../operations/observability.md) |

Attribute 5 deserves emphasis because it is the one most often underestimated. Smart TV
applications are updated slowly and unevenly; a meaningful share of installed apps will be months or
years behind. **The server contract is therefore effectively append-only.** This constraint shapes
API versioning, the client capability model, and the release process.
