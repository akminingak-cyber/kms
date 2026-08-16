# PRODUCT_SPEC.md — KMS TV Product Specification

**Phase:** 1 — Product specification
**Status:** DRAFT — awaiting product approval · **PD-004, PD-008, PD-092, PD-095 APPROVED**
**Version:** 1.4
**Date:** 2026-08-13 (rev. 1.4 — PD-095 approved and propagated)
**Governing document:** `CLAUDE.md` (binding)
**Companion documents:** `REQUIREMENTS.md`, `USER_FLOWS.md`, `FEATURE_MATRIX.md`, `DECISIONS.md`

---

## 0. How to read this document

This specification defines **what KMS TV must become**. It defines no implementation, no
schema, no API, and no code. Those belong to Phases 3–7 and later.

### 0.1 Status vocabulary — used on every non-obvious statement

| Marker | Meaning |
|---|---|
| **[CONFIRMED]** | Stated in the STEP 1 product brief, or already an accepted decision in `PROJECT_STATE.md`. Binding. |
| **[PROPOSED]** | A recommended default, chosen for a stated reason. **Not approved.** May be changed at no cost until Phase 3. |
| **[OPEN]** | A decision that must be made by the product owner. **No default has been chosen**, because choosing silently would violate `CLAUDE.md` §1. |
| **[LEGAL]** | Requires verification by qualified legal or professional advisors. Nothing here is legal advice. |
| **[UNVERIFIED]** | A fact that could not be confirmed from a primary source and must not be relied upon. |

Every **[OPEN]** and **[LEGAL]** item carries a `PD-nnn` identifier and is listed in
`DECISIONS.md`. **Nothing marked [OPEN] has been silently decided.**

### 0.2 What this document deliberately does not do

It does not invent legal facts, commercial terms, market data, vendor capabilities,
pricing, or performance guarantees. Where a number is required but not approved, it is
marked **PROPOSED — REQUIRES VALIDATION** and carries a `PD-nnn` reference.

---

## 1. Product identity

| Attribute | Value | Status |
|---|---|---|
| Product name | **KMS TV** | [CONFIRMED] |
| Product type | Production-grade IPTV / OTT platform | [CONFIRMED] |
| Content modes | Live TV **and** on-demand | [CONFIRMED] |
| Target platforms | Web · Android · Android TV · iOS/iPadOS · Samsung Tizen · LG webOS | [CONFIRMED] |
| **Launch platforms (v1.0)** | **Web · Android · Android TV** | [CONFIRMED — PD-092 APPROVED] |
| **Subsequent platforms (v1.x)** | **iOS/iPadOS · Samsung Tizen · LG webOS** | [CONFIRMED — PD-092 APPROVED] |
| Commercial intent | A real commercial software product, not a demonstration or a player app | [CONFIRMED] |
| Operating premise | A **licensed operator** distributing **licensed content** to **entitled subscribers** | [CONFIRMED] — `CLAUDE.md` §2 |
| **Operator model** | **One operator. Single-tenant.** Multi-tenancy, white-label, and SaaS operator platform are **OUT OF SCOPE** | [CONFIRMED — PD-008 APPROVED] |

**KMS TV is not** a media player, a playlist aggregator, a stream index, or a tool for
accessing third-party services. It is the software an operator runs to distribute
content it holds rights to. This distinction determines the entire architecture:
entitlement and rights enforcement are the product, and the player is a consequence.

**KMS TV is also not a SaaS platform for operators.** Per **PD-008 (APPROVED)** it serves
**one operator**. See §1.2.

### 1.2 Operator model — single-tenant [CONFIRMED — PD-008 APPROVED · FINAL]

KMS TV is **one operator and one product**:

```
ONE OPERATOR
ONE KMS TV SERVICE
ONE CENTRAL ADMIN CONTROL PLANE
ONE CONTENT/RIGHTS DOMAIN
ONE SUBSCRIPTION SYSTEM
ONE PAYMENT DOMAIN
ONE ANALYTICS DOMAIN
```

**OUT OF SCOPE for the current product:** multiple operator tenants · white-label
operators · tenant-specific deployments managed by a shared control plane · tenant
isolation · tenant-specific billing · tenant-specific admin organizations ·
tenant-specific content catalogs · tenant-specific rights domains.

**No tenant concept may appear anywhere in the product.** No `tenant_id` introduced for
hypothetical future use, no artificial tenant abstractions, and no tenant-aware
authorization, billing, content management, caching, storage paths, or analytics. The
architecture remains clean and single-tenant.

**Future extensibility.** The product must not be *deliberately* made impossible to evolve
toward multi-tenancy in the distant future — but **future multi-tenancy must not influence
the current data model unless a concrete requirement requires it**, and **no speculative
infrastructure is to be built**. Should a multi-tenant requirement ever appear, it is a
**new architectural decision requiring its own ADR**, not a resumption of PD-008.

#### TENANCY ≠ TERRITORY

The two decisions are independent and both are approved. They must never be conflated.

| | **Tenancy — PD-008** | **Territory — PD-004** |
|---|---|---|
| Decision | **Single-tenant — one operator** | **Multi-territory — many territories possible** |
| Partitions | *Who runs the platform* | *Where it serves, and what may be served there* |
| Status | Multi-tenancy **OUT OF SCOPE** | Multi-territory **REQUIRED from day one** |
| In the model | No tenant concept, no `tenant_id` | Territory is first-class and configurable (§2.3.1, FR-TER-01) |

**One operator serving several territories is exactly the approved model, and it requires
no tenancy concept at all.** Georgia today, further territories later — one operator, one
catalogue, one rights domain, one admin control plane throughout.

### 1.3 Launch platform scope [CONFIRMED — PD-092 APPROVED · FINAL]

```
v1.0    Web + Android + Android TV
v1.x    iOS / iPadOS
v1.x    Samsung Tizen
v1.x    LG webOS
```

Exact version numbers are **not fixed**.

**Launch principle.** The first production release **prioritizes quality and stability over
maximum platform count**. KMS TV **MUST NOT** attempt to launch all six clients
simultaneously. The three launch clients **MUST** be production-quality and **MUST** pass
the project's full acceptance, security, compatibility, and regression gates.

**Quality principle.** *Three production-quality clients are preferable to six incomplete
clients.* **No platform may be declared production-ready until it passes all ten of:**

| # | Gate | # | Gate |
|---|---|---|---|
| 1 | Functional acceptance tests | 6 | Network failure tests |
| 2 | Playback tests | 7 | Regression tests |
| 3 | Authentication tests | 8 | Performance checks |
| 4 | Authorization tests | 9 | Security checks |
| 5 | Device/session tests | 10 | Platform-specific compatibility testing |

**Why Android TV is a launch platform.** KMS TV is fundamentally a **television/OTT
product**. A launch without a living-room client would omit the primary use case (UC-01).
The Android TV launch client must support the approved product requirements for remote
navigation, focus management, Live TV, EPG, playback, profiles, search, VOD where included
in the applicable release scope, authentication, error handling, and session/device
management.

#### 1.3.1 Platform-neutral API [CONFIRMED — PD-092 APPROVED]

The backend API **MUST remain platform-neutral**.

- **Prohibited:** platform-specific *business* APIs such as `/api/android/`,
  `/api/android-tv/`, `/api/samsung/`, `/api/lg/`.
- **Required:** a **shared versioned API** (`/api/v1/`), consumed by every client.
- **All clients consume the same authoritative business logic.**
- Platform-specific behaviour **may** exist at the **client/player layer** where genuinely
  required — never in the business API.

This is a product constraint, not an API design. The specification of the API itself is
Phase 5.

#### 1.3.2 Shared, server-authoritative business logic [CONFIRMED — PD-092 APPROVED]

The following **remain server-authoritative and platform-independent**, and **MUST NOT be
duplicated independently inside any client**:

| | | | |
|---|---|---|---|
| authentication | authorization | users | profiles |
| devices | sessions | channels | EPG |
| packages | subscriptions | entitlements | rights |
| playback authorization | payments | account state | |

This extends `CLAUDE.md` §4.2 — *"Client applications MUST NOT contain business rules that
determine entitlement"* — from entitlement alone to the full list above. A client renders
what the backend decides, for all fifteen.

#### 1.3.3 Future platform preparation

The architecture **MUST** be designed from day one so that iOS/iPadOS, Samsung Tizen, and
LG webOS can each be **added later without redesigning the core business architecture**.
Their requirements **MUST** be explicitly considered during architecture and API design
(Phases 3 and 5).

**But those clients MUST NOT be implemented during the initial launch phase, and no
placeholder application may be created merely to claim platform support.** Considering a
platform's requirements is design work; shipping an empty shell is not support.

### 1.1 Product identity conflict inherited from the repository

The repository contains a pre-existing scaffold whose `<title>` reads *"KMS – Enterprise
Technology Infrastructure & Security"* — a different product. **[OPEN — PD-001]**
Confirm that KMS TV supersedes that identity and decide the scaffold's fate. Recorded in
`PROJECT_STATE.md` as D-006 and in `docs/legal/DECISION_LOG.md` as L-007. Not resolved
in this phase because it requires the product owner's decision, not an engineering one.

---

## 2. Product goal

### 2.1 Primary product objective

**Enable a licensed television operator to deliver a premium, reliable, multi-platform TV
service — live and on-demand — while provably respecting the rights under which each
piece of content is licensed.** [CONFIRMED, derived]

Two properties are load-bearing and everything else is subordinate to them:

1. **The viewer experience must feel like television**, not like a website that plays
   video. Channel changes are fast, the guide is accurate, playback starts quickly, and
   the remote control works the way a remote control works.
2. **Every playback must be provably authorized.** For any stream ever served, the
   operator must be able to demonstrate to a rights holder who watched, where, on what
   device class, under which contract, and within which window.

Property 2 is what makes property 1 commercially survivable. An operator that cannot
prove compliance loses content, and an operator with no content has no product.

### 2.2 Target users

| Segment | Description | Status |
|---|---|---|
| Household television viewers | The primary audience. Watch live channels on a TV, occasionally on mobile. Value channel availability, guide accuracy, and reliability far above feature count. | [PROPOSED] |
| Mobile / on-the-go viewers | Watch on phone or tablet, typically shorter sessions, often on cellular networks. Value fast startup and low data use. | [PROPOSED] |
| On-demand viewers | Primarily consume VOD, movies, and series. Value catalogue depth, search, resume, and recommendations. | [PROPOSED] |
| Diaspora viewers | Viewers outside Georgia wanting home-market channels. **Subject to two independent gates** (§2.3.1): whether the service is made available in their territory, and whether content rights cover it there. This segment may be unserviceable for some or all content. | [PROPOSED] + [LEGAL — PD-002] |
| Operator staff | Content managers, support, finance, operations, analysts. Internal users of the Admin Control Center. | [CONFIRMED] |

**[OPEN — PD-003]** The target user segments above are inferences drawn from the product
type and the confirmed language set. They have not been confirmed by the product owner
and no market research has been supplied. They must be confirmed or replaced.

### 2.3 Target markets

The brief confirms four product languages: **Georgian (primary), English, Russian,
Spanish**. [CONFIRMED]

**PD-004 is APPROVED and FINAL** (2026-08-13):

| Field | Value | Status |
|---|---|---|
| **Launch territory** | **Georgia** | [CONFIRMED — PD-004] |
| **Architecture** | **Multi-territory from day one** | [CONFIRMED — PD-004] |
| **Future territories** | **Configurable, without redesigning the core platform** | [CONFIRMED — PD-004] |

Binding consequences of the approval:

- Georgia is the initial launch territory. **Georgia MUST NOT be hard-coded as the only
  possible territory** anywhere in the platform.
- **Service availability MUST be configurable per territory.**
- **Content availability MUST be controlled independently per territory**, and content
  rights MUST always be territory-aware.
- Packages, pricing, payment methods, tax configuration, and localization **MAY differ by
  territory** — so each must be modelled as territory-scoped, not global.
- **A future territory MUST be addable without redesigning the core platform.**
- **Installing the application in another country MUST NOT grant access to content.**
- Playback authorization MUST evaluate the applicable territory, service-availability, and
  content-rights rules (§12).

**No territory beyond Georgia is named, planned, or assumed.** The approval mentions the
United States solely as an illustration that a future territory must be addable. Inferring
a launch plan from an illustration would be a fabrication, and none is inferred here
(`CLAUDE.md` §1).

**[UNVERIFIED]** No claim is made in this document about the regulatory, broadcasting,
tax, or data-protection regime of Georgia or of any other country. The territory is now
determined; its legal consequences are not. **PD-081** (applicable privacy regimes),
**PD-035** (classification scheme), **PD-054** (tax treatment), and **PD-075** (payment
methods) each now have a determinate input and remain **[LEGAL]** and open.

**[OPEN — PD-077]** The rationale for the Spanish language requirement remains
unexplained. All four languages remain required; no market is inferred from the set.

---

### 2.3.1 App distribution, service availability, and content rights — three separate concepts

**Binding, from PD-004. These three MUST NOT be merged, collapsed, or inferred from one
another** — in the data model, in the API, in the clients, or in operator tooling.

| # | Concept | Governs | Answers | Controlled by |
|---|---|---|---|---|
| 1 | **App distribution** | Where the client application may be obtained and installed | *Can this person get the app?* | Store listing territories and web accessibility |
| 2 | **Service availability** | Where KMS TV operates commercially — registration, subscription, billing, support | *Can this person become and remain a customer?* | Operator commercial and legal readiness per territory |
| 3 | **Content rights** | Per-asset territorial distribution grants | *May this specific asset be served to this person, here, now, in this mode?* | Rights agreements (§15) |

**They are three independent gates, evaluated in order, and passing one implies nothing
about the next:**

```
install the app            →  app distribution covers this territory
   ↓  (grants nothing)
register / subscribe       →  service availability covers this territory
   ↓  (grants no specific content)
play a given asset         →  content rights cover this asset,
                              this territory, this device class, this mode
```

Worked examples, taken directly from the approval:

- **A person may install the application in a territory where the service is not
  commercially available.** They can open it. They cannot register, subscribe, or play.
  The application must present a clear, non-error "not available in your location yet"
  state — this is an expected condition, not a failure (§31).
- **The service may be available in a territory while a particular channel is not**,
  because the applicable content rights do not cover that territory. The channel is absent
  from that viewer's catalogue, and a direct request is denied neutrally.

**Why the separation is load-bearing.** Merging app distribution into service availability
produces a platform that cannot be listed anywhere it does not yet trade — which blocks
soft launches, diaspora marketing, and app-store presence ahead of commercial readiness.
Merging service availability into content rights produces a platform that assumes a
served territory means a licensed catalogue, which is precisely the assumption that causes
unlicensed distribution. Each merge is a distinct failure, and the second is a compliance
failure.

**Consequential open decisions.** The separation raises two questions the approval does not
answer, and neither has been defaulted:

- **[OPEN — PD-094]** In which territories is the application listed and installable?
- ~~[OPEN — PD-095]~~ **RESOLVED — PD-095 APPROVED.** See §2.3.2.

---

### 2.3.2 Travelling subscribers [CONFIRMED — PD-095 APPROVED · FINAL]

**Policy: the subscription follows the subscriber.** An active subscription remains
associated with the subscriber while travelling. It does **NOT** automatically grant access
to every KMS TV service or every piece of content in every territory.

> **SUBSCRIPTION OWNERSHIP is separate from CONTENT TERRITORY RIGHTS.**

Access while travelling remains subject to all six of:

| # | Condition |
|---|---|
| 1 | **Current territory** |
| 2 | **KMS TV service availability** in that territory (§2.3.1 concept 2) |
| 3 | **Applicable content rights** in that territory (§2.3.1 concept 3, §15) |
| 4 | **Package entitlement** |
| 5 | **Playback authorization** (§12) |
| 6 | **Any applicable platform/device policy** |

**Worked example, as approved:**

| | |
|---|---|
| Home territory | Georgia |
| Current territory | another territory |
| Subscription | **ACTIVE** — it follows the subscriber |
| Requested content **has** applicable rights there | → playback **may be allowed** |
| Requested content **has no** applicable rights there | → playback **must be denied** |

**An active subscription is never a universal content license.** Content available in the
subscriber's home territory is **not** automatically available while travelling — this
follows directly from §2.3.1, where content rights are the third and independent gate.

**Service availability while travelling.** If KMS TV is not available in the current
territory, access is denied per the service-availability policy — `SERVICE_NOT_AVAILABLE`,
distinct from a rights denial. The exact user-facing message and recovery flow **may be
defined later**.

**No roaming limits are defined.** The following are **explicitly NOT part of this
specification** and **must not be assumed** unless separately approved: maximum roaming
days · maximum travel duration · country lists · percentage-of-time rules · mandatory
re-authentication intervals · VPN rules · IP thresholds · travel-specific device
restrictions. Later decisions and applicable legal and business requirements may define
them.

**Privacy.** Territory evaluation follows the platform's privacy and data-minimization
requirements (§33, NFR-PRV-01). **Unnecessary location data must not be collected.**
**[UNVERIFIED]** No geolocation technology, method, accuracy, or provider is defined or
assumed anywhere; determining the current territory is an implementation decision for a
later phase.

**Which territory the authorization checks use.** The territory evaluated in §12.1 checks
6, 9, and 11 is the **current territory, determined server-side at authorization time** —
never the home territory, and never a client-supplied value (§12.2). PD-095 adds **no new
authorization check**; it settles which territory the existing ones read.

### 2.4 Primary use cases

| # | Use case | Priority |
|---|---|---|
| UC-01 | Turn on a device and watch a live channel within seconds | P0 |
| UC-02 | Browse the electronic programme guide and see what is on now and next | P0 |
| UC-03 | Change channels quickly, including returning to the previous channel | P0 |
| UC-04 | Find and watch a movie or series episode on demand | P0 |
| UC-05 | Resume watching something started on another device | P0 |
| UC-06 | Restart a live programme already in progress from its beginning | P1 |
| UC-07 | Watch a programme that aired earlier, within the catch-up window | P1 |
| UC-08 | Search across channels, programmes, and on-demand content | P1 |
| UC-09 | Manage household profiles, including a child-appropriate profile | P1 |
| UC-10 | Subscribe, change package, or cancel | P0 |
| UC-11 | See and remove registered devices and active sessions | P0 |
| UC-12 | Operator: publish a channel with a verified rights basis | P0 |
| UC-13 | Operator: see what rights expire in the next 24 hours and act | P0 |
| UC-14 | Operator: investigate why a specific viewer's playback was denied | P0 |

### 2.5 Business model possibilities

All business-model options below are **[PROPOSED]** — presented for decision, not chosen.
`CLAUDE.md` §1 prohibits inventing commercial facts, and no pricing, market, or revenue
information has been supplied.

| Model | Description | Product implications | Status |
|---|---|---|---|
| **Paid subscription (SVOD/live)** | Recurring fee for package access | Requires packages, subscriptions, payments, entitlement engine. This is the model the rest of this spec assumes as the baseline, because every other model is a variation on it. | [PROPOSED — baseline] |
| **Free tier** | A limited set of channels/content at no cost | Requires an entitlement path for unpaid accounts and a rights basis permitting free distribution — which is a **separate grant** from paid distribution | [OPEN — PD-005] + [LEGAL] |
| **Advertising-supported (AVOD/FAST)** | Ad-funded channels or content | Requires ad insertion, an ad provider, measurement, and consent handling. **No ad provider capability may be assumed** (`CLAUDE.md` §1). | [OPEN — PD-006] + [LEGAL] |
| **Hybrid** | Paid tiers plus an ad-supported free tier | Combination of the above | [OPEN — PD-005/006] |
| **Transactional (TVOD/PPV)** | Pay per title or per event | **NOT AT LAUNCH.** KMS TV will not sell individual titles or events separately at launch (§13.6). **Launch scope only — not a permanent prohibition**; future PPV remains possible as a separately approved future commercial capability | **[NOT AT LAUNCH — PD-007 APPROVED]** |
| ~~**Operator/B2B wholesale**~~ | ~~KMS TV licensed to another operator~~ | **OUT OF SCOPE.** Would require multi-tenancy, which **PD-008 (APPROVED)** excludes from the current product | **[REJECTED — PD-008 APPROVED]** |

**PD-008 is APPROVED: Option A — single-tenant, one operator.** Multi-tenancy, white-label,
and SaaS operator platform are **OUT OF SCOPE** (§1.2). The B2B wholesale model above is
therefore rejected for the current product. Should it ever be revisited, it is a new
architectural decision with its own ADR, not a reopening of PD-008.

### 2.6 Free tier possibilities

If a free tier is approved (PD-005), it must satisfy all of the following, and each is a
hard product requirement, not a preference:

- Free access is an **entitlement outcome**, evaluated by the same engine as paid access.
  There is no separate "free path" that bypasses authorization (`CLAUDE.md` §8).
- Content offered free must carry a **rights basis permitting free distribution**.
  Holding paid-distribution rights does not imply free-distribution rights. [LEGAL]
- Free accounts are still authenticated accounts with devices, sessions, and concurrency
  limits. Anonymous playback is **[OPEN — PD-009]** and is not assumed.
- Free tier limits (channel count, concurrency, quality ceiling) are **[OPEN — PD-005]**.

### 2.7 Subscription possibilities

Subscription is the baseline model. Structural options, all **[PROPOSED]**:

- Tiered packages (e.g. entry / standard / premium) with increasing content access
- Add-on packages layered onto a base (e.g. a sports or cinema add-on) — **structurally
  permitted by PD-049 Q1 (APPROVED, §13.4); whether any is actually sold at launch is
  [OPEN — PD-049 Q2]**
- Billing periods: monthly and/or annual **[OPEN — PD-010]**
- Free trial period **[OPEN — PD-011]** — if offered, it is a distinct subscription state
- Promotional pricing and discounts **[OPEN — PD-012]**

Exact package names, contents, prices, and currencies are **[OPEN — PD-013, PD-014]**.
The names *Free, Basic, Standard, Premium, Sports, Movies* appear in the brief explicitly
as examples that are **not approved**, and this document treats them as illustrative only.

### 2.8 Advertising possibilities

**Not implemented in any phase of the current plan.** If approved later (PD-006), the
product requirements would be: server-side or client-side ad insertion decision, an ad
provider abstraction validated against two candidates (`CLAUDE.md` §4.2), ad-break
signalling in the packaging pipeline, consent capture, and frequency capping. Advertising
also changes privacy obligations materially. [LEGAL]

**No advertising provider, capability, ad format, or measurement standard is assumed or
asserted anywhere in this specification.**

### 2.9 Future expansion possibilities

Recorded as direction, none approved:

| Expansion | Note | Status |
|---|---|---|
| Network DVR / cloud recording | Large storage and rights implications; recording rights are a separate grant | [OPEN — PD-015] + [LEGAL] |
| Offline download | Requires download rights, DRM offline licences, and device storage policy | [OPEN — PD-016] + [LEGAL] |
| 4K / HDR delivery | Higher bitrate ladders, device capability gating, higher CDN cost | [OPEN — PD-017] |
| Additional platforms (Roku, Fire TV, Apple TV, Vidaa, set-top boxes) | Each is a full client project with its own store and certification | [OPEN — PD-018] |
| ~~Multi-tenant / white-label~~ | **OUT OF SCOPE** per PD-008 (APPROVED). Not deferred — excluded. Any future revisit is a **new architectural decision with its own ADR** | **[REJECTED — PD-008 APPROVED]** |
| Personalized recommendations using ML | Phase 22 of the brief explicitly defers ML; deterministic fallback is the P0 requirement | [PROPOSED — later] |
| Social / watch-party features | Out of scope for launch | [PROPOSED — later] |

---

## 3. User types

Two distinct authorization systems exist and **must never be conflated** (`CLAUDE.md` §8):

- **Entitlements** — what a viewer may watch. Derived from subscription, package, rights,
  territory, and device class.
- **Roles** — what an operator staff member may do in the Admin Control Center.

A subscriber never has a role. A staff member's role never grants viewing entitlement.

### 3.1 Viewer-side user types

#### 3.1.1 Anonymous visitor
- **Purpose.** Evaluate the service before creating an account.
- **Capabilities.** View marketing/landing surfaces; view a public channel line-up **if
  approved** (PD-019); register; log in; initiate password recovery.
- **Restrictions.** **No playback** unless anonymous free playback is explicitly approved
  (PD-009). No profile, no favorites, no history, no device registration.
- **Security requirements.** Rate limiting on registration, login, and password-reset
  endpoints. No account enumeration through response content or timing. No personal data
  collected beyond what registration requires.

#### 3.1.2 Registered user
- **Purpose.** An authenticated identity that owns an account but may hold no entitlement.
- **Capabilities.** Manage account and profiles; register devices; manage sessions;
  subscribe; view catalogue metadata; manage notification preferences; export or delete
  their data.
- **Restrictions.** **No playback without an entitlement.** Browsing the catalogue is not
  access to it.
- **Security requirements.** Verified email (or approved alternative identifier, PD-020)
  before privileged account operations; session bound to a device; all sessions revocable.

#### 3.1.3 Free user
- **Purpose.** A registered user entitled to free-tier content only, **if a free tier is
  approved** (PD-005).
- **Capabilities.** Everything a registered user has, plus playback of content whose
  rights permit free distribution.
- **Restrictions.** Reduced concurrency and device allowances **[OPEN — PD-005]**;
  possible quality ceiling; no premium content.
- **Security requirements.** Identical to a paying subscriber. A free account is a full
  account, not a reduced-security one.

#### 3.1.4 Subscriber
- **Purpose.** A registered user with an active, paid subscription.
- **Capabilities.** Playback of all content their package and the applicable rights allow,
  within device and concurrency limits.
- **Restrictions.** Bounded by package contents, rights windows, territory, device class,
  device count, and concurrent streams.
- **Security requirements.** Entitlement re-evaluated at **every** playback authorization,
  never cached past an input change (`CLAUDE.md` §19).

#### 3.1.5 Premium subscriber
- **Purpose.** A subscriber on a higher tier. **Not a separate identity type** — the same
  Subscriber entity with a different package.
- **Capabilities.** Superset of the base tier as defined by the package.
- **Restrictions.** Same enforcement mechanisms; different allowances.
- **Security requirements.** Identical. Tier must never alter which checks run — only
  their outcome.

#### 3.1.6 Household / profile user
- **Purpose.** A person within a subscribing household, represented by a profile.
- **Capabilities.** Own watch history, favorites, resume points, language preference,
  recommendations, and (where configured) age-restricted content limits.
- **Restrictions.** Cannot manage billing, package, devices, or other profiles unless
  they are on the account owner's profile. A child profile is further restricted by
  maturity rating and by parental controls.
- **Security requirements.** Profile switching is **not** authentication. Profiles share
  the account's authentication context, so a profile switch must never elevate privilege.
  Where a profile is PIN-protected, the PIN is verified server-side (PD-021).
  **Watch history must not leak across profiles** without explicit design (`CLAUDE.md` §18).

### 3.2 Operator-side user types

All operator roles are administrative capabilities within the Admin Control Center. All
require MFA before any production deployment (`CLAUDE.md` §7). All actions are audited
with actor, timestamp, and before/after state.

#### 3.2.1 Support operator
- **Purpose.** Resolve subscriber problems.
- **Capabilities.** Look up an account; view subscription status and history; view
  devices and sessions; terminate a session; view **playback denial reasons**; view a
  redacted account timeline; create support notes.
- **Restrictions.** **Must not** see credentials, payment card data, or full personal
  records beyond what is necessary. Must not change packages, prices, entitlements, or
  rights. Must not perform bulk export.
- **Security requirements.** Every lookup audited (support tooling is a common exfiltration
  path). Time-boxed sessions. No impersonation capability without explicit approval
  **[OPEN — PD-022]**; if approved, impersonation must be visibly flagged, consented to
  where required [LEGAL], and fully audited.

#### 3.2.2 Content manager
- **Purpose.** Manage the catalogue: channels, EPG, VOD, categories, artwork, scheduling.
- **Capabilities.** Create and edit channels, VOD titles, series structure, categories,
  metadata, artwork; trigger and review EPG ingest; publish and unpublish.
- **Restrictions.** **Cannot create a distributable asset without a rights reference.**
  Cannot alter rights agreements themselves, alter pricing, or view subscriber personal
  data.
- **Security requirements.** Publishing is a privileged, audited action. Bulk operations
  require preview and confirmation.

#### 3.2.3 Finance operator
- **Purpose.** Manage commercial and billing operations.
- **Capabilities.** View subscriptions and billing state; view invoices and receipts;
  process refunds where permitted; reconcile against the payment provider; view financial
  reports.
- **Restrictions.** **Must never see card data** — it never enters KMS TV systems
  (`CLAUDE.md` §6.2). Cannot alter content, rights, or entitlements directly.
- **Security requirements.** Refunds are permission-gated and audited. Financial exports
  are audited and access-controlled.

#### 3.2.4 Operations
- **Purpose.** Keep the platform running.
- **Capabilities.** View stream health, ingest status, transcode queues, origin and CDN
  status, playback session diagnostics, alerts and runbooks; restart jobs; drain and
  failover.
- **Restrictions.** Cannot alter entitlements, rights, or commercial data. Cannot view
  subscriber personal data beyond diagnostic necessity.
- **Security requirements.** Infrastructure actions audited. No route through operations
  tooling may serve content without authorization (`CLAUDE.md` §2.2 — "internal only" is
  not an exemption).

#### 3.2.5 Analyst
- **Purpose.** Understand product and content performance.
- **Capabilities.** Read-only access to analytics dashboards and aggregate reports.
- **Restrictions.** **Pseudonymous data by default.** No access to identifiable personal
  records, no raw export of viewing history joined to identity without an approved,
  recorded purpose (`CLAUDE.md` §18).
- **Security requirements.** Any identity join requires a recorded decision; exports are
  audited.

#### 3.2.6 Administrator
- **Purpose.** Day-to-day platform administration across domains.
- **Capabilities.** Union of content, support, and operations capabilities, plus user and
  role administration below their own level.
- **Restrictions.** **Cannot grant themselves privileges**, cannot alter audit logs,
  cannot disable security controls.
- **Security requirements.** MFA mandatory. All actions audited. Privilege-escalation
  paths explicitly tested (`CLAUDE.md` §8).

#### 3.2.7 Super administrator
- **Purpose.** Ultimate platform authority, including role and permission definition.
- **Capabilities.** All administrative capabilities; define roles and permissions; manage
  system configuration; manage integrations.
- **Restrictions.** **Cannot bypass authentication, cannot bypass entitlement, cannot
  delete or alter audit records.** There is no super-user viewing bypass: a super admin
  who wants to watch a channel needs an entitlement like anyone else.
- **Security requirements.** MFA mandatory. Minimum number of holders **[OPEN — PD-023]**.
  Every action audited. Creation of a super admin is itself an audited, restricted event.

---

## 4. Account system

### 4.1 Registration
- Identifier: email **[PROPOSED]**; phone-number registration is **[OPEN — PD-020]**
  (relevant if the target market favours phone identity).
- Required data: identifier, password, acceptance of terms and privacy notice [LEGAL].
- **Data minimization** — no field is collected without a stated purpose (`CLAUDE.md` §18).
  Name, date of birth, and address are **not** collected at registration unless a
  specific requirement demands it **[OPEN — PD-024]**.
- Password policy **[PROPOSED]**: minimum length with a breached-password check rather
  than composition rules; final policy **[OPEN — PD-025]**.
- Verification required before privileged operations (see 4.4).
- **Anti-enumeration**: registration must not reveal whether an identifier already exists.

### 4.2 Login
- Identifier + password. Optional MFA if enabled (4.14).
- Issues a session bound to a device (§6).
- **Anti-enumeration**: identical response shape and timing for unknown identifier and
  wrong password.
- Rate limiting and progressive lockout on repeated failures.
- Login on a new device triggers a security notification (§23).

### 4.3 Logout
- Ends the current session and invalidates its tokens server-side.
- "Log out everywhere" ends all sessions across all devices.
- Playback in progress on the ended session must stop within the documented revocation
  interval **[PROPOSED — REQUIRES VALIDATION: ≤ 60 seconds, PD-026]** (`CLAUDE.md` §7 —
  "eventually" is not an interval).

### 4.4 Email verification
- A time-limited, single-use verification link.
- Unverified accounts may browse, but **[PROPOSED]** may not subscribe, add devices
  beyond the first, or change the account identifier.
- Resend is rate-limited.

### 4.5 Password reset
- Initiated by identifier; **always returns the same response** whether or not the account
  exists.
- Time-limited, single-use token delivered out of band.
- On successful reset: **all sessions are invalidated** and a security notification is
  sent.

### 4.6 Account recovery
- Recovery beyond password reset (lost access to the registered identifier) is a
  **manual, audited support process**, never a self-service bypass.
- **[OPEN — PD-027]** Define the identity-verification standard support must apply.
  This is the most commonly abused path into an account and must not be improvised. [LEGAL]

### 4.7 Account status

| Status | Meaning | Playback | Login |
|---|---|---|---|
| `pending_verification` | Registered, identifier not yet verified | Per PD-028 | Yes |
| `active` | Normal | Per entitlement | Yes |
| `suspended` | Suspended by operator (abuse, fraud, non-payment escalation) | **Denied** | **[OPEN — PD-029]** |
| `closed` | Closed by user or operator | **Denied** | No |
| `pending_deletion` | Deletion requested, retention window running | **Denied** | **[OPEN — PD-030]** |

Account status is evaluated at **every** playback authorization, not only at login.

### 4.8 Account deletion
- User-initiated deletion must be available (`CLAUDE.md` §18).
- Deletion removes or irreversibly anonymizes personal data, including watch history,
  within a defined retention window **[OPEN — PD-031]** [LEGAL].
- **Records that must be retained** for compliance — audit logs, rights-related playback
  authorization evidence, financial records — are retained in a form that does not
  identify the person where that is achievable. The tension between deletion obligations
  and retention obligations is real and is **[LEGAL — PD-031]**.
- Deletion propagates to analytics (§26) and to backups within the documented window.
- Deletion is confirmed to the user, and is irreversible after the grace period
  **[OPEN — PD-030]**.

### 4.9 Profile management
See §5.

### 4.10 Device management
See §6.

### 4.11 Active sessions
- The user can list active sessions with device name, device class, approximate location
  **[OPEN — PD-032, privacy-sensitive]**, and last-active time.
- The user can terminate any session, including the current one.
- Terminating a session stops its playback within the revocation interval (PD-026).

### 4.12 Security events
The user can see a security timeline: logins, new device registrations, password changes,
MFA changes, session terminations, and failed-login bursts. **Never** shows credentials,
tokens, or full IP history without a privacy decision **[OPEN — PD-032]**.

### 4.13 Social login readiness
- **Not in launch scope** **[PROPOSED]**.
- The account model must not assume password-only identity: an account must be able to
  carry multiple authentication methods without redesign.
- Provider selection is **[OPEN — PD-033]**. No provider's capabilities are assumed
  (`CLAUDE.md` §1).

### 4.14 2FA / MFA readiness
- **MFA is mandatory for all administrative accounts before production** [CONFIRMED —
  `CLAUDE.md` §7].
- MFA for subscribers is **[PROPOSED — optional, post-launch]**.
- The identity model must support multiple factor types from the start; adding a second
  factor later must not require redesign.

---

## 5. Profiles

### 5.1 Model
An **account** is the billing and security boundary. A **profile** is a viewing identity
within it. Entitlement belongs to the account; personalization belongs to the profile.

### 5.2 Profile attributes

| Attribute | Notes | Status |
|---|---|---|
| Name | Display name, not an identity | [CONFIRMED] |
| Avatar | Selected from a provided set; user image upload is [OPEN — PD-034] (moderation and storage implications) | [PROPOSED] |
| Language | Overrides account default; drives UI and preferred audio/subtitle | [CONFIRMED] |
| Profile type | `standard` or `child` | [PROPOSED] |
| Maturity limit | Maximum content rating this profile may access | [CONFIRMED, value OPEN — PD-035] |
| PIN | Optional for standard profiles; controls access to the profile | [PROPOSED] |
| Watch history | Per profile, private to the profile by default | [CONFIRMED] |
| Favorites | Per profile | [CONFIRMED] |
| Resume points | Per profile, synchronized across devices | [CONFIRMED] |
| Recommendations | Derived per profile | [CONFIRMED] |

### 5.3 Profile limits
- Maximum profiles per account: **[OPEN — PD-036]**. Not guessed.
- At least one profile always exists; the last profile cannot be deleted.
- The account owner's profile cannot be deleted by another profile.

### 5.4 Parental controls and age restrictions
- A **child profile** receives stricter defaults (`CLAUDE.md` §18): restricted maturity
  ceiling, no access to account settings, no purchase capability, and
  **[PROPOSED]** limited or no search outside child-appropriate content.
- Changing parental settings or exiting a child profile requires the **account PIN**,
  which is distinct from a profile PIN. **[PROPOSED]**
- **Content rating scheme is [OPEN — PD-035] and [LEGAL].** No rating system is assumed
  for any territory. **[UNVERIFIED]** This document makes no claim about which
  classification body or scheme applies in any market; that must be established per
  territory with professional advice, and content metadata must carry the scheme
  identifier alongside the rating value so multiple schemes can coexist.

### 5.5 Profile rules
- Profile switching is not authentication and grants no additional privilege (§3.1.6).
- A PIN, where set, is verified **server-side**; the client never decides.
- Deleting a profile deletes its history, favorites, and resume points, subject to the
  retention rules of §33.
- **Watch history is not shared across profiles** without an explicit, recorded design
  decision (`CLAUDE.md` §18).

---

## 6. Device management

### 6.1 Supported device classes

| Class | Platforms | Status |
|---|---|---|
| `web` | Desktop and mobile browsers | [CONFIRMED] |
| `mobile_android` | Android phone | [CONFIRMED] |
| `tablet_android` | Android tablet | [CONFIRMED] |
| `tv_android` | Android TV / Google TV | [CONFIRMED] |
| `mobile_ios` | iPhone | [CONFIRMED] |
| `tablet_ios` | iPad | [CONFIRMED] |
| `tv_tizen` | Samsung Smart TV | [CONFIRMED] |
| `tv_webos` | LG Smart TV | [CONFIRMED] |

**Device class is a rights-relevant attribute**, not a cosmetic label: rights agreements
routinely grant different permissions per device class (§15). Minimum supported OS
versions and model years per platform are **[OPEN — PD-037]** and must be set before
Phase 19; TV platforms are the binding performance constraint (`CLAUDE.md` §19).

### 6.2 Device registration
- A device registers on first authenticated use and is bound to the account.
- Registration records: device class, a stable device identifier, a user-editable display
  name, platform and app version, first-seen and last-seen timestamps.
- Registration is **idempotent** — reinstalling an app must not consume a second slot for
  the same physical device where the platform allows stable identification.
- Registration is rate-limited (`CLAUDE.md` §6.1).

### 6.3 Device identification
- The identifier must be as stable as each platform genuinely permits. **[UNVERIFIED]**
  No specific platform identifier API, its stability, or its persistence guarantees is
  asserted here; each must be verified against official platform documentation during the
  relevant client phase (`CLAUDE.md` §1).
- Device fingerprints are **personal data** and are handled accordingly (§33).
- A client-supplied device identifier is **never trusted as proof of identity** — it is a
  registration key, and the session token is the authority.

### 6.4 Device limits
- **Maximum registered devices per account: [OPEN — PD-038].** Deliberately not chosen —
  the brief explicitly instructs that device limits must not be invented.
- Limits may vary by package (PD-038).
- Behaviour at the limit **[PROPOSED]**: registration is refused with a distinct reason
  code, and the user is offered the device-management screen to remove one.
- **[OPEN — PD-039]** Cooling-off period on device removal (a common anti-sharing control:
  a removed slot cannot be immediately reused). Not chosen; it trades abuse resistance
  against legitimate-user friction.

### 6.5 Device removal
- The user can remove any registered device from device management.
- Removal invalidates that device's sessions and stops its playback within the revocation
  interval (PD-026).
- Removal is recorded in the security timeline.

### 6.6 Session revocation
- Sessions are revocable by the user, by support, and by an administrator.
- Revocation propagates to playback **within a bounded, documented interval** (PD-026) —
  at authorization and, critically, at the edge (`CLAUDE.md` §12).

### 6.7 Concurrent playback rules
- **Maximum concurrent streams per account: [OPEN — PD-040].** Not invented.
- Concurrency may also be constrained **per rights agreement**, independently of the
  package (§15). The effective limit is the **most restrictive** applicable constraint.
- Enforcement is **server-side**, at playback authorization, with a defined behaviour at
  the limit (§12).
- An abandoned session must release its slot within a bounded interval
  **[PROPOSED — REQUIRES VALIDATION: ≤ 120 seconds after last heartbeat, PD-041]**.

### 6.8 Suspicious device behaviour
Signals that **[PROPOSED]** should be detected and surfaced to operations, not silently
enforced against the user without a recorded policy:
- Rapid device registration and removal cycling
- Simultaneous sessions from implausibly distant locations
- A single account's credentials used from an atypical number of networks
- Repeated authorization denials followed by retry storms

**[OPEN — PD-042]** Define the response policy: alert only, throttle, require
re-authentication, or suspend. Automated suspension has real customer-harm potential and
must be an explicit product decision, not an engineering default.

---

## 7. Home experience

### 7.1 Purpose
The home screen answers one question in under a second: **"what can I watch right now?"**
It is not a marketing page and not an exhaustive catalogue.

### 7.2 Sections

| Section | Content | Personalized | Priority |
|---|---|---|---|
| Hero | One or a few promoted items; editorially curated | Partly | P1 |
| Continue watching | In-progress VOD and, where applicable, resumable programmes | Yes | P0 |
| Live now | Channels currently broadcasting, with current programme | Partly | P0 |
| Popular channels | Most-watched channels within the viewer's entitlement | Aggregate | P1 |
| Categories | Entry points into channel and content categories | No | P0 |
| Recommended | Deterministic recommendations (§22) | Yes | P1 |
| Movies | Curated or recent movie rows | Partly | P1 |
| Series | Curated or recent series rows | Partly | P1 |
| Recently added | New catalogue additions | No | P1 |
| Favorites | The profile's favorite channels and content | Yes | P1 |
| Upcoming programmes | Notable programmes starting soon | Partly | P2 |

Section order and composition are **operator-configurable [PROPOSED]**, so editorial
changes do not require a client release.

### 7.3 Content hierarchy
1. Resume what you were watching (highest completion intent)
2. What is live now (the reason a TV product exists)
3. Personal signals — favorites, recommendations
4. Editorial promotion — hero, curated rows
5. Discovery — categories, recently added

### 7.4 Personalization
Personalization is per **profile**, deterministic at launch (§22), and must never surface
content the profile is not entitled to see. **The home screen shows only what the backend
authorizes** — filtering happens server-side, never in the client (`CLAUDE.md` §4.2).

### 7.5 Loading, empty, and error states

| State | Required behaviour |
|---|---|
| Loading | Skeleton placeholders preserving final layout — no layout shift, no spinner-only screens |
| Partial load | Sections render independently; one failed section must not blank the page |
| Empty (new profile) | Show live now, categories, and editorial rows; never an empty screen |
| Empty (no entitlement) | Explain what the account can access and how to change it — never a bare error |
| Error | Per §31: plain-language message, a recovery action, a stable error code, and a logged event |

### 7.6 Responsive behaviour
- **Web**: fluid from small mobile to large desktop; row item counts adapt.
- **Mobile/tablet**: touch targets and safe areas respected; portrait and landscape.
- **TV**: fixed 10-foot layout, overscan-safe margins, typography readable at distance,
  focus always visible (§35).

### 7.7 TV remote behaviour on home
- Predictable D-pad traversal: down moves between rows, left/right within a row.
- Focus is never lost, never trapped, and always visible.
- The first focusable element on entry is deterministic (**[PROPOSED]**: continue
  watching if present, otherwise live now).
- Back from home exits the app after a confirmation **[PROPOSED]**.

---

## 8. Live TV

### 8.1 Experience requirements
Live TV must feel like television: fast channel changes, an accurate guide, and an
obvious live indicator. Everything else on this list is secondary to those three.

### 8.2 Required concepts

| Concept | Behaviour | Priority |
|---|---|---|
| Channel list | Entitled channels, in operator-defined order, with logo, number, and current programme | P0 |
| Channel groups | Operator-defined groupings (e.g. by genre or provider) | P0 |
| Categories | Navigable classification of channels | P0 |
| Channel search | Search by name and number within Live TV | P1 |
| Favorites | Per-profile favorite channels, filterable as a group | P1 |
| Channel sorting | By number, name, or category; user preference persisted per profile | P1 |
| Current programme | Now-playing title, time range, and progress | P0 |
| Next programme | Immediately following title and start time | P0 |
| Live indicator | Unambiguous indication that playback is at the live edge | P0 |
| Player | See §11 | P0 |
| Channel switching | Direct selection, up/down stepping, and number entry where the platform allows | P0 |
| Previous channel | Return to the immediately previous channel with one action | P1 |
| Now/next overlay | Programme information without leaving playback | P0 |
| Full-screen playback | Default on TV; available on all platforms | P0 |

### 8.3 Channel switching behaviour
- Channel change must be perceptibly immediate. **[PROPOSED — REQUIRES VALIDATION]**
  target: ≤ 2.0 s to first frame on the minimum TV device (PD-043).
- Rapid stepping through channels must **debounce** authorization requests rather than
  issuing one per keypress — otherwise zapping becomes an accidental denial-of-service
  against the operator's own authorization service.
- Each committed channel change is a **new playback authorization** (§12). Switching is
  never a way to inherit a previous channel's authorization.

### 8.4 User flow
```
Home → Live TV → Category / Group → Channel → Playback
```
Detailed in `USER_FLOWS.md` (UF-06, UF-07, UF-09).

---

## 9. Channels — product metadata

Product-level attributes only. **No schema, no types, no storage decisions** — those are
Phase 4.

| Attribute | Purpose | Required |
|---|---|---|
| ID | Stable internal identifier | Yes |
| Name | Display name | Yes |
| Slug | Stable URL-safe identifier | Yes |
| Description | Editorial description | No |
| Logo | Channel artwork, multiple sizes | Yes |
| Category | Primary classification | Yes |
| Language | Primary audio language | Yes |
| Country | Origin/associated country | No |
| Provider | Content supplier | Yes |
| EPG identifier | Key linking to guide data | Yes |
| Stream reference | Pointer to the delivery source | Yes |
| Status | `draft` / `published` / `unpublished` / `disabled_by_rights` | Yes |
| Sorting | Operator-defined order / channel number | Yes |
| Visibility | Which packages and device classes may see it | Yes |
| **Rights reference** | **The agreement permitting distribution** | **Yes — mandatory** |
| Playback policy | Restart / catch-up / recording permissions, quality ceiling | Yes |
| Catch-up availability | Whether and for how long (§17) | Yes |
| Recording availability | Whether network recording is permitted (PD-015) | Yes |
| DRM requirement | Whether the channel must be delivered protected | Yes |

**Hard product rule.** A channel **cannot reach `published` without a rights reference**
(`CLAUDE.md` §2.3). This is a validation rule enforced by the system, not a checklist
item. `disabled_by_rights` is a status the system sets **automatically** on expiry — it is
not an operator action, and an operator cannot override it back to `published` while the
rights window is closed.

---

## 10. EPG

### 10.1 Purpose
The guide is the primary navigation surface of live television. An inaccurate guide
damages trust faster than almost any other defect, because the viewer notices immediately
and blames the whole product.

### 10.2 Required behaviour

| Capability | Behaviour | Priority |
|---|---|---|
| Now / next | Current and following programme per channel | P0 |
| Schedule grid | Channels × time, scrollable both axes | P0 |
| Date navigation | Move forward and back by day, within the retained range | P0 |
| Channel navigation | Move between channels without losing time position | P0 |
| Programme details | Title, description, time, duration, genre, rating, artwork | P0 |
| Programme search | Search programmes by title within the retained range | P1 |
| Timezone handling | Stored and computed in UTC; displayed in the viewer's local time | P0 |
| Duration and progress | Elapsed/remaining for the current programme | P0 |
| Live indicator | Clear marking of the current time position in the grid | P0 |
| Restart / catch-up affordances | Shown only where rights permit (§17, §18) | P1 |

**Time handling is absolute** (`CLAUDE.md` §4.1): all storage and computation in UTC,
localization is presentation only. DST transitions must be correct — a guide that is an
hour wrong twice a year is a guide nobody trusts.

### 10.3 EPG range
- Past retention and future horizon are **[OPEN — PD-044]**. Not guessed; both drive
  storage volume, ingest cost, and catch-up behaviour.

### 10.4 Platform variants

| Platform | Presentation |
|---|---|
| **Web EPG** | Full grid, mouse and keyboard, hover previews, deep-linkable to a channel/time |
| **Mobile EPG** | Vertical channel list with horizontal time scroll, or per-channel day list; touch-optimized; grid density reduced |
| **TV EPG** | Full grid optimized for D-pad; predictable focus movement; fast paging by day; low memory footprint on constrained TV hardware |

### 10.5 Missing or stale EPG data

**This must degrade, never fail.** Live TV without a guide is diminished; live TV that
will not play because the guide is missing is broken.

| Condition | Required behaviour |
|---|---|
| No data for a channel | Channel remains **playable**. Show "Programme information unavailable". Never block playback. |
| Data stale (beyond freshness threshold) | Serve what exists, mark it as possibly outdated where user-visible confusion is likely, and raise an operations alert |
| Gaps in a schedule | Render the gap honestly rather than stretching the adjacent programme to fill it |
| Overlapping entries | Resolve by a defined, documented rule and flag for operator review |
| Ingest feed fails entirely | Serve the last good data; alert operations; **never** replace good data with a partial ingest |
| Retrospective correction | Accept corrections and update; catch-up boundaries recomputed where applicable |

Freshness threshold and the overlap-resolution rule are **[OPEN — PD-045]**.

---

## 11. Player

### 11.1 Controls

| Control | Live | Catch-up / Restart | VOD |
|---|---|---|---|
| Play / pause | **[OPEN — PD-046]** (pausing live requires a buffer strategy) | Yes | Yes |
| Volume / mute | Yes | Yes | Yes |
| Fullscreen | Yes | Yes | Yes |
| Seek | No | Within the programme only | Yes |
| Live edge / "go live" | Yes | Yes (return to live) | N/A |
| Restart | Where rights permit (§18) | N/A | N/A |
| Subtitles | Where available | Where available | Where available |
| Audio tracks | Where available | Where available | Where available |
| Quality selection | **[PROPOSED]** auto by default, manual where the platform allows | Same | Same |
| Channel switching | Yes | N/A | N/A |
| EPG information | Now/next overlay | Programme info | Title info |

### 11.2 Playback states

| State | Meaning | User-visible behaviour |
|---|---|---|
| `loading` | Authorization and manifest acquisition in progress | Progress indication; no error before the timeout elapses |
| `playing` | Media advancing | Controls auto-hide; live indicator where applicable |
| `buffering` | Underrun during playback | Buffering indicator; **do not** tear down the session immediately |
| `paused` | User-initiated | Paused state; live sessions may drift from the edge (PD-046) |
| `ended` | VOD or programme finished | Next-episode or return-to-browse affordance |
| `unauthorized` | Authorization denied | Specific, non-technical message per reason code (§12, §31) |
| `expired` | Playback token or session expired mid-stream | **Attempt silent re-authorization once**; on failure, show a recoverable error |
| `unavailable` | Content exists but is not currently deliverable | Explain and offer alternatives |
| `network_error` | Client connectivity failure | Retry with backoff; offer manual retry |
| `source_error` | Upstream/source failure | Report; offer retry; alert operations |

### 11.3 Error behaviour
- Every player error shows: a **plain-language message**, a **recovery action**, and a
  **stable, short error code** the viewer can quote to support.
- **Errors never expose internals** — no stack traces, URLs, vendor errors, tokens, or
  host names (`CLAUDE.md` §10).
- Every error emits an analytics event with the reason code (§26) and a log entry with a
  correlation ID (§32).
- **Distinguish "you may not" from "we could not."** Telling an entitled viewer their
  subscription is invalid during a CDN outage is a support and trust disaster.

---

## 12. Playback authorization — product behaviour

**The single most important behaviour in the product.** Specified here at product level;
designed in Phase 6, implemented in Phase 15. `CLAUDE.md` §12 governs.

### 12.1 What must be verified, every time

Every playback request is authorized against **all** of the following. There is no
fast path that skips any of them:

1. **Authenticated user** — a valid, unexpired, unrevoked session
2. **Account status** — active, not suspended, closed, or pending deletion
3. **Subscription** — a subscription state that permits viewing
4. **Package** — the package includes the requested asset
5. **Entitlement** — the combined decision (§14 of the plan, Phase 14)
6. **Content rights** — an active rights window covering this asset, territory,
   device class, and distribution mode
7. **Device** — registered, not removed, not revoked
8. **Concurrent sessions** — within the effective concurrency limit
9. **Geographic policy** — where a rights agreement requires it, **server-side**, against
   the **current** territory (PD-095)
10. **Playback policy** — restart/catch-up/quality permissions for this asset
11. **Service availability** — KMS TV is commercially available in the **current**
    territory (PD-004, PD-095)

**Check 11 was added by the approval of PD-004** and is deliberately **separate from
check 6**. Service availability and content rights are different questions with different
owners: availability is the operator's commercial and legal readiness in a territory,
rights are a per-asset contractual grant. A territory can be served with a given asset
unlicensed there, and an asset can be licensed for a territory the operator does not yet
serve. Collapsing the two would make one of those states unrepresentable — and the second
of them is the state in which unlicensed distribution happens.

App distribution (§2.3.1 concept 1) is **not** an authorization check, because it is not an
authorization question. It governs whether someone can obtain the application at all, and
by PD-004 item 14 it confers nothing.

**PD-095 adds no twelfth check.** The eight inputs its approval requires at playback time —
subscriber account state, subscription state, package entitlement, current territory,
service availability, content rights, device/platform policy, playback policy — map onto
checks 2, 3, 4, 6/9/11, 11, 6, 7, and 10 respectively. What PD-095 settles is **which
territory** checks 6, 9, and 11 evaluate: **the current one, determined server-side**.
A subscription that follows the subscriber changes check 3's answer while travelling
(it stays valid); it changes nothing about checks 6, 9, or 11.

**PD-049 Q1 adds no twelfth check either.** Its approval — an account may hold `0..N`
commercial grants (§13.4) — changes what checks **4** (package) and **5** (entitlement)
evaluate **over**: a set of grants rather than a single grant. It does not change which
checks run, and it does not weaken any of them. This follows §3.1.5's existing rule that
*"Tier must never alter which checks run — only their outcome."* The rules by which several
grants resolve into one effective entitlement are **[OPEN — PD-099]**; until they are
decided, no resolution behaviour may be assumed, and check 5 has no defined outcome for a
multi-grant conflict. **Where several grants could each reach the same asset, the decision
evidence must record which grant the authorization rested on** (§13.5, §26.3).

### 12.2 Core product rules

- **Possessing a URL is not permission.** A viewer holding a manifest URL must not be
  able to play (`CLAUDE.md` §12).
- **The client is never the authority.** Clients render what the backend authorizes.
- **Client-reported location, device class, and time are never authoritative.**
- Every denial returns a **stable, machine-readable reason code**; clients never parse
  human-readable text (`CLAUDE.md` §10).
- Every decision — allow and deny — is **logged as compliance evidence**, with no
  credentials or tokens in the log.

### 12.3 Expected behaviours

| Scenario | Product behaviour | Reason code family |
|---|---|---|
| **Valid playback** | Authorized session issued; playback starts | `AUTHORIZED` |
| **Expired subscription** | Denied. Explain state, offer renewal path directly from the error | `SUBSCRIPTION_EXPIRED` |
| **Unauthorized channel** (not in package) | Denied. Explain which package includes it, offer upgrade | `NOT_IN_PACKAGE` |
| **Expired content rights** | Denied. **Neutral message** — "not currently available" — never blame the viewer | `RIGHTS_EXPIRED` |
| **Outside licensed territory** (service available, this asset not licensed here) | Denied. Neutral message. **No workaround is offered, suggested, or hinted at** (`CLAUDE.md` §1) | `TERRITORY_RESTRICTED` |
| **Service not available in this territory** (PD-004) | Denied. **Distinct from the above** — the service does not operate here at all, rather than this asset being unlicensed. Neutral, forward-looking message; no workaround offered or hinted | `SERVICE_NOT_AVAILABLE` |
| **Device class not permitted** | Denied. State which device types can play it | `DEVICE_CLASS_NOT_PERMITTED` |
| **Too many devices** | Registration/playback denied; offer device management to remove one | `DEVICE_LIMIT_REACHED` |
| **Too many simultaneous streams** | Denied. Show which sessions are active and offer to stop one **[PROPOSED — PD-047]** | `CONCURRENCY_LIMIT_REACHED` |
| **Revoked device** | Denied; session terminated; user prompted to re-authenticate | `DEVICE_REVOKED` |
| **Expired playback session** mid-stream | One silent re-authorization attempt; on failure a recoverable error | `SESSION_EXPIRED` |
| **Account suspended** | Denied; direct the viewer to support | `ACCOUNT_SUSPENDED` |

**PD-047** matters more than it looks: showing *which* devices are streaming is
genuinely useful, and is also a privacy disclosure across a household. It needs a
decision, not a default.

### 12.4 Playback session properties (product level)
- Short-lived and renewable while viewing continues
- Bound to one viewer, one device, and one asset or session
- Revocable server-side, with revocation effective within PD-026
- Requires periodic heartbeat to retain its concurrency slot (PD-041)
- Never transferable between devices or profiles

---

## 13. Packages

### 13.1 Concept
A **package** is a commercial bundle defining which content an entitled account may
access. It is the bridge between commerce (§14) and rights (§15).

**An account may hold more than one commercial grant at a time** — §13.4, PD-049 Q1
**APPROVED**. Where this section and §13.2 speak of "the package" in the singular, they
describe a single grant; they must not be read as asserting that only one may exist.

### 13.2 Behaviour

| Aspect | Requirement | Status |
|---|---|---|
| Package visibility | Publicly listed, hidden (retention/win-back), or legacy (existing subscribers only, not sellable) | [PROPOSED] |
| Package contents | Channels, VOD collections, and feature entitlements (quality ceiling, concurrency, device allowance) | [CONFIRMED, values OPEN] |
| Package eligibility | May be restricted by territory, platform, or promotion | [PROPOSED] |
| Package activation | Effective on payment confirmation or trial start; entitlement effect is **immediate** | [CONFIRMED] |
| Package expiration | On subscription end; entitlement effect is **immediate** (`CLAUDE.md` §19) | [CONFIRMED] |
| Package upgrade | Takes effect immediately; proration rules **[OPEN — PD-048]** | [PROPOSED] |
| Package downgrade | **[PROPOSED]** takes effect at period end, to avoid mid-period entitlement loss the viewer already paid for | [OPEN — PD-048] |

### 13.3 Package names and contents
The names *Free, Basic, Standard, Premium, Sports, Movies* appear in the brief as
**explicitly unapproved examples**. This specification uses no package names.
**[OPEN — PD-013]** Define the package catalogue: names, contents, allowances, and
eligibility. **[OPEN — PD-014]** Define pricing, currency, and billing periods.

### 13.4 Commercial grants [CONFIRMED — PD-049 Q1 APPROVED · structural]

**A KMS TV account MAY hold more than one commercial grant at the same time.**
The product model **MUST NOT** assume `1 account = exactly 1 commercial grant`.

```
1 account
    ↓
0..N commercial grants
    ↓
effective entitlements
    ↓
playback authorization
```

Multiple grants may coexist, subject to the entitlement-resolution rules that are
**[OPEN — PD-099]**.

**What PD-049 Q1 does not decide.** It is a **structural** approval only. It does **not**
approve the commercial sale of add-ons at launch **[OPEN — PD-049 Q2]**, any tier names or
package contents **[OPEN — PD-013]**, any prices **[OPEN — PD-014]**, or promotions
**[OPEN — PD-012]**. Transactional/PPV purchase is now settled separately and is **not sold
at launch** — **PD-007 APPROVED**, §13.6. A structure that *permits*
several grants obliges no one to *sell* several, and **no add-on pricing, purchase flow,
admin surface, or attach-rate analytics may be built** until PD-049 Q2 is answered.

**No change to playback authorization.** The eleven checks in §12.1 are unchanged and **no
twelfth check is added.** Several grants change what checks 4 and 5 evaluate **over** — a
set rather than a single grant — not which checks run.

**Singular phrasing elsewhere is deliberate.** `USER_FLOWS.md` UF-15 (*"selects **a**
package"*), `REQUIREMENTS.md` FR-PKG-05/06 (*"their package"*), and EC-21 describe the
**commercial shape**, which PD-049 Q2 has not decided. They do not contradict the model
above, and they are **not** reworded now, because rewording them would imply add-ons are
sold.

### 13.5 Six concepts that are not interchangeable [CONFIRMED — PD-049 Q1]

Establishing that grants may coexist makes the distinctions below load-bearing rather than
academic. **These MUST NOT be treated as identical.**

| Concept | What it is | Layer | Customer-facing? |
|---|---|---|---|
| **Account** | The identity that owns the commercial relationship and its profiles, devices, and sessions | Identity | Yes |
| **Subscription** | The commercial relationship over time — the six states of §14.1, append-only history, billing period, renewal. Check **3** | Billing lifecycle | Yes |
| **Commercial grant** | One thing the operator has agreed to give this account. `0..N` per account (PD-049 Q1) | Commercial | Yes, by its label |
| **Tier** | A named, mutually exclusive position on a value ladder. **Not a separate entity** — §3.1.5 | Commercial | Yes |
| **Add-on** | A grant held **in addition to** another. **Whether any is sold is [OPEN — PD-049 Q2]** | Commercial | Yes, if sold |
| **Package** | *"A commercial bundle defining which content an entitled account may access"* (§13.1) — the carrier of content sets and feature allowances. Check **4** | Entitlement input | Name only |
| **Entitlement** | The **derived decision** for one account/profile/asset/moment. Check **5**. Never stored as truth, never computed by a client | Computed, server-side | **No** — only its effect |
| **Content right** | The contractual grant from a rights holder: holder, contract reference, territory, window, device classes, modes, concurrency cap. Check **6** | Rights / compliance | **No — never** |

**The two collapses that must never happen.**

1. **Package merged into subscription.** Under a single-grant assumption they look like one
   thing. They are not: PD-048's scheduled downgrade and PD-049 Q1's coexisting grants both
   require a subscription to persist while its grants change.
2. **Commercial grant merged into content right.** Both answer *"may this be watched"*, but
   a grant is **the operator's commercial promise** and a right is **the rights holder's
   contractual permission**. Merging them makes it possible to sell access the operator does
   not hold — the exact failure `CLAUDE.md` §2 exists to prevent, and the same collapse
   L-009 rejected for service availability versus content rights.

**Compliance consequence.** Where several grants could each reach the same asset, the
playback-authorization evidence required by §12.2 and §26.3 must record **which** grant the
decision rested on. *"The account was entitled"* is not evidence a rights holder can audit.

**Compatibility.** PD-049 Q1 is compatible with **PD-004** (territory attaches to grant
eligibility, not cardinality), **PD-008** (several grants are **not** several tenants — one
operator, one subscription system, no `tenant_id`), **PD-092** (grants stay
server-authoritative; the API returns a **resolved** decision, never a grant set for a
client to reconcile), and **PD-095** (current territory, service availability, and content
rights remain authoritative). **None of those decisions was modified.**

### 13.6 Launch commercial scope [CONFIRMED — PD-007 APPROVED]

> **KMS TV will not sell individual titles or events separately at launch.**

| In scope at launch | Out of scope at launch |
|---|---|
| Recurring subscription model | Pay-per-view |
| Subscription-based commercial grants | One-time title purchase |
| Entitlement-based access | One-time event purchase |
| Rights-based playback authorization | Rental |
| | Transactional content purchase |

**This is a launch-scope decision, not a permanent prohibition.** **Future PPV remains
possible as a separately approved future commercial capability** — it would be a new product
decision with its own record, not a resumption of PD-007. This is deliberately **unlike**
§1.2's treatment of multi-tenancy, which PD-008 places permanently out of scope.

**Future-compatibility requirement, and why nothing is owed against it.** The architecture
must not make future PPV impossible, and must not require a fundamental redesign of the
account / grant / entitlement model to add it. **That requirement is already satisfied by
PD-049 Q1** (§13.4): an account may hold `0..N` commercial grants, so a future PPV grant
would simply be an **asset-scoped, one-off** commercial grant alongside a subscription
grant, in the terms §13.5 already defines. **No preparatory work is required, and none may
be done.**

**Explicitly not to be built — now or as preparation:** PPV purchase flow · PPV billing ·
PPV entitlement logic · PPV rental logic · PPV UI · PPV admin tools · PPV reporting · PPV
payment flow · PPV refund logic. Building any of them would be the speculative
infrastructure §1.2's architectural rules prohibit. **PPV is not a current feature of this
product and appears in no launch feature set.**

**This table says nothing about add-ons.** Whether add-ons are sold at launch is
**[OPEN — PD-049 Q2]**, neither approved nor rejected by PD-007. Package names, contents,
allowances and eligibility remain **[OPEN — PD-013]**; grant resolution rules remain
**[OPEN — PD-099]**; additional VOD content types remain **[OPEN — PD-057]** — **no event
content model is inferred** merely because PPV was considered and declined.

---

## 14. Subscriptions

### 14.1 Lifecycle states [CONFIRMED — from the brief]

| State | Meaning | Playback |
|---|---|---|
| `pending` | Created, awaiting first successful payment or activation | **[OPEN — PD-050]** |
| `active` | Paid and current | Permitted per package |
| `past_due` | Payment failed, retries in progress | **[OPEN — PD-051]** grace behaviour |
| `paused` | Deliberately paused by user or operator | Denied |
| `cancelled` | Cancellation requested; runs to period end | Permitted until period end |
| `expired` | Period ended without renewal | Denied |

**[PROPOSED additions requiring approval]** `trialing` (PD-011) and `suspended`
(operator-initiated, distinct from user-initiated `paused`). Recorded as **PD-052**.
Note: `IMPLEMENTATION_PLAN.md` Phase 12 listed a slightly different state set
(`trial, active, past-due, suspended, cancelled, expired`). **The brief's six states are
authoritative**; the plan will be reconciled at Phase 12. Logged as PD-052.

### 14.2 Transitions
- Every transition is **explicit and audited**; invalid transitions are rejected, not
  silently coerced.
- Every transition that changes entitlement invalidates cached entitlement **immediately**.
- Subscription history is **append-only** — the current state is never the only record.

### 14.3 Behaviours

| Event | Behaviour |
|---|---|
| Activation | On payment confirmation (§28) or trial start; entitlement effective immediately |
| Renewal | Automatic at period end unless cancelled; idempotent — a duplicated provider callback must never double-charge or double-extend |
| Cancellation | User-initiated; access continues to period end **[PROPOSED]**; no immediate termination unless the user explicitly requests it |
| Expiration | Access ends at period end; entitlement invalidated immediately |
| Upgrade / downgrade | Per §13.2; proration **[OPEN — PD-048]** |
| Grace period | **[OPEN — PD-051]** — length and whether playback continues during it |
| Payment failure | Enter `past_due`; retry schedule and dunning **[OPEN — PD-053]**; notify the user (§23) |

### 14.4 Money handling
- Money is **never** a floating-point value; currency is stored with every amount
  (`CLAUDE.md` §9).
- Tax treatment is **[LEGAL — PD-054]**. **[UNVERIFIED]** No claim is made about tax
  obligations in any jurisdiction.

---

## 15. Rights management

**A first-class product requirement, not an administrative afterthought.**
`CLAUDE.md` §2 governs. Nothing in this section is legal advice.

### 15.1 The controlling question
For every distributable asset, the system must answer at any time, without human research:

> **Why are we allowed to serve this, to whom, where, in what form, and until when?**

An asset that cannot answer this **is not distributable**.

### 15.2 Rights concepts

| Concept | Meaning |
|---|---|
| Provider / rights holder | Who granted the right |
| Contract reference | The agreement the grant derives from |
| Territory | Where distribution is permitted |
| Start date / end date | The window during which distribution is permitted |
| Platforms / device classes | Which device classes are permitted |
| Distribution modes | Live · Catch-up · Restart · VOD · Recording · Download |
| Concurrency limit | Contractual cap on simultaneous streams, independent of package |
| DRM requirement | Whether protected delivery is contractually required |
| Quality ceiling | Maximum permitted resolution/bitrate, where the contract specifies one |

### 15.3 Modes are independent — the rule most often broken
**Live rights do not imply catch-up rights. Catch-up rights do not imply restart rights.
None of them imply recording or download rights.** Each mode is granted separately and
enforced separately. This is the most common source of inadvertent breach in time-shifted
television, and it is treated as a first-class product rule rather than an implementation
detail. (Recorded as L-002 in `docs/legal/DECISION_LOG.md`.)

### 15.4 Rights lifecycle behaviour

| Transition | Required behaviour |
|---|---|
| **Becoming active** | Content becomes available automatically at window start. No manual publish step is required, and none may be needed for correctness. |
| **Approaching expiry** | Operator-visible warnings and alerting, at a lead time that allows action **[OPEN — PD-055]**; a dashboard of rights expiring within 24 hours is required (`CLAUDE.md` §14) |
| **Expired** | Content becomes unavailable **automatically and verifiably**. Enforced by **two independent mechanisms**: a scheduled job **and** a re-check at every playback authorization. Either alone is insufficient. |
| **Revoked** | Immediate removal from availability, ahead of the window end; in-progress sessions terminated **[PROPOSED — PD-056]** and cached entitlement invalidated at once |

**Product rule.** The system **must not continue offering content after applicable rights
expire** [CONFIRMED — brief §15 and `CLAUDE.md` §2.1]. This includes hiding it from
browse, denying playback authorization, invalidating caches, and purging delivery caches
where the content was distributed via CDN.

### 15.5 In-progress viewing at the moment of expiry
**[OPEN — PD-056]** When a rights window closes while a viewer is watching: terminate
immediately, or allow the current programme/title to finish? These have different
compliance profiles and different viewer-experience costs. **[LEGAL]** — the answer may
be dictated by contract terms rather than chosen. Not decided here.

### 15.6 Audit and evidence
- All rights records and changes are auditable: who, what, when, against which contract.
- Playback authorization decisions are retained as compliance evidence (§26.3), with
  no credentials or tokens.
- The operator must be able to produce evidence for any sampled asset on demand.

### 15.7 Legal boundary
**[LEGAL]** Rights interpretation, contract drafting, territory definitions, classification
requirements, and retention obligations require qualified professional advice. This
document specifies **system behaviour that makes compliance enforceable and provable** —
it does not determine what any contract permits.

---

## 16. VOD

### 16.1 Content types [CONFIRMED]
`movie` · `series` → `season` → `episode` · `documentary` · other authorized types
**[OPEN — PD-057]** (e.g. concerts, events, short-form).

### 16.2 Capabilities

| Capability | Behaviour | Priority |
|---|---|---|
| Catalogue | Browsable, entitlement-filtered server-side | P0 |
| Categories / genres | Multi-genre classification; navigable | P0 |
| Search | Across titles, people, and descriptions (§19) | P1 |
| Filters | Genre, language, year, rating, availability | P1 |
| Sorting | Recency, popularity, alphabetical | P1 |
| Details | Synopsis, cast/crew, duration, rating, artwork, availability window | P0 |
| Trailers | Where supplied and rights-permitted — **a trailer is separately licensed** | P1 |
| Playback | Per §11 and §12 | P0 |
| Subtitles | Multiple languages where supplied | P0 |
| Audio tracks | Multiple languages where supplied | P0 |
| Favorites / watchlist | Per profile, synchronized (§20) | P1 |
| Watch history | Per profile (§21) | P1 |
| Continue watching | Resume across devices (§21) | P0 |
| Recommendations | Deterministic at launch (§22) | P1 |
| Series navigation | Season selection, next episode, "up next" | P0 |

### 16.3 Availability
- VOD availability is governed by the **rights system**, not by a catalogue flag (§15).
- Titles approaching the end of their window **[PROPOSED]** may be surfaced as "leaving
  soon" — subject to PD-055 lead-time policy.

---

## 17. Catch-up TV

### 17.1 Concept
Watch a programme that has already aired, within a **catch-up window**.

### 17.2 Behaviour

| Aspect | Requirement |
|---|---|
| Catch-up window | **Configurable per channel and per rights agreement — [CONFIRMED as configurable; no universal duration is assumed]**. Default value is **[OPEN — PD-058]**. |
| Programme selection | From the EPG, past programmes are selectable where catch-up is available |
| Availability indication | The guide must clearly distinguish available from unavailable past programmes |
| Playback | Bounded to the programme's start and end, with configurable padding |
| Seek | Permitted **within the programme only** |
| Restrictions | Requires **catch-up distribution rights** — live rights are not sufficient (§15.3) |
| Expiration | On window end, the programme becomes unavailable and recordings are removed |
| Rights validation | Validated at authorization, per programme, not per channel |

### 17.3 EPG accuracy dependency
Catch-up boundaries derive from EPG data, which is imperfect in practice. Required
behaviour: **padding before and after** the scheduled boundary **[OPEN — PD-059]**, and
graceful handling of retrospective schedule corrections. An inaccurate boundary must
produce a slightly imperfect start point, **never a playback failure**.

---

## 18. Restart TV

### 18.1 Concept
"Start this live programme from the beginning" while it is still broadcasting.

### 18.2 Behaviour

| Aspect | Requirement |
|---|---|
| Eligibility | The programme is currently live, restart is enabled for the channel, and the elapsed portion is retained |
| Rights requirement | Requires **restart distribution rights** — a separate grant (§15.3) |
| UI | A restart affordance on the live player and in the now/next overlay, shown **only when genuinely available** |
| Playback transition | Seamless switch from live to the restarted stream, with clear indication of non-live state |
| Return to live | A single, always-available "go live" action |
| Seek | **[PROPOSED]** permitted within the elapsed portion; forward seek limited to the live edge |
| Errors | If restart fails, **fall back to live playback** rather than failing the session |

### 18.3 Interaction with concurrency
A restart session **[PROPOSED]** consumes one concurrency slot, exactly like a live
session. Confirmed under PD-040.

---

## 19. Search

### 19.1 Scope
Channels · programmes (EPG) · movies · series · episodes · categories [CONFIRMED]

### 19.2 Behaviour

| Capability | Requirement | Priority |
|---|---|---|
| Global search | One entry point searching all types, grouped by type in results | P1 |
| Autocomplete | Suggestions as the query is typed, debounced | P1 |
| Typo tolerance | Fuzzy matching; must work for **Georgian, Cyrillic, and Latin** scripts | P1 |
| Transliteration | **[OPEN — PD-060]** cross-script matching (e.g. a Latin-typed query matching Georgian titles) — valuable in a multi-script market, non-trivial to build |
| Filtering | By type, genre, language, availability | P1 |
| Sorting | Relevance by default; alternatives where useful | P2 |
| Entitlement filtering | Results reflect what the profile may see, **filtered server-side** | P0 |
| Empty state | Before typing: suggestions, recent searches, popular content | P1 |
| No-result state | Clear message plus alternatives — never a dead end | P1 |
| TV input | Remote-friendly on-screen keyboard; voice input where the platform provides it **[UNVERIFIED — per-platform capability must be confirmed]** | P1 |

**Multi-script search is a first-order requirement, not a refinement**, given the
confirmed Georgian/English/Russian language set. Georgian script, Cyrillic, and Latin
must all be handled for input, indexing, and collation.

---

## 20. Favorites

| Aspect | Requirement |
|---|---|
| Favorite channels | Per profile; filterable as a group in Live TV |
| Favorite content | Movies and series (watchlist) per profile |
| Favorite programmes | **[PROPOSED]** future EPG programmes, which double as reminders (§23) |
| Synchronization | Immediate across all devices for the profile |
| Ordering | **[PROPOSED]** user-reorderable channel favorites |
| Limits | **[OPEN — PD-061]** maximum favorites per profile, if any |
| Entitlement | A favorite that becomes unentitled remains saved but is shown as unavailable — never silently deleted |

---

## 21. Watch history

| Aspect | Requirement |
|---|---|
| Playback history | Per profile: what was watched, when, on which device class, and how far |
| Continue watching | Titles between defined start and completion thresholds **[OPEN — PD-062]** |
| Resume position | Per profile per title, synchronized across devices, with a defined conflict-resolution rule for concurrent devices |
| History deletion | Per item and in bulk, user-initiated, and effective |
| Synchronization | Near-real-time across devices |
| Retention | **[OPEN — PD-063]** [LEGAL] |

**Privacy requirements (`CLAUDE.md` §18 — binding):**
- Viewing history is **sensitive personal data**
- **Not exposed across profiles** within an account without explicit design
- **Not shared with third parties** without a lawful basis and a recorded decision [LEGAL]
- Deletable by the user, with deletion propagating to analytics
- Child profile history receives stricter defaults

---

## 22. Recommendations

### 22.1 Scope at launch
**No machine learning** [CONFIRMED — brief §22]. A deterministic, explainable system only.

### 22.2 Deterministic fallback — the launch algorithm

Ordered, rule-based, and fully explainable:

1. **Continue watching** — in-progress items, most recent first
2. **Because you watched X** — same series, same genre, or same channel as a recent item
3. **Favorites-adjacent** — content from favorited channels or same-genre content
4. **Popular within entitlement** — most-watched among content this profile may access
5. **Language match** — content matching the profile's language preference
6. **Recently added** — new catalogue entries within entitlement
7. **Editorial** — operator-curated rows as the final fallback

### 22.3 Rules
- **Never recommend content the profile is not entitled to see.** A recommendation the
  viewer cannot play is worse than no recommendation.
- Never recommend content beyond the profile's maturity limit.
- Recommendations are per **profile**, never per account.
- Every recommendation must be explainable — the reason is recorded even when not shown.
- Deterministic: identical inputs produce identical output, which makes it testable.

### 22.4 Signals used
Watch history · favorites · popularity (aggregate) · category preference · language ·
profile preferences. **All within the profile boundary** — cross-profile signal use
requires an explicit privacy decision **[OPEN — PD-064]**.

---

## 23. Notifications

### 23.1 Channels
Email · Push (mobile/TV where supported) · In-app [CONFIRMED]

### 23.2 Events

| Category | Examples | Optional? |
|---|---|---|
| **Security** | New device login, password changed, MFA changed, suspicious activity | **No** — security-critical, classified as non-optional |
| **Account** | Verification, account status changes | No |
| **Subscription** | Activation, renewal reminder, expiry warning, cancellation confirmation | Partly [OPEN — PD-065] |
| **Payment** | Payment failed, retry scheduled, receipt available | No |
| **Content** | New content, new episode of a followed series | Yes |
| **Reminders** | Upcoming favorited programme starting soon | Yes |
| **Service** | Maintenance windows, incidents, service changes | Partly |

### 23.3 Preferences
- Per-category, per-channel preferences, per **account** (not per profile) for security
  and billing; per **profile** for content and reminders **[PROPOSED]**.
- Marketing communications are **opt-in or opt-out per applicable law — [LEGAL, PD-066]**.
  **[UNVERIFIED]** No claim is made about consent requirements in any jurisdiction.
- Security notifications **cannot be disabled** and this is disclosed to the user.

### 23.4 Content rules
- **No sensitive data in a push payload.** Lock screens are public surfaces.
- No credentials, tokens, or full personal records in any notification.
- All templates localized into all supported languages (§29).

---

## 24. Admin Control Center

The operator's control surface. Every section is permission-gated **server-side** and
every mutating action is audited with actor, timestamp, and before/after state.

| Section | Purpose | Major actions |
|---|---|---|
| **Dashboard** | Platform state at a glance | View health, concurrent streams, playback success rate, denial reasons, rights expiring soon, ingest status, payment outcomes |
| **Users** | Subscriber account administration | Search, view, suspend, unsuspend, close; view status history; initiate support actions |
| **Profiles** | Profiles within an account | View, assist with parental settings; **no history browsing without a recorded privacy decision (PD-067)** |
| **Devices** | Registered devices | View, remove, revoke |
| **Sessions** | Active sessions | View, terminate |
| **Channels** | Live channel catalogue | Create, edit, order, publish, unpublish; **rights reference mandatory** |
| **Channel groups** | Channel grouping | Create, edit, order, assign |
| **EPG** | Guide data | View schedules, trigger and monitor ingest, review gaps/overlaps, apply corrections |
| **Catch-up** | Time-shift configuration | Configure windows per channel, monitor recordings, review availability |
| **VOD / Movies / Series / Seasons / Episodes** | On-demand catalogue | Create, edit, structure, schedule, publish; **rights reference mandatory** |
| **Categories** | Classification | Create, edit, order, assign |
| **Assets** | Media and artwork | Upload, review, associate, monitor processing |
| **Packages** | Commercial bundles | Create, edit, define contents and allowances, set visibility |
| **Subscriptions** | Subscription administration | View, change state where permitted, view history |
| **Payments** | Billing operations | View transactions, process refunds, reconcile — **never card data** |
| **Promotions** | Discounts and campaigns | Create, schedule, monitor (subject to PD-012) |
| **Rights** | Rights agreements | Create, edit, attach to assets, view coverage |
| **Contracts** | Contract references | Record and link agreements |
| **Expiration** | Rights expiry management | View expiring windows, warnings, take action |
| **Streaming — Sources** | Ingest sources | Configure, monitor, failover; **provenance mandatory** |
| **Streaming — Encoders** | Transcoding | Monitor jobs, queues, failures; restart |
| **Streaming — Origins** | Origin servers | Monitor, configure, drain |
| **Streaming — CDN** | Delivery | Monitor hit ratio and errors, purge caches |
| **Playback sessions** | Live session diagnostics | Search sessions, inspect authorization decisions and denial reasons, terminate |
| **Streaming — Health** | Stream health | Per-channel status, alerts, history |
| **Analytics** | Product and content reporting | Dashboards, reports, exports (audited) |
| **Notifications** | Messaging | Manage templates, schedule announcements, review delivery |
| **Audit logs** | Accountability | Search and review; **append-only, never editable or deletable** |
| **System configuration** | Platform settings | Manage configuration and integrations; **secrets are never displayed**, only set and rotated |

**Hard rules**
- Publishing any distributable asset requires a rights reference (§9, §15).
- Bulk and destructive operations require preview and explicit confirmation.
- No admin view ever displays credentials, tokens, or payment card data.
- No admin capability may serve content without authorization — "internal only" is not an
  exemption (`CLAUDE.md` §2.2).

---

## 25. Admin roles — conceptual capabilities

Conceptual only. RBAC is designed in Phase 6 and implemented in Phase 9.
Every capability is a **named permission**; roles are **data, not code constants**
(`CLAUDE.md` §8).

| Capability domain | Super Admin | Administrator | Content Manager | Support | Finance | Operations | Analyst |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| View dashboard | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Manage channels / EPG / VOD | ✔ | ✔ | ✔ | — | — | — | — |
| Publish content | ✔ | ✔ | ✔ | — | — | — | — |
| Manage rights & contracts | ✔ | ✔ | **[OPEN — PD-068]** | — | — | — | — |
| View subscriber accounts | ✔ | ✔ | — | ✔ | ✔ | — | — |
| Suspend / close accounts | ✔ | ✔ | — | **[OPEN — PD-069]** | — | — | — |
| Manage devices / sessions | ✔ | ✔ | — | ✔ | — | ✔ | — |
| Manage packages | ✔ | ✔ | — | — | ✔ | — | — |
| View payments | ✔ | ✔ | — | — | ✔ | — | — |
| Process refunds | ✔ | **[OPEN — PD-070]** | — | — | ✔ | — | — |
| Manage streaming infrastructure | ✔ | ✔ | — | — | — | ✔ | — |
| View playback sessions | ✔ | ✔ | — | ✔ | — | ✔ | — |
| View analytics | ✔ | ✔ | ✔ | — | ✔ | ✔ | ✔ |
| Export analytics | ✔ | ✔ | — | — | ✔ | — | **[OPEN — PD-071]** |
| Manage notifications | ✔ | ✔ | ✔ | — | — | — | — |
| View audit logs | ✔ | ✔ | — | — | — | — | — |
| Manage roles & permissions | ✔ | — | — | — | — | — | — |
| Manage system configuration | ✔ | — | — | — | — | ✔ | — |

**Invariants (all roles, no exceptions):** cannot self-escalate · cannot alter or delete
audit logs · cannot view credentials or card data · cannot bypass entitlement to watch
content · MFA required · all mutating actions audited.

---

## 26. Analytics

Three **separate** systems with different audiences, retention, and access controls.
They must not be conflated.

### 26.1 Product analytics — pseudonymous by default
Users · registrations · active users (daily/monthly) · sessions · playback starts ·
playback failures by reason · watch time · popular channels · popular content ·
subscription conversions · churn · device-class distribution · search behaviour ·
recommendation effectiveness.

Rules: pseudonymous identifiers by default; every event has a **stated purpose**;
retention enforced automatically; deletion requests propagate; no credentials or personal
records in payloads (`CLAUDE.md` §18). Joining analytics to identity requires a recorded
decision **[OPEN — PD-072]**.

### 26.2 Operational monitoring
API rate/errors/duration · playback success rate · time-to-first-frame · rebuffer ratio ·
bitrate distribution · concurrent streams · transcode queue depth · ingest freshness ·
CDN hit ratio and origin egress · error rates by platform. Audience: operations. Not
personal data.

### 26.3 Security and compliance auditing
Privileged actions · authentication events · authorization decisions · **playback
authorization decisions as rights-compliance evidence** · rights changes · admin access.
**Append-only**, access-controlled, retention driven by compliance rather than product
need, and containing no credentials or tokens.

---

## 27. Advertising / free tier

**Nothing in this section is approved or planned for implementation.**

If a free or ad-supported tier is approved (PD-005, PD-006), the product requirements
would be: free-tier entitlement paths through the same engine; **free-distribution rights
as a separate grant** [LEGAL]; ad-break signalling in packaging; an ad provider
abstraction validated against two candidates; frequency capping; consent capture [LEGAL];
and measurement.

**FAST channels** (free ad-supported streaming channels) would additionally require
scheduled linear playout of on-demand assets — effectively a channel-origination
capability that does not otherwise exist in this product. It is a substantial engineering
programme, not a configuration option, and is **[OPEN — PD-006]**.

**No advertising provider, ad format, measurement standard, or vendor capability is
assumed or asserted** (`CLAUDE.md` §1).

---

## 28. Payments — product requirements

### 28.1 Absolute rules [CONFIRMED — `CLAUDE.md` §6.2]
- **Card data must never touch KMS TV servers.** Provider-hosted or tokenized flows only.
- PAN, CVV, and track data are never stored, logged, or transmitted through our systems.
- No payment provider's capabilities, fees, webhooks, or supported methods are assumed
  **[UNVERIFIED]** until read from that provider's official documentation.

### 28.2 Required product behaviours

| Behaviour | Requirement |
|---|---|
| Purchase | Select package → provider-hosted payment → confirmation → entitlement effective immediately |
| Renewal | Automatic at period end unless cancelled; **idempotent** |
| Cancellation | Self-service; access to period end **[PROPOSED]**; confirmation notification |
| Refund | Operator-initiated, permission-gated, audited, with defined entitlement consequences |
| Payment failure | Enter `past_due`; retry schedule and dunning **[OPEN — PD-053]**; notify the user |
| Invoices / receipts | Available to the user **[OPEN — PD-073]** [LEGAL — content requirements are jurisdiction-specific] |
| State synchronization | Provider state is the source of truth for payment; **KMS TV is the source of truth for entitlement**. Reconciliation must be automated. |
| Duplicate callbacks | Idempotent — a repeated callback must never double-charge, double-extend, or double-refund |
| Delayed callbacks | Handled correctly out of order; a late callback must not resurrect a superseded state |

### 28.3 Open decisions
**[OPEN — PD-074]** Payment providers. **[OPEN — PD-075]** Payment methods (cards,
bank transfer, local methods, carrier billing, wallets). **[OPEN — PD-076]** App-store
in-app purchase — where store policy requires IAP, it changes commercial economics and
the subscription model materially. **[UNVERIFIED]** No claim is made about any app
store's current policy; each must be read from official documentation at Phase 27.

---

## 29. Internationalization

### 29.1 Languages [CONFIRMED]
**Georgian (primary)** · English · Russian · Spanish

**[OPEN — PD-077]** Confirm the rationale for Spanish alongside Georgian and Russian, and
whether all four are required at launch or phased. This affects translation cost,
QA matrix size, and the search design (§19).

### 29.2 Requirements

| Requirement | Detail |
|---|---|
| Language selector | Available on all platforms; per profile with an account default |
| Locale persistence | Persisted server-side per profile so it follows the viewer across devices |
| Content localization | Titles, descriptions, and artwork localizable per language; per-item fallback where a translation is absent |
| Date / time formatting | Locale-appropriate; **times always computed in UTC** and rendered in the viewer's timezone |
| Number formatting | Locale-appropriate separators; **currency always displayed with its currency** |
| Fallback language | **[PROPOSED]** requested → Georgian → English → any available; final chain **[OPEN — PD-078]** |
| No hard-coded strings | **[CONFIRMED — binding]** Every user-visible string is externalized on every platform |
| Script support | **Georgian (Mkhedruli), Cyrillic, and Latin** must all render, input, sort, and search correctly |
| Typography | Fonts must cover Georgian script on every platform — **[UNVERIFIED]**, per-platform font availability must be confirmed during each client phase, especially on TV platforms where font support is more limited |
| RTL | Not required by the confirmed language set; **[PROPOSED]** do not preclude it architecturally |

Georgian script support on Smart TV platforms is a **known risk** flagged here rather than
assumed: TV platform font coverage is narrower than web or mobile, and this must be
verified early — not discovered during Phase 23.

---

## 30. Accessibility

### 30.1 Standard
**[OPEN — PD-079]** Target conformance level and any applicable legal requirement
[LEGAL]. **[UNVERIFIED]** No claim is made about accessibility legislation in any market.
**[PROPOSED]** adopt a recognized standard as the internal baseline regardless of legal
obligation, because it is the only way to make "accessible" testable.

### 30.2 Requirements by platform

| Requirement | Web | Mobile | TV |
|---|:---:|:---:|:---:|
| Keyboard navigation | Required | — | Required (D-pad) |
| Screen reader support | Required | Required (platform reader) | Required where the platform provides one **[UNVERIFIED]** |
| Contrast minimums | Required | Required | Required — critical at viewing distance |
| Visible focus states | Required | — | **Critical** — the only way to know where you are |
| Captions / subtitles | Required where supplied | Required | Required |
| Caption styling | **[PROPOSED]** user-configurable size and contrast | Same | Same |
| Audio track selection | Required | Required | Required |
| Audio description | **[OPEN — PD-080]** — depends on supplied assets | Same | Same |
| Readable typography | Required | Required | Required — minimum sizes for 10-foot viewing |
| Reduced motion | **[PROPOSED]** respect platform preference | Same | Same |
| Remote navigation | — | — | Required (§35) |

Accessibility is verified per platform in its client phase, not deferred to a
post-launch audit.

---

## 31. Error states — standardized behaviour

Every error state defines four things: a **user-facing message**, a **recovery action**,
a **logging requirement**, and an **analytics event**.

| Condition | User message (intent) | Recovery action | Logged | Analytics event |
|---|---|---|---|---|
| No internet | "You appear to be offline." | Retry; auto-retry with backoff | Client only | `error.network.offline` |
| Server unavailable | "We're having trouble connecting. Please try again shortly." | Retry; status link if available | Yes + correlation ID | `error.server.unavailable` |
| Authentication failure | "Sign-in failed. Check your details." — **identical for unknown identifier and wrong password** | Retry; password recovery | Yes (no credentials) | `error.auth.failed` |
| Session expired | "Please sign in again." | Re-authenticate, then return to the prior context | Yes | `error.auth.session_expired` |
| Subscription expired | "Your subscription has ended." | Direct renewal path | Yes | `error.entitlement.subscription_expired` |
| Content unavailable | "This isn't available right now." — **neutral, never blames the viewer** | Suggest alternatives | Yes | `error.content.unavailable` |
| Rights expired | "This is no longer available." — neutral | Suggest alternatives | Yes — **compliance evidence** | `error.rights.expired` |
| Territory restricted (asset unlicensed here) | "This isn't available in your location." — **no workaround offered or hinted** | Suggest available alternatives | Yes — compliance evidence | `error.rights.territory` |
| Service not available in territory | "KMS TV isn't available in your location yet." — a **normal state, not an error**; distinct from an unlicensed asset; **no workaround offered or hinted** | Offer to be notified if approved (PD-065); no dead end | Yes | `error.service.not_available` |
| Stream unavailable | "We can't play this right now." | Retry; alternative channel | Yes + operations alert | `error.playback.source` |
| EPG unavailable | "Programme information is unavailable." — **playback still works** | None needed; retry in background | Yes + operations alert | `error.epg.unavailable` |
| Payment failure | "Your payment didn't go through." | Update payment method; retry | Yes (**never** card data) | `error.payment.failed` |
| Device limit reached | "You've reached your device limit." | Open device management to remove one | Yes | `error.device.limit` |
| Concurrency limit reached | "You're already watching on another device." | Stop another session (PD-047), or wait | Yes | `error.playback.concurrency` |

**Universal rules.** Errors never expose internals. Every error has a stable, short,
quotable code. Every error offers a path forward — a dead-end error screen is a defect.
Messages are localized into all supported languages (§29).

---

## 32. Security — product requirements

Product-level statements only; controls are designed in Phase 6.
All are subordinate to `CLAUDE.md` §6–§8, which is binding.

| Domain | Product requirement |
|---|---|
| Authentication | Centralized; server-side verification; short-lived tokens; rotating, revocable refresh tokens; no bypass mode on any branch |
| Authorization | Deny by default; server-side on every request; roles and entitlements separated; no client-side entitlement logic |
| Account security | Memory-hard password hashing; breached-password checks **[PROPOSED]**; rate limiting; lockout; anti-enumeration; security event timeline |
| Device security | Registered, identified, limited, removable, revocable; suspicious behaviour detected (PD-042) |
| Session security | Bound to device; revocable; bounded revocation interval (PD-026); CSRF, fixation, and replay protections |
| Playback authorization | The full §12 check on every request; edge validation; short-lived signed URLs; server-side concurrency and geo enforcement |
| Admin security | MFA mandatory; named permissions; least privilege; full audit; separated from subscriber surfaces |
| Rate limiting | On authentication, registration, password reset, device registration, playback authorization, search, and payment endpoints |
| Audit logs | Append-only; who/what/when/where/before/after; no credentials or full payment data |
| Secrets | Never in the repository, never displayed in admin, rotatable without a code change |
| Privacy | See §33 |

---

## 33. Privacy — product requirements

**[LEGAL]** Nothing in this section is legal advice. **[UNVERIFIED]** No claim is made
about the privacy law of any jurisdiction. **[OPEN — PD-081]** Determine which privacy
regimes apply, based on the territories decided in PD-004, with qualified advice.

### 33.1 Product commitments (independent of jurisdiction)

| Commitment | Requirement |
|---|---|
| Minimum collection | Every personal-data field has a stated purpose; nothing is collected "in case it's useful" |
| Purpose statement | Documented per field, reviewable |
| Retention | Defined per category and enforced automatically; values **[OPEN — PD-063, PD-082]** [LEGAL] |
| Account deletion | Implemented, tested, and effective — including analytics and backups within the documented window |
| Data export | User can obtain their data **[OPEN — PD-083]** for scope and format [LEGAL] |
| Watch history deletion | Per item and in bulk; propagates to analytics |
| Analytics preferences | **[OPEN — PD-084]** whether analytics is opt-out, opt-in, or neither [LEGAL] |
| Device / session visibility | The user can see and terminate their own sessions and devices |
| Children's profiles | Stricter defaults; additional obligations likely apply [LEGAL — PD-085] |
| Cross-profile isolation | Viewing history not exposed across profiles without explicit design |
| Third-party sharing | Requires a lawful basis and a recorded decision — never a default [LEGAL] |
| Third-party SDKs | Reviewed for data collection before inclusion; collection disclosed |
| Processing locations | Documented before launch, including cross-border transfers [LEGAL — PD-086] |

### 33.2 Sensitive-data classification
**Viewing history is sensitive.** It reveals politics, religion, health interests,
sexuality, and language. It is treated as sensitive personal data throughout — in access
control, in analytics, in support tooling, and in retention.

---

## 34. Platform requirements

For each platform: navigation · authentication · playback · EPG · search · VOD ·
profiles · settings · error handling.

**Release scope (PD-092 APPROVED):** §34.1 Web, §34.2 Android, and §34.3 Android TV are
**v1.0 launch clients**. §34.4 iOS/iPadOS, §34.5 Samsung Tizen, and §34.6 LG webOS are
**v1.x subsequent clients** — specified now so the architecture accommodates them, **not
implemented at launch**. §34.7 cross-platform invariants apply to all six.

### 34.1 Web — **v1.0 LAUNCH**
- **Navigation** — persistent header; responsive from mobile to large desktop; deep-linkable
  and browser-history correct
- **Authentication** — full account lifecycle; tokens stored per the Phase 6 strategy
- **Playback** — adaptive streaming in-browser; fullscreen; PiP where supported
  **[UNVERIFIED — per-browser]**
- **EPG** — full grid; keyboard and mouse; deep links to channel/time
- **Search** — full global search with autocomplete
- **VOD** — full catalogue, filters, details, series navigation
- **Profiles** — full management including creation, editing, parental controls
- **Settings** — account, devices, sessions, language, notifications, privacy
- **Errors** — full §31 set; graceful offline handling

### 34.2 Android (phone / tablet) — **v1.0 LAUNCH**
- **Navigation** — native patterns; correct back-stack; tablet layouts
- **Authentication** — full lifecycle; tokens in platform secure storage
- **Playback** — adaptive; background/foreground transitions; audio focus; interruption
  recovery; network-change resilience
- **EPG** — mobile-optimized guide
- **Search** — full search with platform keyboard
- **VOD** — full catalogue and playback
- **Profiles / Settings** — full
- **Errors** — full set plus offline and network-transition states

### 34.3 Android TV / Google TV — **v1.0 LAUNCH**
- **Navigation** — 10-foot UI, D-pad only, focus management, no focus traps
- **Authentication** — TV-appropriate entry; **[PROPOSED]** second-screen or code-based
  sign-in, since typing on a TV remote is punishing
- **Playback** — TV-tuned; surface handling; HDMI events; long-session stability
- **EPG** — full TV grid with fast day paging, memory-constrained
- **Search** — on-screen keyboard; voice where the platform provides it **[UNVERIFIED]**
- **VOD** — full catalogue, remote-optimized
- **Profiles** — selection and switching; **[PROPOSED]** creation and editing deferred to
  other platforms
- **Settings** — reduced set appropriate to TV
- **Errors** — full set, remote-navigable, readable at distance

### 34.4 iOS / iPadOS — **v1.x SUBSEQUENT**
- **Navigation** — native patterns; iPad multitasking layouts
- **Authentication** — full lifecycle; tokens in platform secure storage
- **Playback** — adaptive; backgrounding; PiP; AirPlay and casting **gated by rights
  metadata** (§15)
- **EPG / Search / VOD / Profiles / Settings** — as Android, with platform-native patterns
- **Errors** — full set plus interruption and route-change handling

### 34.5 Samsung Tizen — **v1.x SUBSEQUENT**
- **Navigation** — remote-driven 10-foot UI; correct back and exit behaviour
- **Authentication** — TV-appropriate; second-screen sign-in **[PROPOSED]**
- **Playback** — platform media pipeline; long-session stability across model years
- **EPG** — TV grid, aggressively optimized for constrained hardware
- **Search** — on-screen keyboard
- **VOD** — full catalogue, remote-optimized
- **Profiles** — selection and switching
- **Settings** — reduced set
- **Errors** — full set, remote-navigable

### 34.6 LG webOS — **v1.x SUBSEQUENT**
As Tizen, with one addition: **both Magic Remote pointer and directional-key navigation
must be fully supported.** Neither may be a second-class input mode.

### 34.7 Cross-platform invariants
- **No client contains entitlement logic.** Clients render what the backend authorizes.
- **No client duplicates any of the fifteen server-authoritative domains in §1.3.2.**
- All clients use the **same platform-neutral versioned API** (§1.3.1) and the same reason
  codes. **No platform-specific business API exists.**
- All clients report QoE telemetry: startup time, rebuffer ratio, bitrate distribution,
  failure reasons.
- All clients externalize strings and support all four languages.
- All clients ship an attribution/notices screen (`CLAUDE.md` §3.2).

---

## 35. TV remote UX

| Aspect | Requirement |
|---|---|
| **Focus model** | Exactly one focused element at all times. Focus is always visible, with high contrast and a clear indicator. Focus is never lost during navigation, loading, or content updates. |
| **Directional navigation** | Up/down/left/right movement is spatially predictable. Moving right then left returns to the origin. No dead ends, no traps, no invisible focusable elements. |
| **Back behaviour** | Back moves up one level in a predictable hierarchy. From a modal it closes the modal. From player it returns to the prior context. From home it prompts before exit. **Back never silently discards user input.** |
| **OK / select** | Activates the focused element. Long-press behaviour, if used, is discoverable and never the only route to a function. |
| **Playback controls** | Play/pause, channel up/down, and directional keys map to their expected functions. Media keys are honoured where the platform provides them **[UNVERIFIED — per platform]**. |
| **Keyboard** | On-screen keyboard is remote-navigable, supports Georgian, Cyrillic, and Latin input, and remembers layout choice. Physical/BT keyboards supported where available. |
| **Modals** | Trap focus deliberately while open, restore focus on close, and are always dismissible with Back. |
| **Accessibility** | Focus indicators meet contrast minimums; screen reader support where the platform provides it **[UNVERIFIED]**; no interaction depends on colour alone. |
| **Timing** | Navigation feedback is immediate. Held-key repeat is handled without flooding the backend (§8.3 debounce rule). |

---

## 36. Performance requirements

**Every number below is PROPOSED — REQUIRES VALIDATION.** None is approved, and none is
a guarantee. All are validated in Phase 32 under realistic load, on the **minimum**
supported device class per platform (`CLAUDE.md` §19). Recorded collectively as
**PD-087**.

| Metric | Proposed target | Measured where |
|---|---|---|
| API p95 latency (catalogue/read) | ≤ 300 ms | Server-side, excluding client network |
| API p99 latency (catalogue/read) | ≤ 800 ms | Server-side |
| **Playback authorization p95** | **≤ 250 ms** | Server-side — on the critical path of every play |
| **Playback authorization p99** | **≤ 600 ms** | Server-side |
| Time to first frame — VOD | ≤ 2.0 s | Client, minimum device, broadband |
| Time to first frame — live | ≤ 2.0 s | Client, minimum device |
| **Channel change time** | **≤ 2.0 s** | Client, minimum **TV** device (PD-043) |
| Rebuffer ratio | ≤ 0.5% of watch time | Client telemetry, aggregate |
| App cold start — mobile | ≤ 3.0 s to interactive | Minimum device |
| App cold start — TV | ≤ 5.0 s to interactive | **Minimum TV device** — the binding constraint |
| EPG grid load (initial) | ≤ 1.5 s | Minimum device, realistic channel count |
| EPG day navigation | ≤ 800 ms | Minimum device |
| Catalogue/browse load | ≤ 1.0 s | Minimum device |
| Search first results | ≤ 500 ms | Server-side |
| Search autocomplete | ≤ 200 ms | Server-side |
| Admin page load | ≤ 2.0 s | Desktop |

**Measurement rules.** Performance is measured, never asserted. Targets are validated on
the lowest supported device, not a development machine. TV platforms are the binding
constraint on every client target.

---

## 37. Scalability

**No capacity figure is claimed.** `CLAUDE.md` §20 and §19 prohibit asserting capacity
without load testing, which occurs in Phase 32. This section defines the **dimensions**
that must scale and the properties that must hold.

| Dimension | Scaling property required |
|---|---|
| Users / accounts | Horizontal application scaling; no per-user server state |
| **Concurrent sessions** | The defining constraint. Must survive the **spike at the start of a popular live event** — the load pattern that average-load testing misses entirely |
| Channels | Channel count must not degrade EPG grid or catalogue performance non-linearly |
| EPG records | High-volume, time-partitioned, with enforced retention |
| VOD catalogue | Browse and search performance must hold as the catalogue grows |
| API traffic | Stateless tier, horizontally scalable, with rate limiting that holds under load |
| Streaming traffic | Served from the edge. **The application tier never proxies segments.** Origin scales independently and is shielded |
| Admin operations | Bulk operations must not degrade subscriber-facing performance |

**Invariants**
- Stateless application tier — adding an instance is always possible
- Long-running work is queued with bounded concurrency and dead-letter handling
- Every external dependency has a defined, tested behaviour when slow or unavailable
- Degradation under overload is graceful and defined, never collapse
- **Capacity claims require Phase 32 evidence.** Until then, capacity is unknown.

---

## 38. Content and rights safety

**Binding, restated from `CLAUDE.md` §1 and §2.2 and `docs/legal/CONTENT_RIGHTS_POLICY.md`.**

KMS TV supports **only authorized content distribution**. The product must never contain
features whose purpose or primary effect is to:

- bypass DRM or any content protection system
- bypass authentication or authorization
- bypass geo-restrictions or territorial licensing
- steal, harvest, or replay stream credentials
- extract, restream, or re-host protected streams from third parties
- evade content-provider controls, watermarking, or audit mechanisms
- redistribute unauthorized copyrighted content

This applies to **admin tooling and internal diagnostics** exactly as it applies to
subscriber features. *"Internal only" is not an exemption.*

**Specification-level consequences enforced throughout this document:**
- No asset is distributable without a rights basis (§9, §15, §16)
- Rights expiry disables content automatically via two independent mechanisms (§15.4)
- Territory denials offer **no workaround and no hint of one** (§12.3, §31)
- Client-reported location, device class, and time are never authoritative (§12.2)
- Playback requires a backend authorization decision; a URL is not permission (§12.2)
- DRM-readiness means an integration point, never circumvention (§12, PD-088)

---

## 39. Traceability

| This section | Requirements | Flows | Decisions |
|---|---|---|---|
| §1.2 Operator model | FR-OPR-* | — | **PD-008 APPROVED** |
| §1.3 Launch platform scope | FR-PLT-* | — | **PD-092 APPROVED**, PD-037, PD-094 |
| §2.3, §2.3.1 Territories | FR-TER-* | UF-07, UF-13, UF-15, UF-18 | **PD-004 APPROVED**, PD-094 |
| §2.3.2 Travelling subscribers | FR-TRV-* | UF-26 | **PD-095 APPROVED** |
| §3 User types | FR-USR-* | UF-01…UF-05 | PD-022, PD-023 |
| §4 Account | FR-ACC-* | UF-01, UF-02, UF-03, UF-05 | PD-020, PD-024…PD-033 |
| §5 Profiles | FR-PRF-* | UF-04 | PD-034…PD-036, PD-021 |
| §6 Devices | FR-DEV-* | UF-05, UF-20 | PD-037…PD-042 |
| §8–§10 Live/EPG | FR-LIV-*, FR-EPG-* | UF-06…UF-09 | PD-044, PD-045 |
| §11–§12 Player/Authz | FR-PLY-*, FR-AUT-* | UF-07, UF-18, UF-19, UF-20 | PD-026, PD-046, PD-047 |
| §13–§14 Commerce | FR-PKG-*, FR-SUB-* | UF-15, UF-16, UF-17 | PD-010…PD-014, PD-048…PD-053 |
| §15 Rights | FR-RGT-* | UF-19, UF-24 | PD-055, PD-056 |
| §16–§18 VOD/Timeshift | FR-VOD-*, FR-CUP-*, FR-RST-* | UF-10, UF-11, UF-13, UF-14 | PD-057…PD-059 |
| §19–§22 Discovery | FR-SCH-*, FR-FAV-*, FR-HIS-*, FR-REC-* | UF-12 | PD-060…PD-064 |
| §24–§25 Admin | FR-ADM-* | UF-21…UF-25 | PD-067…PD-071 |
| §29–§31 UX quality | FR-I18N-*, FR-A11Y-*, FR-ERR-* | all | PD-077…PD-080 |
| §32–§33 Security/Privacy | NFR-SEC-*, NFR-PRV-* | all | PD-081…PD-086 |
| §36–§37 Performance/Scale | NFR-PER-*, NFR-SCL-* | — | PD-087 |

---

## 40. What is explicitly not specified

Stated as clearly as what is (`CLAUDE.md` §21):

- **No database schema, API contract, or technology choice.** Phases 3–5.
- **No pricing, package names, or commercial terms.** PD-013, PD-014.
- ~~**No target territories.**~~ **RESOLVED — PD-004 APPROVED:** Georgia launch,
  multi-territory architecture, future territories configurable. **No territory beyond
  Georgia is named, planned, or assumed**, and no second territory's legal, tax, payment,
  or classification regime has been researched.
- ~~**No launch platform scope.**~~ **RESOLVED — PD-092 APPROVED:** v1.0 ships Web,
  Android, and Android TV; iOS/iPadOS, Samsung Tizen, and LG webOS follow in v1.x.
  **Exact version numbers are not fixed**, and no v1.x date is assumed.
- **No app distribution territories.** PD-094 — distinct from service availability.
- ~~**No travelling-subscriber policy.**~~ **RESOLVED — PD-095 APPROVED:** subscription
  follows the subscriber; current territory, service availability, and content rights
  remain authoritative. **Still not specified, deliberately:** roaming duration, country
  lists, percentage-of-time rules, VPN/IP rules, travel-specific device restrictions, and
  the location-detection technology — none is defined or assumed.
- **No device, concurrency, profile, or catch-up limits.** PD-036, PD-038, PD-040, PD-058.
- **No content rating scheme.** PD-035 — no scheme is assumed for any market.
- **No legal or regulatory determinations.** All marked [LEGAL].
- **No vendor selections** — payment, CDN, DRM, notification providers. PD-074, PD-088.
- **No capacity figures.** Phase 32.
- **No advertising design.** PD-006.
- **No ML recommendation design.** Deterministic only at launch.
- ~~**No multi-tenancy decision.**~~ **RESOLVED — PD-008 APPROVED:** Option A, single-tenant, one operator. Multi-tenancy, white-label, and SaaS operator platform are **out of scope**, and **no tenant abstraction may be built speculatively**.
