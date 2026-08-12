# Third-Party Integration Boundaries

## 1. The rule

> **No vendor type, SDK class, error, or vocabulary crosses into the domain.**
> Every third party sits behind a port defined by KMS TV in KMS TV's language, with at least one
> adapter, contract tests, and a documented failure mode.

This is not architectural purity. In an OTT platform the vendors *will* change: CDNs are
renegotiated, PSPs are replaced when entering a new market, DRM providers change with volume, EPG
providers are dropped for data quality. A platform that has `Stripe\Customer` or a CDN vendor's
signing scheme spread through its codebase cannot make those changes at business speed.

## 2. Verification requirement (hard constraint)

The environment in which this document was written **cannot reach vendor documentation** — vendor
domains are blocked by the egress proxy (see
[`00-inspection-report.md`](00-inspection-report.md), finding E6).

Therefore: **no vendor capability is asserted anywhere in this repository.** Every integration below
is specified as *what KMS TV needs*, with an explicit verification checklist to be completed by a
human against current vendor documentation and a signed agreement. Where a document elsewhere in
this repo describes vendor behaviour, it is marked `[UNVERIFIED]`.

A port may be designed before verification. **An adapter may not be built before its checklist is
complete**, because building against assumed behaviour is how integration projects lose months.

## 3. Ports

Each port is owned by exactly one bounded context. `packages/php-shared-kernel` holds the shared
value objects the ports speak in; the ports themselves live in their owning module's `Contracts/`.

| # | Port | Owner | Purpose | Needed by |
|---|---|---|---|---|
| P1 | `PaymentGateway` | C2 Billing | Authorize, capture, refund, tokenize, subscribe, webhooks | P3 |
| P2 | `DrmLicenceProvider` | D3 Protection | Content key provisioning, licence issuance per DRM system | P6 |
| P3 | `CdnProvider` | D4 Delivery | Signed URL/token, purge, config reference, log delivery | P4 |
| P4 | `ObjectStorage` | B3 Media | Mezzanine + package storage, multipart upload, lifecycle | P5 |
| P5 | `Transcoder` | B3 Media | Submit encode job, poll/receive status, retrieve outputs | P5 |
| P6 | `ScheduleProvider` | B2 Schedule | Fetch schedule, detect revisions, map channel identifiers | P2 |
| P7 | `MetadataProvider` | B1 Catalog | Enrich titles: synopses, artwork, credits, ratings | P2 |
| P8 | `NotificationChannel` | E3 Notifications | Email, SMS, push (APNs/FCM) | P1 (email), P8 (push) |
| P9 | `IdentityProvider` | A1 Identity | Social / operator SSO / MSISDN login | Optional |
| P10 | `SearchIndex` | B4 Discovery | Index documents, query, faceting | P10 |
| P11 | `GeoLocation` | D2 Playback | IP → territory, VPN/proxy signal | P4 |
| P12 | `AnalyticsSink` | E2 Analytics | Event export to warehouse/BI | P10 |
| P13 | `SecretStore` / `KeyVault` | D3 Protection | Secret retrieval, content key wrapping | P1 (secrets), P6 (keys) |

## 4. Anatomy of an integration

Every integration ships with all six parts. An integration missing any of them is not done.

1. **Port interface** — expressed only in KMS TV domain terms.
2. **Adapter** — the vendor-specific implementation, in `Infrastructure/`.
3. **Error translation** — vendor errors mapped to a KMS TV error taxonomy that distinguishes
   `retryable` / `permanent` / `needs_human`. Retrying a permanent decline is how duplicate charges
   and support escalations happen.
4. **Contract test** — runs against the vendor **sandbox** on a schedule (nightly, not per-PR), and
   fails loudly when vendor behaviour drifts. This is the mechanism that keeps the mocks used in unit
   tests honest.
5. **Fake/in-memory implementation** — used in unit and integration tests. Owned by us, kept in
   sync by the contract test above. Rule: **there is one fake per port, not ad-hoc mocks per test.**
6. **Runbook** — what happens when the vendor is down, degraded, or rate-limiting; who is called;
   what the user sees. In [`docs/operations/`](../operations/).
7. **Exit plan** — what it would take to replace this vendor: what data of ours they hold, how it is
   extracted, what identifiers would need remapping, and roughly how long a migration takes. Written
   **when the adapter is built**, while the answers are known, not during the renegotiation where it
   is needed. A port with no exit plan is a port whose abstraction has not been tested against the
   scenario it exists for.

## 5. Verification checklists

To be completed by a human, against current vendor documentation, before the corresponding adapter
is built. Answers are recorded in an ADR per vendor.

### P1 PaymentGateway (blocks Phase 3)
- [ ] Markets, currencies, and local payment methods actually supported in the launch territories
- [ ] Recurring billing: does the provider hold the subscription schedule, or do we? (Design assumes **we do**)
- [ ] Strong customer authentication / 3-D Secure handling for recurring payments
- [ ] Hosted payment fields available so no card data touches our infrastructure (PCI DSS SAQ A)
- [ ] Webhook delivery guarantees, signing, replay window, and idempotency semantics
- [ ] Refund, chargeback and dispute APIs
- [ ] Sandbox parity with production
- [ ] Whether app-store billing is mandatory for iOS/Android sign-ups in our model — this is a
      commercial and compliance question with major revenue impact (**OQ-10**)

### P2 DrmLicenceProvider (blocks Phase 6 — start early, long lead time)
- [ ] Commercial agreements required for **Widevine (Google)**, **PlayReady (Microsoft)**, and
      **FairPlay Streaming (Apple)**. These are separate agreements with separate timelines and are
      the single longest-lead item in the programme
- [ ] Whether a multi-DRM vendor is used or each is integrated directly
- [ ] Supported encryption schemes and whether one CMAF encode can serve all three (design intent:
      yes, via `cbcs`; see [`docs/streaming/drm.md`](../streaming/drm.md)) `[UNVERIFIED]`
- [ ] Key delivery/provisioning model and key rotation support
- [ ] Licence policy controls available: security level, HDCP, output protection, max resolution,
      persistent/offline licences, licence duration, renewal
- [ ] Latency and availability SLA for licence issuance, and geographic points of presence
- [ ] Test/staging licence server and test devices

### P3 CdnProvider (blocks Phase 4)

> **Status.** No adapter exists, and none may be built until this list is complete. What *does*
> exist is the port and an origin-direct provider — which is not a placeholder for a CDN but a real
> delivery mode, and the one the platform runs on until a vendor is chosen (OQ-8).
>
> The port is deliberately minimal: `name()` and `urlFor()`. Purge, log delivery, geo controls and
> shield configuration all belong here eventually, but their *shapes* differ between vendors, and
> declaring methods now would mean inventing an interface from an imagined vendor. An adapter may
> not be built before its checklist is complete, and neither may the interface it would implement.
>
> Two checklist items below are now answerable from our side rather than the vendor's, because the
> platform has been built to make them straightforward to satisfy:
>
> - **Per-session tokens** — everything that varies per viewer is packed into one opaque query
>   parameter, so a vendor needs to support excluding exactly one parameter from the cache key
>   while still validating it. Confirmed working against our own origin.
> - **Geo controls** — the design already treats edge geo-blocking as defence in depth only;
>   territory is decided and recorded at authorization, so a vendor that cannot do it is not
>   disqualified.

- [ ] Token authentication or signed URL scheme, and whether it supports per-session tokens
- [ ] Origin shield / mid-tier caching
- [ ] Purge API: granularity and propagation time
- [ ] Real-time and raw log delivery for QoE and licensor reporting
- [ ] Geo-blocking capability at the edge, and whether it can be trusted as the only geo control
      (design assumes **not** — geo is also enforced at authorization)
- [ ] Live/low-latency support, if LL-HLS/LL-DASH is in scope
- [ ] Commercial model: committed volume, per-region pricing, egress from origin

### P6 ScheduleProvider (blocks Phase 2)
- [ ] Coverage of the actual channel lineup, and licence to redistribute the data to end users
- [ ] Delivery format, update frequency, revision/correction semantics
- [ ] Channel identifier mapping to our channels; stability of those identifiers
- [ ] Series/episode identifiers that can be joined to Catalog
- [ ] How far ahead and how far back data is available (drives catch-up UX)

### P7 MetadataProvider (blocks Phase 2)
- [ ] **Redistribution rights for artwork and synopses** — commonly restricted; a frequent and
      expensive late discovery
- [ ] Attribution obligations
- [ ] Rate limits and caching permissions

### P13 SecretStore / KeyVault (blocks Phase 1 for secrets)
- [ ] HSM or KMS backing for content key wrapping
- [ ] Access control granularity — can it express *"the licence proxy may resolve a key by id; the
      packager may only receive keys pushed per job and may not query"*? If it cannot express that
      distinction, the key-access model in
      [`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md) §3
      is not enforceable and must be redesigned around what the vault can actually do
- [ ] Audit logging of key access
- [ ] Rotation and re-wrapping procedure
- [ ] **Backup and restore of key material**, and escrow of unseal material under split control —
      losing the vault makes the library permanently unplayable, so this is a hard requirement, not a
      preference
- [ ] Availability characteristics: the licence path depends on it at request time

## 6. Anti-corruption layer patterns

- **Inbound feeds** (schedule, metadata, mezzanine delivery) land in a **staging area first**, are
  validated and normalised, and only then are promoted into the domain. A provider is never allowed
  to write directly into Catalog or Schedule tables. This preserves the ability to reprocess a feed
  after finding a mapping bug — which will happen.
- **Outbound calls** are wrapped with timeout, retry (idempotent only), circuit breaker, and
  bulkhead so one slow vendor cannot exhaust the worker pool.
- **Webhooks** are authenticated by signature, stored raw and immutable on receipt, acknowledged
  immediately, and processed asynchronously with idempotency keys. Never process a webhook inline:
  provider retry storms during an incident will amplify the outage.
- **Vendor identifiers** are stored in a dedicated mapping table per integration, never in the
  aggregate's own columns, so a vendor swap does not require a domain migration.

## 7. Multi-vendor readiness

Two ports are designed for **more than one live adapter simultaneously**, because single-vendor
dependency there is a business risk rather than an inconvenience:

- **`CdnProvider`** — multi-CDN is how large-scale delivery survives a regional CDN failure.
  Selection/steering logic is deferred (Phase 10), but the abstraction must exist from Phase 4 or
  retrofitting it means touching every playback response.
- **`PaymentGateway`** — a second PSP is normal when entering a new market, and PSP outages during
  a billing run are a revenue event.

The others are single-adapter until a real second need appears. Building a multi-vendor abstraction
without a second vendor produces the wrong abstraction.
