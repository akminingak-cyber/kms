# DECISIONS.md — KMS TV Product Decision Register

**Phase:** 1 — Product specification
**Status:** OPEN — awaiting product owner decisions · **4 APPROVED (PD-004, PD-008, PD-092, PD-095)**
**Version:** 1.5
**Date:** 2026-08-16 (PD-096, PD-097, PD-098 recorded as OPEN; PD-035 research blocker recorded)
**Governing document:** `CLAUDE.md` (binding — §1 prohibits inventing facts)
**Related registers:** `PROJECT_STATE.md` §7 (engineering decisions, `D-nnn`) ·
`docs/legal/DECISION_LOG.md` (legal decisions, `L-nnn`)

---

## 0. Purpose and rules

This register lists **every decision this specification did not make**, and states why.

`CLAUDE.md` §1 prohibits inventing facts, third-party capabilities, licensing
permissions, and commercial terms. The STEP 1 brief reinforces this: *"Do not silently
choose these."* Accordingly, **no decision below has been made on the product owner's
behalf.** Where a recommendation exists it is labelled and reasoned, and it remains a
recommendation until recorded as accepted.

### Status values

| Status | Meaning |
|---|---|
| **OPEN** | Not decided. No default is in effect. |
| **RECOMMENDED** | A specific option is recommended with a stated reason. **Still not decided.** |
| **ACCEPTED** | Decided. Records the decision, date, and rationale. |
| **REJECTED** | Considered and declined, with rationale retained. |
| **SUPERSEDED** | Replaced by a later decision, which is named. Never deleted. |

### Flags

**[LEGAL]** requires qualified legal or professional verification — nothing in this
document is legal advice. **[BLOCKING]** must be resolved before the named phase can
proceed correctly. **[UNVERIFIED]** depends on a third-party fact that has not been read
from a primary source.

### Register summary

Counts below are **derived by counting the decision headings in this document**, not
estimated. Two decisions (PD-002, PD-085) appear twice as cross-reference pointers, so the
document contains 100 headings for 98 unique decisions; the totals row counts unique
decisions. A decision loses its `[BLOCKING]` and `[LEGAL]` heading flags when it is
approved, so the blocking and legal columns count **open** decisions only.

**PD-096, PD-097 and PD-098 carry no `[BLOCKING]` or `[LEGAL]` heading flag.** This is
deliberate, not an oversight: all three arise from PD-035, and which phase they gate — and
whether they require legal verification in their own right — cannot be determined until
PD-035 is resolved. Assigning a flag now would be a determination, and no determination has
been made. The blocking and legal columns are therefore **unchanged at 22 and 24**.

| Category | Decisions | Blocking (open) | Legal (open) |
|---|---:|---:|---:|
| 1. Product identity and market | 7 | 2 | 1 |
| 2. Business model and commerce | 23 | 5 | 6 |
| 3. Identity and account | 13 | 2 | 2 |
| 4. Profiles and parental control | 8 | 1 | 2 |
| 5. Devices and concurrency | 7 | 3 | 0 |
| 6. Content, EPG and time-shift | 9 | 1 | 2 |
| 7. Rights and compliance | 4 | 3 | 2 |
| 8. Discovery and personalization | 7 | 0 | 1 |
| 9. Admin and roles | 6 | 0 | 2 |
| 10. Privacy and data | 8 | 1 | 8 |
| 11. Platform and technical | 8 | 4 | 0 |
| **Total (unique decisions)** | **98** | **22** | **24** |

**Decided so far: 4 of 98.**

| ID | Decision | Status |
|---|---|---|
| **PD-004** | Target territories — **Georgia launch, multi-territory architecture, future territories configurable** | **APPROVED · FINAL** (2026-08-13) |
| **PD-008** | Multi-tenancy — **Option A, single-tenant. One operator, one product. Multi-tenancy, white-label, and SaaS operator platform all OUT OF SCOPE** | **APPROVED · FINAL** (2026-08-13) |
| **PD-092** | Launch platform scope — **Option B. v1.0 ships Web + Android + Android TV; iOS/iPadOS, Samsung Tizen, LG webOS follow in v1.x** | **APPROVED · FINAL** (2026-08-13) |
| **PD-095** | Travelling-subscriber policy — **Option A. Subscription follows the subscriber; current territory, service availability, and content rights remain authoritative** | **APPROVED · FINAL** (2026-08-13) |

**Phase 3 (System architecture) has no remaining blocking decisions.** Phase 4 still
requires **PD-035** (classification scheme) and the structural part of PD-013/PD-049.

**TENANCY ≠ TERRITORY.** PD-008 (single-tenant) and PD-004 (multi-territory) are
compatible and independent: one operator serving many territories is the approved model.
See PD-008 for the comparison table.

**Version 1.5 changes.** Documentation only — **no decision was made, approved, or
resolved.** Three decisions arising from PD-035 recorded as **OPEN** in §4: **PD-096**
(unrated assets), **PD-097** (governing scheme for a travelling subscriber), **PD-098**
(warnings/descriptors as product policy). Decision count 95 → 98; **open blocking unchanged
at 22, open legal unchanged at 24** (see the flag note above). PD-035 gains a recorded
**primary-source research blocker** — the required official sources were unreachable from
the current environment, no bypass attempted — and a **capability-only** technical-model
note that selects no scheme and asserts no legal obligation. Non-blocking documentation
observations recorded in §13.1 without being fixed. No existing decision ID was changed,
renumbered, or altered in status.

**Version 1.4 changes.** PD-095 approved and recorded (§1): Option A, subscription follows
the subscriber. Open blocking 23 → 22; approved 3 → 4. The approval **answers the policy
question the brief declined to answer** and settles which territory the existing
authorization checks use — **the current territory, determined server-side**. It adds **no
twelfth authorization check**, and explicitly defines **no** roaming duration, country list,
or location-detection technology.

**Version 1.3 changes.** PD-092 approved and recorded (§11): Option B, three launch
clients. Open blocking 24 → 23; approved 2 → 3. The approval **confirms** the Option-B
recommendation and adds four binding principles the brief did not propose — launch,
quality, API neutrality, and shared server-authoritative business logic. No decision was
added or removed.

**Version 1.2 changes.** PD-008 approved and recorded (§2): Option A, single-tenant, one
operator. Open blocking 25 → 24; approved 1 → 2. No decision was added or removed. The
approval is **stricter than the Option-B recommendation** in `DECISION_BRIEF.md`, which is
marked superseded rather than amended.

**Version 1.1 changes.** PD-004 approved and recorded (§1). Two consequential decisions
added — **PD-094** (app distribution scope) and **PD-095** (travelling-subscriber policy) —
arising from the three-concept separation the approval mandates; neither is answered by
the approval, so neither has been defaulted. Decision count 93 → 95; open blocking 24 → 25
(PD-004 resolved, two added); open legal 25 → 25 (PD-004 resolved, PD-095 added).

**Correction, 2026-08-13.** The blocking and legal totals were first published as 21 and
24, and the per-category rows alongside them were estimates written before the register
was finished. The measured values at that time were **24 blocking** and **25 legal**. The
difference was not attributable to any particular decisions — no decision was added,
removed, or reclassified, and every entry was correctly flagged in its own text and in §12
from the start. Only the summary row was wrong. Recorded rather than quietly amended, per
`CLAUDE.md` §1 and §24.

---

## 1. Product identity and market

### PD-001 — Product identity and the pre-existing scaffold **[BLOCKING — Phase 1]**
The repository contains a scaffold titled *"KMS – Enterprise Technology Infrastructure &
Security"* — a different product from KMS TV — which is non-buildable (no `src/`), targets
Vite rather than Next.js, and declares a Supabase dependency contradicting the Laravel 12 +
PostgreSQL direction.
**Why it matters.** Two contradictory product definitions in one repository guarantee
contradictory work. Nothing should be built beside it until its fate is settled.
**Options.** (a) Delete it, archiving on a branch; (b) delete outright; (c) retain part of
it — which then requires the full `CLAUDE.md` §3 license intake, since its provenance is
unassessed (L-007).
**RECOMMENDED:** (a). Preserves history at no cost and removes the contradiction.
**Status:** OPEN · Also tracked as `PROJECT_STATE.md` D-006 and `DECISION_LOG.md` L-007.

### PD-002 — Diaspora / out-of-territory service **[LEGAL]**
Whether viewers outside the home territory are served, and with what content.
**Why it matters.** Territorial rights routinely prohibit it. This segment may be
partially or entirely unserviceable, and assuming otherwise builds a market plan on an
unlicensed foundation.
**Status:** OPEN · Required by Phase 1 · Depends on PD-004 — **input now available: Georgia (APPROVED)**. PD-004 separates service availability from content rights, so the diaspora question is now precisely: which territories become *service-available*, and which rights cover them.

### PD-003 — Target user segments
The segments in `PRODUCT_SPEC.md` §2.2 are inferences from the product type and language
set. No market research was supplied.
**Why it matters.** Segments drive feature priority and the launch platform order.
**Status:** OPEN · Required by Phase 1.

### PD-004 — Target territories — **APPROVED · FINAL**

| Field | Value |
|---|---|
| **Decision** | **APPROVED** |
| **Decision ID** | **PD-004** |
| **Launch territory** | **Georgia** |
| **Architecture** | **Multi-territory** |
| **Future territories** | **Configurable** |
| **Status** | **FINAL** |
| **Approved by** | Product owner |
| **Approved on** | 2026-08-13 |
| **Supersedes** | The OPEN status of this entry and the Option-B recommendation in `DECISION_BRIEF.md` Part 1 |

**The decision as approved.** KMS TV launches commercially in **Georgia** first. The
platform architecture MUST be **multi-territory from day one**. Specifically:

1. Georgia is the initial launch territory.
2. The architecture MUST NOT hard-code Georgia as the only possible territory.
3. Future territories MUST be configurable without rewriting the core architecture.
4. **App distribution MUST be treated separately from service availability.**
5. Service availability MUST be configurable per territory.
6. Content availability MUST be controlled independently per territory.
7. Content rights MUST always be territory-aware.
8. Packages MAY differ by territory.
9. Pricing MAY differ by territory.
10. Payment methods MAY differ by territory.
11. Tax configuration MAY differ by territory.
12. Localization MAY differ by territory.
13. A future territory MUST be addable without redesigning the core platform.
14. **Installing the KMS TV application in another country MUST NOT automatically grant
    access to content.**
15. Playback authorization MUST evaluate the applicable territory, service-availability,
    and content-rights rules.

**The three-concept separation (binding).** App distribution, service availability, and
content rights are **three independent concepts and MUST NOT be merged**. Each is a
separate gate, evaluated separately:

| Concept | Governs | Answers |
|---|---|---|
| **App distribution** | Where the client application may be obtained and installed | *Can this person get the app?* |
| **Service availability** | Where KMS TV operates commercially — registration, subscription, billing, support | *Can this person become and remain a customer?* |
| **Content rights** | Per-asset territorial distribution grants | *May this specific asset be served to this person, here, now, in this mode?* |

Worked examples, from the approval:
- A person may install the application in a territory where the **service is not
  commercially available**. Installation grants nothing.
- The service may be available in a territory while a **particular channel is unavailable
  there**, because the applicable content rights do not cover it.

**No future territory is named or assumed.** The approval cites the United States only as
an illustration of addability. `CLAUDE.md` §1 prohibits inferring a launch plan from an
example, and none is inferred here or anywhere else in the specification.

**What remains open.** The territory is now determined; its *legal consequences* are not.
PD-081 (applicable privacy regimes), PD-035 (classification scheme), PD-054 (tax
treatment), and PD-075 (payment methods) each now have a determinate input and remain
**[LEGAL]** and OPEN. **[UNVERIFIED]** This document continues to make no claim about the
regulatory, broadcasting, tax, or data-protection regime of Georgia or of any other
country.

**Consequential new decisions.** The three-concept separation surfaces two questions the
approval does not answer, recorded as **PD-094** (app distribution scope) and **PD-095**
(travelling-subscriber policy) rather than defaulted.

**Recorded in:** `docs/legal/DECISION_LOG.md` L-008 · `PROJECT_STATE.md` §7 D-015 ·
`PRODUCT_SPEC.md` §2.3 and §2.3.1 · `REQUIREMENTS.md` §A28 · `DECISION_BRIEF.md` Part 1.

### PD-094 — App distribution scope **[BLOCKING — Phase 19]**
In which territories is the KMS TV application listed and installable, given that this is
now explicitly **separate** from service availability (PD-004 item 4)?
**Why it matters.** PD-004 establishes that installation grants nothing, but does not say
where installation is offered. Store listing territories are a distribution and marketing
decision with store-policy consequences, and they determine what a person who installs the
app outside a served territory actually sees.
**[UNVERIFIED]** No app store's territorial listing rules or requirements are assumed.
**Options.** Launch territory only · launch territory plus selected others · unrestricted
listing with an in-app "service not available here" state.
**RECOMMENDATION — NOT APPROVED:** none. This is a distribution decision, and PD-004
deliberately separated it from service availability precisely so it could be taken on its
own merits.
**Status:** OPEN · Arising from PD-004 · Required by Phase 19.

### PD-095 — Travelling-subscriber policy — **APPROVED · FINAL**

| Field | Value |
|---|---|
| **Decision ID** | **PD-095** |
| **Decision** | **Option A — Subscription follows the subscriber** |
| **Status** | **APPROVED** |
| **Policy** | **Subscription follows subscriber** |
| **Restriction** | **Current territory + service availability + content rights remain authoritative** |
| **Exact roaming duration** | **NOT DEFINED** |
| **Specific countries** | **NOT DEFINED** |
| **Location-detection technology** | **NOT DEFINED** |
| **Approved by** | Product owner |
| **Approved on** | 2026-08-13 |

**The decision as approved.** An active KMS TV subscription **remains associated with the
subscriber while travelling**. It does **NOT** automatically grant access to every KMS TV
service or every piece of content in every territory.

Access remains subject to all six of:

1. **Current territory**
2. **KMS TV service availability** in that territory
3. **Applicable content rights** in that territory
4. **Package entitlement**
5. **Playback authorization**
6. **Any applicable platform/device policy**

**Core principle (binding).**

> **SUBSCRIPTION OWNERSHIP is separate from CONTENT TERRITORY RIGHTS.**

A subscriber travelling from Georgia to another territory retains an active subscription.
The platform then determines whether the requested service and content are available in
the subscriber's **current** territory.

| Worked example (from the approval) | |
|---|---|
| Subscriber home territory | Georgia |
| Current territory | USA |
| Subscription | **ACTIVE** — it follows the subscriber |
| Requested content **has** applicable rights in the USA | → playback **may be allowed** |
| Requested content **has no** applicable rights in the USA | → playback **must be denied** |

**The USA appears here only because the approval used it to illustrate the principle. It is
not a planned territory**, and no territory beyond Georgia is named, planned, or assumed —
consistent with PD-004, which carries the same disclaimer.

**An active subscription is not a universal content license.** Content available in the
subscriber's home territory is **not** automatically available while travelling.

**Service availability.** The current territory is evaluated against KMS TV service
availability (PD-004). If the service is not available in the current territory, access is
denied per the service-availability policy. The exact user-facing message and recovery flow
**may be defined later**.

**Content rights remain territory-aware.** A channel or content item may be AVAILABLE in
Georgia and UNAVAILABLE in another territory. The product model supports this (FR-TER-05,
`AC-FR-TER-05-1`).

**Nothing about roaming limits is invented.** The following are **explicitly NOT defined**
by this approval and **MUST NOT be assumed** unless separately approved as product
decisions: maximum roaming days · maximum travel duration · country lists ·
percentage-of-time rules · mandatory re-authentication intervals · VPN rules · IP
thresholds · device restrictions specific to travel. These may be set by later decisions
and by applicable legal and business requirements.

**Privacy.** Territory evaluation follows the platform's privacy and data-minimization
requirements (`CLAUDE.md` §18, NFR-PRV-01). **Unnecessary location data must not be
collected.** **No geolocation technology is defined** — the mechanism for determining the
current territory is an implementation decision for a later phase, and remains
**[UNVERIFIED]**: no method, accuracy, or provider is assumed anywhere.

**Effect on playback authorization — a refinement, not a new check.** The eight inputs the
approval requires at playback time (subscriber account state · subscription state · package
entitlement · current territory · service availability · content rights · device/platform
policy · playback policy) all map onto the **existing eleven checks** in
`PRODUCT_SPEC.md` §12.1. PD-095 adds no twelfth check. What it settles is **which**
territory those checks use: **the current territory, determined server-side at
authorization time — never the home territory, and never a client-supplied value.**

**Data-model consequence.** The subscription is **not territory-bound for validity**: it
persists across territories rather than lapsing or suspending on travel. Each authorization
carries a **determined current territory**. Whether an account additionally records a home
territory for *commercial* purposes — pricing, tax, package eligibility, all territory-
scoped under PD-004 — follows from PD-013, PD-014, and PD-054 and is **not decided here**.

**Compatibility.** Compatible with **PD-004** — PD-095 builds directly on its three-concept
separation (app distribution / service availability / content rights) and on its
territory-aware rights model. Compatible with **PD-008** — travelling concerns one
subscriber of the single operator and introduces no tenancy. Neither decision is modified.

**Recorded in:** `PRODUCT_SPEC.md` §2.3.2 and §12 · `REQUIREMENTS.md` §A31 (FR-TRV-01…07) ·
`USER_FLOWS.md` UF-26 · `FEATURE_MATRIX.md` §12 · `docs/legal/DECISION_LOG.md` L-011 ·
`PROJECT_STATE.md` §7 D-023.

### PD-077 — Rationale for Spanish
Georgian (primary), English, and Russian form a coherent set. Spanish alongside them is
unusual and was not explained.
**Why it matters.** Affects translation cost, the QA matrix, search design, and possibly
PD-004. If it signals a second market, that changes the rights and payment strategy.
**Status:** OPEN · Required by Phase 1.

---

## 2. Business model and commerce

### PD-005 — Free tier **[BLOCKING — Phase 12] [LEGAL]**
Whether a free tier exists, and what it includes.
**Why it matters.** Requires an entitlement path for unpaid accounts **and** a rights basis
permitting free distribution — a **separate grant** from paid distribution. Also changes
capacity planning, since free users consume delivery without revenue.
**Status:** OPEN.

### PD-006 — Advertising / FAST channels **[LEGAL]**
Whether the platform carries advertising, and whether it originates FAST channels.
**Why it matters.** Ad insertion, consent capture, measurement, and frequency capping are a
substantial programme. **FAST additionally requires scheduled linear playout of on-demand
assets — a channel-origination capability that does not otherwise exist in this product.**
**[UNVERIFIED]** No ad provider, format, or measurement standard is assumed.
**Status:** OPEN.

### PD-007 — Transactional / pay-per-view
Whether individual titles or events can be purchased separately.
**Status:** OPEN.

### PD-008 — Multi-tenancy — **APPROVED · FINAL**

| Field | Value |
|---|---|
| **Decision ID** | **PD-008** |
| **Decision** | **Option A — Single-tenant KMS TV** |
| **Status** | **APPROVED · FINAL** |
| **Operator model** | **One operator** |
| **Multi-tenancy** | **OUT OF SCOPE** |
| **White-label** | **OUT OF SCOPE** |
| **SaaS operator platform** | **OUT OF SCOPE** |
| **Future multi-tenancy** | Possible future architectural decision, **not part of current implementation** |
| **Approved by** | Product owner |
| **Approved on** | 2026-08-13 |
| **Supersedes** | The OPEN status of this entry and the Option-B recommendation in `DECISION_BRIEF.md` Part 1 |

**The decision as approved.** KMS TV operates as a platform for **one operator/business**.
The initial and production architecture **MUST NOT** implement SaaS multi-tenancy or
multiple isolated operators.

**Explicitly OUT OF SCOPE for the current product:**

- multiple operator tenants
- white-label operators
- tenant-specific deployments managed by a shared control plane
- tenant isolation
- tenant-specific billing
- tenant-specific admin organizations
- tenant-specific content catalogs
- tenant-specific rights domains

**KMS TV is one operator and one product.**

**Architectural principles (binding).** The architecture must remain clean and
single-tenant. Specifically:

1. Do **NOT** introduce a `tenant_id` into every table merely for hypothetical future use.
2. Do **NOT** create artificial tenant abstractions.
3. Do **NOT** implement tenant-aware authorization.
4. Do **NOT** implement tenant-aware billing.
5. Do **NOT** implement tenant-aware content management.
6. Do **NOT** implement tenant-aware caching.
7. Do **NOT** implement tenant-aware storage paths.
8. Do **NOT** implement tenant-aware analytics.
9. Do **NOT** implement multi-operator administration.
10. Do **NOT** implement white-label functionality.

**Future extensibility.** The code must not be *deliberately* made impossible to evolve
toward multi-tenancy in the distant future. But **future multi-tenancy MUST NOT influence
the current data model unless a concrete requirement requires it**, and **no speculative
infrastructure may be built**. If a multi-tenant requirement appears later it is a **new
architectural decision requiring its own ADR**, not a resumption of this one.

**The approved product model:**

```
ONE OPERATOR
ONE KMS TV SERVICE
ONE CENTRAL ADMIN CONTROL PLANE
ONE CONTENT/RIGHTS DOMAIN
ONE SUBSCRIPTION SYSTEM
ONE PAYMENT DOMAIN
ONE ANALYTICS DOMAIN
```

**TENANCY ≠ TERRITORY — the two must not be confused.**

| | Tenancy (PD-008) | Territory (PD-004) |
|---|---|---|
| **Decision** | Single-tenant — **one operator** | Multi-territory — **many territories possible** |
| **What it partitions** | *Who runs the platform* | *Where the platform serves and what may be served there* |
| **In scope?** | Multi-tenancy **OUT OF SCOPE** | Multi-territory **REQUIRED from day one** |
| **In the model** | No tenant concept, no `tenant_id`, no tenant abstractions | Territory is a first-class, configurable concept (FR-TER-01) |

One operator serving several territories is **exactly the approved model**, and it
requires **no tenancy concept whatsoever**. Georgia today, further territories later, all
under one operator, one catalogue, one rights domain, one admin control plane.

**Relationship to the earlier recommendation.** `DECISION_BRIEF.md` recommended **Option
B** (single-tenant code with deployment-per-operator). The approval selects **Option A**,
which is **stricter**: Option B contemplated a second deployment for a second operator,
whereas Option A rules out multiple operators entirely — including the shared control
plane over per-operator deployments that Option B implied. The approval is narrower than
the recommendation, not a variant of it, and this document follows the approval.

**Consequences recorded elsewhere:**
- `PRODUCT_SPEC.md` §1.2 (operator model), §2.5 (B2B wholesale → out of scope),
  §2.9 (multi-tenant/white-label → out of scope), §40
- `REQUIREMENTS.md` §A29 (FR-OPR-01…06)
- `USER_FLOWS.md` Appendix B (multi-tenant onboarding → out of scope, not deferred)
- `docs/legal/DECISION_LOG.md` L-010 · `PROJECT_STATE.md` §7 D-018

### PD-009 — Anonymous playback
Whether unauthenticated visitors can play anything.
**Why it matters.** Anonymous playback has no device identity and no concurrency
attribution, which weakens rights enforcement and complicates compliance evidence.
**Status:** OPEN · Depends on PD-005.

### PD-019 — Public channel line-up visibility
Whether the channel list is visible before registration.
**Status:** OPEN.

### PD-010 — Billing periods
Monthly, annual, or both; whether periods differ per package.
**Status:** OPEN · Required by Phase 12.

### PD-011 — Free trial
Whether trials exist; duration; eligibility; payment method requirement.
**Why it matters.** Adds a subscription state (`trialing`) and trial-abuse controls.
**Status:** OPEN · Required by Phase 12.

### PD-012 — Promotions, discounts, retention offers
**Status:** OPEN · Required by Phase 27.

### PD-013 — Package catalogue **[BLOCKING — Phase 12]**
Names, contents, allowances, and eligibility per package.
**Why it matters.** The names *Free, Basic, Standard, Premium, Sports, Movies* appear in
the brief as **explicitly unapproved examples**. This specification therefore uses no
package names anywhere. Packages are the bridge between commerce and rights, and the
entitlement model cannot be finalized without them.
**Status:** OPEN.

### PD-014 — Pricing and currency **[BLOCKING — Phase 12] [LEGAL]**
Prices, currencies, and whether pricing varies by territory.
**Status:** OPEN · Depends on PD-004 — **input now available: Georgia (APPROVED)**.

### PD-049 — Add-ons versus exclusive tiers
Whether packages stack (base + sports add-on) or are mutually exclusive tiers.
**Why it matters.** Materially shapes the entitlement model. Should be settled before
Phase 4 so the data model is built once.
**Status:** OPEN · Required before Phase 4.

### PD-048 — Proration, upgrade and downgrade timing
**RECOMMENDED:** upgrades immediate, downgrades at period end — a mid-period downgrade
removes access the viewer has already paid for.
**Status:** RECOMMENDED, not accepted · Required by Phase 12.

### PD-050 — Playback during `pending` subscription
**Status:** OPEN · Required by Phase 12.

### PD-051 — Grace behaviour during `past_due` **[BLOCKING — Phase 12]**
Whether playback continues while payment retries run, and for how long.
**Why it matters.** A genuine tension: cutting access immediately on a failed card harms
customers whose card merely expired; leaving it open indefinitely gives away the product.
This is a commercial judgement, not an engineering default, and the flows deliberately do
not assume an answer.
**Status:** OPEN.

### PD-052 — Subscription state set reconciliation
The brief confirms six states: `pending`, `active`, `past_due`, `paused`, `cancelled`,
`expired`. `IMPLEMENTATION_PLAN.md` Phase 12 listed a slightly different set
(`trial, active, past-due, suspended, cancelled, expired`).
**Resolution taken in this specification:** the brief's six states are treated as
authoritative; `trialing` (PD-011) and `suspended` (operator-initiated, distinct from
user-initiated `paused`) are recorded as proposed additions. `IMPLEMENTATION_PLAN.md`
Phase 12 will be reconciled when Phase 12 is entered.
**Status:** OPEN (confirm the final set) — noted here so the discrepancy is not silently
resolved in code later.

### PD-053 — Dunning and retry schedule
**Status:** OPEN · Required by Phase 27.

### PD-091 — Refund policy **[LEGAL]**
Circumstances, timeframes, proration, and entitlement consequences.
**Status:** OPEN · Required by Phase 27.

### PD-054 — Tax treatment **[LEGAL]**
**[UNVERIFIED]** No claim is made about tax obligations in any jurisdiction.
**Status:** OPEN · Depends on PD-004 — **input now available: Georgia (APPROVED)** · Required by Phase 12.

### PD-073 — Invoices and receipts **[LEGAL]**
Whether required, and what they must contain — jurisdiction-specific.
**Status:** OPEN · Required by Phase 27.

### PD-074 — Payment providers **[BLOCKING — Phase 27]**
**[UNVERIFIED]** No provider's capabilities, fees, supported methods, webhook semantics,
or regional availability may be assumed until read from that provider's official
documentation (`CLAUDE.md` §1). `CLAUDE.md` §4.2 requires the abstraction to be validated
against **two** real candidates before it is finalized.
**Status:** OPEN.

### PD-075 — Payment methods
Cards, bank transfer, local methods, wallets, carrier billing. Strongly territory-dependent.
**Status:** OPEN · Depends on PD-004 — **input now available: Georgia (APPROVED)**.

### PD-076 — In-app purchase **[UNVERIFIED]**
Whether app-store IAP is required on mobile and TV platforms.
**Why it matters.** Where store policy mandates IAP, it changes commercial economics and
the subscription model materially, and it constrains where subscription flows can live.
**[UNVERIFIED]** No store's current policy is asserted; each must be read from official
documentation at Phase 27.
**Status:** OPEN.

---

## 3. Identity and account

### PD-020 — Account identifier type
Email, phone, or both. Strongly market-dependent.
**Status:** OPEN · Depends on PD-004 — **input now available: Georgia (APPROVED)** · Required by Phase 8.

### PD-024 — Registration data fields
Which personal data is collected at registration. `CLAUDE.md` §18 requires a stated
purpose per field and prohibits collecting anything "in case it's useful".
**RECOMMENDED:** identifier and password only at registration; collect nothing else
without a specific, stated need.
**Status:** RECOMMENDED, not accepted.

### PD-025 — Password policy
**RECOMMENDED:** a minimum length plus a breached-password check, rather than composition
rules — composition rules produce predictable passwords and frustrate users without
improving outcomes.
**Status:** RECOMMENDED, not accepted · Required by Phase 8.

### PD-026 — Token revocation interval **[BLOCKING — Phase 8]**
`CLAUDE.md` §7 requires a bounded, documented interval — *"eventually" is not an interval*.
**PROPOSED — REQUIRES VALIDATION:** ≤ 60 seconds from revocation to playback stopping.
**Why it matters.** This number is the difference between "the device was removed" and
"the device was removed and can no longer watch." It must be measured in Phase 15, not
asserted.
**Status:** OPEN.

### PD-027 — Account recovery identity verification **[LEGAL]**
The standard support must apply when a user has lost access to their registered identifier.
**Why it matters.** **The most commonly abused route into any account.** The flows
deliberately define no fallback, because an improvised one becomes the weakest link.
**Status:** OPEN · Required by Phase 8.

### PD-028 — Restrictions on unverified accounts
**Status:** OPEN · Required by Phase 8.

### PD-029 — Login behaviour for suspended accounts
Whether a suspended user can sign in to see why and contact support, or is refused outright.
**Status:** OPEN · Required by Phase 8.

### PD-030 — Deletion grace period and login during `pending_deletion`
**Status:** OPEN · Depends on PD-031 · Required by Phase 9.

### PD-031 — Deletion versus retention **[BLOCKING — Phase 9] [LEGAL]**
Deletion obligations conflict with retention obligations for audit records, rights
compliance evidence, and financial records.
**Why it matters.** A real, unavoidable tension. The product commitment is that retained
records should not identify the person where that is achievable — but what is achievable,
and what is required, is a legal determination, not an engineering one.
**Status:** OPEN.

### PD-032 — Session location display
Whether approximate location is shown in the session list. Useful for spotting compromise;
also a privacy disclosure.
**Status:** OPEN · Required by Phase 9.

### PD-033 — Social login providers
**[UNVERIFIED]** No provider's capabilities are assumed.
**Status:** OPEN · P3.

### PD-022 — Support impersonation
Whether support can act as a subscriber.
**Why it matters.** Genuinely useful for diagnosis and a serious privacy and abuse
surface. If approved, it must be visibly flagged, consented to where required [LEGAL], and
fully audited.
**Status:** OPEN · P3.

### PD-023 — Minimum number of super administrators
**Why it matters.** One holder is a single point of failure; many holders dilute
accountability.
**Status:** OPEN · Required by Phase 18.

---

## 4. Profiles and parental control

### PD-035 — Content rating / maturity scheme **[BLOCKING — Phase 4] [LEGAL]**
Which classification scheme applies, per territory.
**[UNVERIFIED]** **This document makes no claim about which classification body or scheme
applies in any market.** No scheme has been assumed anywhere in this specification.
**Why it matters.** Maturity limits gate entitlement on every browse, search, and
recommendation surface. The data model must carry a **scheme identifier alongside the
rating value** so multiple schemes can coexist across territories — which is a Phase 4
design consequence of this decision.

**Primary-source research status (2026-08-16): BLOCKED.** Primary-source legal research for
PD-035 could not be completed from the current Claude environment because external HTTPS
access to the required official sources was denied by the environment network policy. No
bypass was attempted. The required sources — **Matsne** and the **Georgian Communications
Commission / ComCom** — remain unread and require direct verification. **This is a source
*accessibility* problem, not a statement that the sources do not exist or that the law is
unknown.** Recorded in full at `docs/legal/DECISION_LOG.md` **L-012**.

**Technical model consequence — PRODUCT / ARCHITECTURAL REQUIREMENT, NOT A LEGAL
CONCLUSION.** The technical model can proceed **conceptually** without selecting a final
legal authority, because the *shape* of the model is stable across whichever scheme is
ultimately determined. The classification model must be **capable of representing**:
classification scheme · rating value · territory · `effective_from` · `effective_until` ·
authority/source reference · verification status · warnings/descriptors · parental-control
policy. This states a **capability requirement only**. It selects no scheme, asserts no
legal obligation, defines no schema, and does not resolve PD-035. Recorded as
`PROJECT_STATE.md` §7 **D-025**.

**Consequential new decisions.** Three questions arise from PD-035 and are **not** answered
by it or by this record: **PD-096** (unrated assets), **PD-097** (governing scheme for a
travelling subscriber), **PD-098** (warnings/descriptors as product policy).

**Status:** **OPEN — LEGAL REVIEW REQUIRED** · Depends on PD-004 — **input now available:
Georgia (APPROVED)** · primary-source verification outstanding (L-012).

### PD-096 — Behaviour for an asset unrated in a territory's applicable scheme
What happens to an asset that carries no rating in the classification scheme applicable to
a given territory?
**Why it matters.** Multi-territory operation (PD-004) makes this state reachable in normal
use: a licensor supplies a rating under a different scheme, or supplies none. Every browse,
search, recommendation, and playback-authorization surface needs a defined behaviour for
it, and the two obvious behaviours have opposite failure modes — treating unrated as
permitted **fails open**, which is a child-safety failure; treating unrated as blocked
**fails closed**, which withholds licensed content the operator paid for.
**[UNVERIFIED]** No claim is made about what any law requires for unrated content in any
territory. Whether a legally mandated behaviour exists is among the questions blocked by
L-012.
**RECOMMENDATION — NONE OFFERED.** Depends on PD-035, which is unresolved.
**Status:** **OPEN** · Arising from PD-035 · Blocking status and phase **not assigned** —
see §12 note.

### PD-097 — Governing classification scheme for a travelling subscriber's maturity limit
Which classification scheme governs a profile's maturity limit when the subscriber is
present in a territory other than the one in which the limit was set?
**Why it matters.** PD-004 (multi-territory) and PD-095 (subscription follows the
subscriber) together make this reachable in normal use: the limit is expressed in the
scheme of one territory while the catalogue in the current territory is rated under
another. Without a defined governing scheme — and, where schemes differ, a mapping between
them — the system fails open or fails closed exactly as in PD-096. Any such mapping is a
**legal and editorial artefact**, not an engineering lookup table someone fills in from
intuition.
**[UNVERIFIED]** No claim is made about whether any law permits, requires, or forbids
applying one territory's scheme to a subscriber present in another.
**RECOMMENDATION — NONE OFFERED.** Depends on PD-035 and on the mapping question above.
**Status:** **OPEN** · Arising from PD-035, PD-004, PD-095 · Blocking status and phase
**not assigned** — see §12 note.

### PD-098 — Content warnings and descriptors as KMS TV product policy
Are content warnings or descriptors required by **KMS TV product policy**, in addition to
whatever the applicable legal requirements turn out to be?
**Why it matters.** The legal position is unestablished (L-012), but this product question
is separable and does not depend on it: an operator may choose to surface descriptors for
editorial, trust, or parental-experience reasons whether or not law compels it. Deciding it
separately is what keeps a product choice from being presented as a legal obligation, and a
legal obligation from being mistaken for a product preference — the separation D-026
requires.
**[UNVERIFIED]** No claim is made about whether warnings or descriptors are legally
mandatory in any territory.
**RECOMMENDATION — NONE OFFERED.**
**Status:** **OPEN** · Arising from PD-035 · Blocking status and phase **not assigned** —
see §12 note.

### PD-036 — Maximum profiles per account
**Status:** OPEN · Required by Phase 9.

### PD-021 — Profile PIN and parental control policy
Which actions require which PIN; PIN length; lockout on failure.
**Status:** OPEN · Required by Phase 9.

### PD-034 — User-uploaded avatars
Introduces moderation and storage obligations.
**RECOMMENDED:** operator-provided avatar set only at launch.
**Status:** RECOMMENDED, not accepted.

### PD-085 — Children's privacy obligations **[LEGAL]**
**[UNVERIFIED]** No claim is made about children's privacy law in any jurisdiction.
**Status:** OPEN · Depends on PD-004 — **input now available: Georgia (APPROVED)**.

---

## 5. Devices and concurrency

### PD-037 — Minimum supported OS versions and model years **[BLOCKING — Phase 19]**
Per platform.
**Why it matters.** `CLAUDE.md` §19 makes the **lowest** supported device the binding
performance constraint. Every client performance target is meaningless until this is set,
and TV platforms are where it bites hardest.
**Status:** OPEN.

### PD-038 — Maximum registered devices per account **[BLOCKING — Phase 9]**
**Deliberately not proposed.** The STEP 1 brief explicitly instructs that device limits
must not be invented. May vary by package.
**Status:** OPEN.

### PD-040 — Maximum concurrent streams **[BLOCKING — Phase 15]**
**Deliberately not proposed**, same reason. Note that a **rights-agreement concurrency cap
may be lower than the package cap; the most restrictive applies** — so this decision sets a
ceiling, not the effective limit.
**Status:** OPEN.

### PD-039 — Device slot cooling-off period
A common anti-sharing control: a removed slot cannot be immediately reused.
**Why it matters.** Trades abuse resistance against genuine-user friction — a family
replacing a broken TV is indistinguishable from account sharing without it, and
indistinguishable *and inconvenienced* with it.
**Status:** OPEN.

### PD-041 — Abandoned session slot-release interval
**PROPOSED — REQUIRES VALIDATION:** ≤ 120 seconds after the last heartbeat.
**Why it matters.** Too long and viewers are locked out of slots they are not using; too
short and a brief network blip costs them their session.
**Status:** OPEN.

### PD-042 — Response to suspicious device behaviour
Alert only, throttle, require re-authentication, or suspend.
**Why it matters.** **Automated suspension has real customer-harm potential** and must be
an explicit product decision, never an engineering default. The specification therefore
assumes no automated punitive action.
**Status:** OPEN.

### PD-047 — Displaying active sessions on a concurrency denial
Showing *which* devices are streaming is genuinely useful, and is also a privacy
disclosure within a household — one profile learns what another is doing.
**Status:** OPEN · Required by Phase 15.

---

## 6. Content, EPG and time-shift

### PD-044 — EPG retention and future horizon **[BLOCKING — Phase 11]**
How far back and forward the guide extends.
**Why it matters.** Drives storage volume, ingest cost, catch-up availability, and EPG
query performance.
**Status:** OPEN.

### PD-045 — EPG freshness threshold and overlap-resolution rule
When data is considered stale, and how overlapping entries are resolved.
**Status:** OPEN · Required by Phase 11.

### PD-058 — Default catch-up window
**Confirmed as configurable** per channel and per rights agreement; **no universal
duration is assumed** anywhere in this specification, per the brief's explicit
instruction. The **default** value remains undecided.
**Status:** OPEN · Required by Phase 26.

### PD-059 — Catch-up boundary padding
Padding before and after the scheduled programme boundary, to absorb EPG inaccuracy.
**Why it matters.** Without it, imperfect guide data produces programmes that start late
or cut off early — a visible quality failure with an invisible cause.
**Status:** OPEN · Required by Phase 26.

### PD-015 — Network recording / DVR **[LEGAL]**
**Why it matters.** Recording rights are a **separate grant**. Storage cost scales with
subscriber count if per-user, or with catalogue if shared — and the two have very
different architectures.
**Status:** OPEN · P3.

### PD-016 — Offline download **[LEGAL]**
Requires download rights, DRM offline licences, and a device storage policy.
**Status:** OPEN · P3.

### PD-017 — 4K / HDR delivery
Higher bitrate ladders, device capability gating, materially higher CDN cost.
**Status:** OPEN.

### PD-057 — Additional VOD content types
Beyond movie, series, season, episode, documentary.
**Status:** OPEN.

### PD-046 — Live pause behaviour
Whether live playback can be paused, and how far it may drift from the edge.
**Why it matters.** Requires a buffer strategy and interacts with restart and catch-up.
**Status:** OPEN.

---

## 7. Rights and compliance

### PD-055 — Rights expiry warning lead time **[BLOCKING — Phase 13]**
How far ahead operators are warned.
**Why it matters.** Too short and there is no time to renegotiate; too long and warnings
become noise that gets ignored — which is the same as having none. `CLAUDE.md` §14
mandates a "rights expiring within 24 hours" metric regardless of this value.
**Status:** OPEN.

### PD-056 — In-progress viewing at the moment of rights expiry **[BLOCKING — Phase 15] [LEGAL]**
When a rights window closes while a viewer is watching: terminate immediately, or allow
the current programme or title to complete?
**Why it matters.** Different compliance profiles and different viewer costs. **The answer
may be dictated by contract terms rather than chosen.** Every flow that touches it
(UF-10, UF-11, UF-19, EC-13, EC-30) explicitly declines to assume an answer.
**Status:** OPEN.

### PD-002 — see §1 · Diaspora / territorial service **[LEGAL]**

### PD-088 — DRM provider(s) **[BLOCKING — Phase 30]**
**[UNVERIFIED]** No DRM provider's capabilities, platform coverage, licensing terms, or
policy support may be assumed. `CLAUDE.md` §4.2 requires validation against two candidates.
**Restated:** DRM-readiness means an integration point. It never means implementing,
weakening, or working around any protection system (`CLAUDE.md` §1, §12).
**Status:** OPEN.

---

## 8. Discovery and personalization

### PD-060 — Cross-script transliteration in search
Whether a Latin-typed query matches Georgian or Cyrillic titles.
**Why it matters.** Valuable in a multi-script market — viewers type in whichever keyboard
is to hand — and non-trivial to build well.
**Status:** OPEN · P3.

### PD-061 — Favorites limits
**Status:** OPEN.

### PD-062 — Continue-watching thresholds
The start threshold (below which an item is not "in progress") and completion threshold
(above which it is finished).
**Status:** OPEN · Required by Phase 25.

### PD-064 — Cross-profile signal use in recommendations
**RECOMMENDED:** no cross-profile signals. `CLAUDE.md` §18 treats viewing history as
sensitive and requires explicit design before crossing profile boundaries — and a
recommendation that reveals what someone else in the household watched is exactly the
failure that rule exists to prevent.
**Status:** RECOMMENDED, not accepted.

### PD-065 — Optionality of subscription notifications
Which subscription notifications a user may disable.
**Status:** OPEN.

### PD-066 — Marketing consent model **[LEGAL]**
Opt-in or opt-out. **[UNVERIFIED]** No claim is made about consent requirements in any
jurisdiction.
**Status:** OPEN · Depends on PD-004 — **input now available: Georgia (APPROVED)**.

### PD-080 — Audio description
Depends on whether described audio assets are supplied by content providers.
**Status:** OPEN.

---

## 9. Admin and roles

### PD-067 — Admin access to viewing history **[LEGAL]**
Whether, and under what circumstances, staff may view an identifiable viewer's history.
**Why it matters.** Investigating a playback failure does **not** license browsing what
someone watches. Support tooling is a common exfiltration path and is treated as one
throughout the specification.
**RECOMMENDED:** no history browsing by default; access only under a recorded,
purpose-limited, audited exception.
**Status:** RECOMMENDED, not accepted.

### PD-068 — Whether Content Managers may edit rights agreements
**Why it matters.** Rights are the compliance boundary. Separating catalogue management
from rights management is the safer default; combining them is more convenient.
**Status:** OPEN · Required by Phase 18.

### PD-069 — Whether Support may suspend or close accounts
**Status:** OPEN · Required by Phase 18.

### PD-070 — Whether Administrators may process refunds
**Status:** OPEN · Required by Phase 18.

### PD-071 — Whether Analysts may export analytics
**Why it matters.** Export is the point at which pseudonymous aggregate data leaves
controlled systems.
**Status:** OPEN · Required by Phase 29.

### PD-072 — Joining analytics to identity **[LEGAL]**
`CLAUDE.md` §18 requires a recorded decision and a stated purpose.
**Status:** OPEN · Required by Phase 29.

---

## 10. Privacy and data

### PD-081 — Applicable privacy regimes **[BLOCKING — Phase 6] [LEGAL]**
Which privacy laws apply, based on PD-004.
**[UNVERIFIED]** **This specification makes no claim about the privacy law of any
jurisdiction.** Determining applicability requires qualified advice.
**Status:** OPEN.

### PD-082 — Retention periods per data category **[LEGAL]**
**Status:** OPEN · Depends on PD-081.

### PD-063 — Watch history retention **[LEGAL]**
Called out separately because viewing history is classified as sensitive throughout.
**Status:** OPEN · Depends on PD-081.

### PD-083 — Data export scope and format **[LEGAL]**
**Status:** OPEN · Depends on PD-081.

### PD-084 — Analytics preference model **[LEGAL]**
Opt-out, opt-in, or neither.
**Status:** OPEN · Depends on PD-081.

### PD-086 — Data processing locations and cross-border transfers **[LEGAL]**
`CLAUDE.md` §18 requires documentation before launch.
**Status:** OPEN · Depends on PD-004 — **input now available: Georgia (APPROVED)** — and on PD-081.

### PD-079 — Accessibility conformance target **[LEGAL]**
**[UNVERIFIED]** No claim is made about accessibility legislation in any market.
**RECOMMENDED:** adopt a recognized standard as the internal baseline **regardless of
legal obligation** — it is the only way to make "accessible" testable rather than
aspirational.
**Status:** RECOMMENDED, not accepted.

### PD-085 — see §4 · Children's privacy **[LEGAL]**

---

## 11. Platform and technical

### PD-092 — Launch platform scope — **APPROVED · FINAL**

| Field | Value |
|---|---|
| **Decision ID** | **PD-092** |
| **Decision** | **Option B** |
| **Status** | **APPROVED · FINAL** |
| **Launch platforms** | **Web · Android · Android TV** |
| **Subsequent platforms** | **iOS/iPadOS · Samsung Tizen · LG webOS** |
| **Approved by** | Product owner |
| **Approved on** | 2026-08-13 |
| **Relationship to recommendation** | **Confirms it.** Option B was the recommended option; the approval adopts it and adds four binding principles the brief did not propose |

**The decision as approved.** The initial production launch ships **three** clients:

```
v1.0    Web + Android + Android TV
v1.x    iOS / iPadOS
v1.x    Samsung Tizen
v1.x    LG webOS
```

Exact version numbers are **not fixed**.

**Launch principle (binding).** The first production release **must prioritize quality and
stability over maximum platform count**. KMS TV MUST NOT attempt to launch all six clients
simultaneously. The three launch clients MUST be production-quality and MUST pass the
project's full acceptance, security, compatibility, and regression gates.

**Quality principle (binding).** *Three production-quality clients are preferable to six
incomplete clients.* **No platform may be declared production-ready until it passes all
ten of:** functional acceptance tests · playback tests · authentication tests ·
authorization tests · device/session tests · network failure tests · regression tests ·
performance checks · security checks · platform-specific compatibility testing.

**API principle (binding).** The backend API MUST remain **platform-neutral**.
Platform-specific *business* APIs — `/api/android/`, `/api/android-tv/`, `/api/samsung/`,
`/api/lg/` — MUST NOT be created. The model is a **shared versioned API** (`/api/v1/`),
and **all clients consume the same authoritative business logic**. Platform-specific
behaviour may exist at the **client/player layer** where genuinely required, never in the
business API.

**Shared business logic (binding).** The following remain **server-authoritative and
platform-independent**, and **MUST NOT be duplicated independently inside any client**:

authentication · authorization · users · profiles · devices · sessions · channels · EPG ·
packages · subscriptions · entitlements · rights · playback authorization · payments ·
account state

This restates and strengthens `CLAUDE.md` §4.2 — *"Client applications MUST NOT contain
business rules that determine entitlement"* — extending it from entitlement to the full
list above.

**TV product requirement.** Android TV is a launch platform **because KMS TV is
fundamentally a television/OTT product**. The Android TV launch client MUST support the
approved product requirements for: remote navigation · focus management · Live TV · EPG ·
playback · profiles · search · VOD where included in the applicable release scope ·
authentication · error handling · session and device management.

**Future platform strategy.** The architecture MUST be designed from day one so that
iOS/iPadOS, Samsung Tizen, and LG webOS can each be **added later without redesigning the
core business architecture**. During architecture and API design their requirements MUST be
explicitly considered — but those clients **MUST NOT be implemented during the initial
launch phase**, and **no placeholder application may be created merely to claim platform
support**.

**Compatibility.** PD-092 is compatible with **PD-004** (Georgia launch territory,
multi-territory architecture) and **PD-008** (single-tenant KMS TV). Neither is modified.
The three decisions are orthogonal: PD-092 governs *which clients ship*, PD-004 *where the
service operates and what may be served there*, PD-008 *how many operators exist*.

**Consequences for open decisions and blockers.**
- **PD-037** (minimum supported OS versions and model years) is now **narrowed to the three
  launch platforms** for v1.0 purposes, and becomes correspondingly more urgent. Still open.
- **PD-094** (app distribution scope) now has a concrete initial store set to reason about.
  Still open.
- **Blocker B-005** (Android SDK absent) moves **onto the launch critical path** — both
  Android and Android TV are launch platforms.
- **Blocker B-004** (Apple toolchain unavailable on Linux) moves **off the launch critical
  path**, but macOS procurement still gates the v1.x iOS release and its lead time is
  unchanged.
- **Blocker B-006** (Tizen/webOS SDKs and developer registration) is deferred to v1.x, but
  **registration lead time is calendar time** and should still begin during Phase 3.

**Recorded in:** `PRODUCT_SPEC.md` §1.3 and §34 · `REQUIREMENTS.md` §A30 (FR-PLT-01…09) ·
`FEATURE_MATRIX.md` §0.4 · `DECISION_BRIEF.md` Part 1 · `PROJECT_STATE.md` §7 D-020.

### PD-018 — Additional platforms
Roku, Fire TV, Apple TV, Vidaa, operator set-top boxes. Each is a full client project with
its own store and certification.
**Status:** OPEN · P3.

### PD-093 — CDN provider **[BLOCKING — Phase 17]**
**[UNVERIFIED]** No CDN's capabilities, signed-URL semantics, purge behaviour, origin
shielding, or regional coverage may be assumed until read from official documentation.
`CLAUDE.md` §4.2 requires validation against two candidates before the abstraction is
finalized.
**Status:** OPEN.

### PD-043 — Channel change time target
**PROPOSED — REQUIRES VALIDATION:** ≤ 2.0 s on the minimum TV device.
**Why it matters.** Channel change speed is the single most noticed quality attribute of a
live TV product. It is also the hardest target on constrained TV hardware.
**Status:** OPEN · Validated in Phase 32.

### PD-087 — Performance targets **[BLOCKING — Phase 32]**
The full set in `PRODUCT_SPEC.md` §36 and `REQUIREMENTS.md` §B3.
**Every value is PROPOSED — REQUIRES VALIDATION.** None is approved and none is a
guarantee. All are validated in Phase 32 on the **minimum** supported device per platform.
**Status:** OPEN.

### PD-089 — Availability target **[BLOCKING — Phase 34]**
A numeric availability objective.
**Status:** OPEN.

### PD-090 — RPO and RTO **[BLOCKING — Phase 34]**
`CLAUDE.md` §17 requires both numerically before production — *an undefined target cannot
be met*. Also tracked as `PROJECT_STATE.md` D-011.
**Status:** OPEN.

### PD-078 — Language fallback chain
**PROPOSED:** requested → Georgian → English → any available.
**Status:** RECOMMENDED, not accepted.

### Carried forward from Phase 0 (`PROJECT_STATE.md` §7)

| ID | Decision | Status | Note |
|---|---|---|---|
| D-006 | Fate of the pre-existing scaffold | OPEN | Same decision as PD-001 |
| D-007 | Local runtime strategy (Docker daemon / native services) | OPEN | Blocks Phase 7 |
| D-008 | PHP version target — 8.4.19 installed, Laravel 12 range unverified | OPEN | Verify against official docs at Phase 7 |
| D-009 | Web framework — Next.js target vs. Vite in repository | OPEN | Ties to PD-001 and PD-092 |
| D-010 | Static analysis level and coverage thresholds | OPEN | Set at Phase 7 |
| D-011 | RPO / RTO | OPEN | Same decision as PD-090 |

---

## 12. Decisions required before each phase

Phases cannot be correctly designed while their blocking decisions are open. This is the
practical output of this register.

| Before | Must be resolved |
|---|---|
| **Phase 2** (Requirements) | PD-003, PD-077 — segments and language rationale shape priority |
| **Phase 3** (Architecture) | ~~PD-004~~ **APPROVED** · ~~PD-008~~ **APPROVED** · ~~PD-092~~ **APPROVED — Option B, Web + Android + Android TV at launch** — ✅ **no blocking decisions remain for Phase 3** |
| **Phase 4** (Database) — added | ~~PD-095~~ **APPROVED — subscription follows the subscriber; current territory authoritative at authorization** |
| **Phase 19** (Web TV) — added | **PD-094** (app distribution scope) |
| **Phase 4** (Database) | PD-035 (rating scheme), PD-049 (add-ons vs tiers), PD-013 (package structure) |
| **Phase 6** (Security & threat model) | PD-081 (privacy regimes), PD-026 (revocation interval) |
| **Phase 7** (Backend foundation) | D-007, D-008, D-010 |
| **Phase 8** (Authentication) | PD-020, PD-025, PD-027, PD-028, PD-029 |
| **Phase 9** (Users/devices) | **PD-038** (device limit), PD-036, PD-031, PD-032 |
| **Phase 11** (EPG) | PD-044, PD-045 |
| **Phase 12** (Packages/subscriptions) | **PD-013**, **PD-014**, PD-010, PD-050, **PD-051**, PD-052 |
| **Phase 13** (Rights) | **PD-055** |
| **Phase 15** (Playback authorization) | **PD-040** (concurrency), **PD-056** (expiry mid-play), PD-041, PD-047 |
| **Phase 17** (Origin/CDN) | **PD-093** |
| **Phase 18** (Admin) | PD-023, PD-068, PD-069, PD-070 |
| **Phase 19** (Web TV) | **PD-037** (minimum devices — now narrowed to the three launch platforms), PD-079, D-009 |
| **Phase 26** (Catch-up/Restart) | PD-058, PD-059 |
| **Phase 27** (Payments) | **PD-074**, PD-075, PD-076, PD-053, PD-091, PD-073 |
| **Phase 29** (Analytics) | PD-072, PD-071, PD-084 |
| **Phase 30** (DRM) | **PD-088** |
| **Phase 32** (Load/performance) | **PD-087**, PD-043 |
| **Phase 34** (Disaster recovery) | **PD-089**, **PD-090** |

**Not yet placed in this table: PD-096, PD-097, PD-098.** All three arise from PD-035 and
none appears above, because the phase each one gates depends on how PD-035 is resolved.
Placing them now would assert a dependency that has not been determined. They are listed
here so they are not lost, and they must be assigned a phase — or explicitly recorded as
non-blocking — at the same time PD-035 is decided.

---

## 13. Unresolved ambiguities in the brief itself

Distinct from decisions — these are places where the STEP 1 brief was internally
ambiguous or incomplete, recorded rather than resolved by assumption.

| # | Ambiguity | Handling in this specification |
|---|---|---|
| **A-01** | **Georgian primary + Russian + Spanish.** Spanish is unusual in this set and unexplained. | Recorded as PD-077. All four treated as required; no market inferred from the set. |
| **A-02** | **No target territory stated**, yet rights, geo-enforcement, privacy, tax, and classification all depend on it. | **RESOLVED 2026-08-13** by the approval of PD-004: Georgia is the launch territory, the architecture is multi-territory, and future territories are configurable. No territory beyond Georgia is assumed anywhere; the approval's mention of the United States is an illustration of addability, not a plan. |
| **A-03** | **Subscription state sets differ** between the STEP 1 brief and `IMPLEMENTATION_PLAN.md` Phase 12. | Brief treated as authoritative; discrepancy recorded as PD-052 rather than silently reconciled. |
| **A-04** | **Package names given as examples** and explicitly unapproved. | No package names used anywhere in this specification. Recorded as PD-013. |
| **A-05** | **"Household/profile user" listed as a user type** alongside account-level types. | Modelled as a profile within an account, not a separate identity type. Documented in `PRODUCT_SPEC.md` §3.1.6. |
| **A-06** | **"Free user" and "premium subscriber" listed as distinct user types**, but both are subscription states of a registered user. | Modelled as entitlement outcomes, not separate identities. Free tier existence recorded as PD-005. |
| **A-07** | **Recording listed in admin sections and rights concepts**, but no recording feature was specified. | Recorded as PD-015; rights model carries a recording mode; no feature specified. |
| **A-08** | **No launch platform scope given**, though six platforms are named. | Recorded as PD-092 with a proposed order and explicit reasoning. |
| **A-09** | **DRM listed as a phase and a rights attribute**, with no provider or policy given. | Recorded as PD-088. DRM-readiness specified as an integration point only. |
| **A-10** | **"Do not assume a universal catch-up duration"** — instruction given, no default supplied. | Window specified as configurable per channel **and per rights agreement**; default recorded as PD-058. |
| **A-11** | **Advertising described as an optional future model** while also listing FAST channels. | Recorded as PD-006, noting FAST requires channel-origination capability that does not otherwise exist. |
| **A-12** | **Pre-existing repository scaffold** contradicts the stated product, stack, and backend. | Recorded as PD-001; scaffold left untouched; no code copied from it. |

### 13.1 Non-blocking documentation observations

Recorded 2026-08-16. Both are **NON-BLOCKING DOCUMENTATION OBSERVATION**s, deliberately
**not fixed** in the turn that recorded them. Neither affects a decision, a requirement, a
count, or a phase gate. They are logged so they are not rediscovered as if new.

| # | Observation | Status |
|---|---|---|
| **DO-01** | **A-08 is not marked RESOLVED.** The A-08 row above ("no launch platform scope given") still reads as recorded-not-resolved, although **PD-092 was APPROVED on 2026-08-13**, which supplies the launch scope A-08 was raised about. A-02 was marked **RESOLVED** when PD-004 was approved; A-08 was not given the same treatment. | **NON-BLOCKING DOCUMENTATION OBSERVATION** — not fixed |
| **DO-02** | **`DECISION_BRIEF.md` PD-008 supersession sentence omits §4 and §21.** The sentence marking the PD-008 Option-B recommendation as superseded enumerates §5–7 and §8–20, and does not name **§4** or **§21**. Whether those two sections are also superseded is therefore unstated. | **NON-BLOCKING DOCUMENTATION OBSERVATION** — not fixed |

---

## 14. Register maintenance

1. A decision moves from OPEN to ACCEPTED or REJECTED **only** by an explicit product
   owner decision, recorded here with date and rationale.
2. Accepted decisions are mirrored into `PROJECT_STATE.md` §7. Legal decisions are
   mirrored into `docs/legal/DECISION_LOG.md`.
3. Superseded decisions are marked, never deleted. Identifiers are never reused.
4. A phase gate fails if it depends on an OPEN blocking decision (`CLAUDE.md` §22).
5. **No engineering work may silently resolve an OPEN decision.** Encountering one during
   implementation is a signal to stop and ask, not to choose.
