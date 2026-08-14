# DECISIONS.md — KMS TV Product Decision Register

**Phase:** 1 — Product specification
**Status:** OPEN — awaiting product owner decisions
**Version:** 1.0
**Date:** 2026-08-13
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
document contains 95 headings for 93 unique decisions; the totals row counts unique
decisions.

| Category | Count | Blocking | Legal |
|---|---:|---:|---:|
| 1. Product identity and market | 5 | 2 | 2 |
| 2. Business model and commerce | 23 | 6 | 6 |
| 3. Identity and account | 13 | 2 | 2 |
| 4. Profiles and parental control | 5 | 1 | 2 |
| 5. Devices and concurrency | 7 | 3 | 0 |
| 6. Content, EPG and time-shift | 9 | 1 | 2 |
| 7. Rights and compliance | 4 | 3 | 2 |
| 8. Discovery and personalization | 7 | 0 | 1 |
| 9. Admin and roles | 6 | 0 | 2 |
| 10. Privacy and data | 8 | 1 | 8 |
| 11. Platform and technical | 8 | 5 | 0 |
| **Total (unique decisions)** | **93** | **24** | **25** |

**Correction, 2026-08-13.** The blocking and legal totals were first published as 21 and
24, and the per-category rows alongside them were estimates written before the register
was finished. The measured values are **24 blocking** and **25 legal**. The difference is
not attributable to any particular decisions — no decision was added, removed, or
reclassified, and every entry was correctly flagged in its own text and in §12 from the
start. Only the summary row was wrong. Recorded rather than quietly amended, per
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
**Status:** OPEN · Required by Phase 1 · Depends on PD-004.

### PD-003 — Target user segments
The segments in `PRODUCT_SPEC.md` §2.2 are inferences from the product type and language
set. No market research was supplied.
**Why it matters.** Segments drive feature priority and the launch platform order.
**Status:** OPEN · Required by Phase 1.

### PD-004 — Target territories **[BLOCKING — Phase 3] [LEGAL]**
The list of countries in which KMS TV will operate.
**Why it matters.** This is the most consequential open decision in the register.
Territories determine: rights negotiations and geo-enforcement obligations; applicable
privacy law; tax treatment; payment methods; content classification schemes; CDN
footprint; and language priority. **Phases 3 through 6 cannot be correctly designed
without it.**
**[UNVERIFIED]** This specification makes no claim about the regulatory, broadcasting,
tax, or data-protection regime of any country.
**Status:** OPEN · **Required before Phase 3.**

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

### PD-008 — Multi-tenancy **[BLOCKING — Phase 3]**
Whether KMS TV serves one operator or several.
**Why it matters.** **The most expensive decision on this list to defer.** Retrofitting
tenancy touches every table, every query, and every authorization check. Deciding it at
Phase 3 costs a design conversation; deciding it at Phase 20 costs a rewrite.
**RECOMMENDED:** decide explicitly either way and record it — *"single-tenant, and we
accept that multi-tenancy would be a major programme"* is a perfectly good answer, and
far better than silence.
**Status:** OPEN · **Required before Phase 3.**

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
**Status:** OPEN · Depends on PD-004.

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
**Status:** OPEN · Depends on PD-004 · Required by Phase 12.

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
**Status:** OPEN · Depends on PD-004.

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
**Status:** OPEN · Depends on PD-004 · Required by Phase 8.

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
**Status:** OPEN · Depends on PD-004.

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
**Status:** OPEN · Depends on PD-004.

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
**Status:** OPEN · Depends on PD-004.

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
**Status:** OPEN · Depends on PD-004, PD-081.

### PD-079 — Accessibility conformance target **[LEGAL]**
**[UNVERIFIED]** No claim is made about accessibility legislation in any market.
**RECOMMENDED:** adopt a recognized standard as the internal baseline **regardless of
legal obligation** — it is the only way to make "accessible" testable rather than
aspirational.
**Status:** RECOMMENDED, not accepted.

### PD-085 — see §4 · Children's privacy **[LEGAL]**

---

## 11. Platform and technical

### PD-092 — Launch platform scope **[BLOCKING — Phase 3]**
Which of the six platforms ship at launch, and in what order.
**Why it matters.** Determines the client build sequence, certification lead times, device
procurement, and the QA matrix. It also determines whether the **Apple toolchain blocker
(B-004)** is on the critical path — it cannot be resolved by installation and requires
macOS hardware or hosted CI procurement.
**PROPOSED:** Web → Android → Android TV → iOS → Tizen → webOS. Reasoning: Web validates
the API contract fastest; Tizen and webOS share technology with the web client and benefit
from it settling first; iOS carries a procurement dependency that should start early but
need not gate the first release.
**Status:** OPEN.

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
| **Phase 3** (Architecture) | **PD-004** (territories), **PD-008** (multi-tenancy), **PD-092** (launch scope) |
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
| **Phase 19** (Web TV) | **PD-037** (minimum devices), PD-079, D-009 |
| **Phase 26** (Catch-up/Restart) | PD-058, PD-059 |
| **Phase 27** (Payments) | **PD-074**, PD-075, PD-076, PD-053, PD-091, PD-073 |
| **Phase 29** (Analytics) | PD-072, PD-071, PD-084 |
| **Phase 30** (DRM) | **PD-088** |
| **Phase 32** (Load/performance) | **PD-087**, PD-043 |
| **Phase 34** (Disaster recovery) | **PD-089**, **PD-090** |

---

## 13. Unresolved ambiguities in the brief itself

Distinct from decisions — these are places where the STEP 1 brief was internally
ambiguous or incomplete, recorded rather than resolved by assumption.

| # | Ambiguity | Handling in this specification |
|---|---|---|
| **A-01** | **Georgian primary + Russian + Spanish.** Spanish is unusual in this set and unexplained. | Recorded as PD-077. All four treated as required; no market inferred from the set. |
| **A-02** | **No target territory stated**, yet rights, geo-enforcement, privacy, tax, and classification all depend on it. | Recorded as PD-004 and flagged as blocking Phase 3. No territory assumed anywhere. |
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
