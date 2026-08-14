# DECISION_BRIEF.md — KMS TV Blocking Decision Brief

**Prepared for:** Product owner
**Phase:** 1 — Product specification (STEP 1 complete, awaiting decisions)
**Status:** **WAITING FOR PRODUCT OWNER DECISIONS** · **PD-004 and PD-008 APPROVED**
**Version:** 1.2
**Date:** 2026-08-13 (rev. 1.2 — PD-008 approved and recorded)
**Source register:** `docs/product/DECISIONS.md` — IDs and wording taken from it verbatim
**Governing document:** `CLAUDE.md` (binding)

---

## 0. Purpose and standing

This brief exists so that decisions can be made **quickly and on the record**. It analyses;
it does not decide. Every recommendation carries the label
**RECOMMENDATION — NOT APPROVED** and remains a recommendation until you record it.

Nothing in this document is legal, tax, or regulatory advice. Where a decision depends on
law, it is flagged **[LEGAL]** and must be verified by qualified advisors.
**[UNVERIFIED]** marks a third-party fact not read from a primary source, per
`CLAUDE.md` §1.

### 0.1 Current state — 2 approved, 24 blocking decisions open

**Revision 1.2.** **PD-008 is APPROVED — Option A, single-tenant.** One operator, one
product. Multi-tenancy, white-label, and SaaS operator platform are **OUT OF SCOPE**.
Blocking count 25 → **24 open**. **PD-092 is now the only remaining Phase 3 blocker.**

**Revision 1.1.** PD-004 is **APPROVED** (Georgia launch, multi-territory architecture,
future territories configurable). Two consequential decisions arose from it and remain
open: **PD-094** (app distribution scope) and **PD-095** (travelling-subscriber policy).

*Original count correction, retained for the record.* The review request referred to 21
blocking decisions. That figure came from my STEP 1 report, where the register's summary
row was an **estimate** rather than a count; the measured figure at that time was **24**.
The summary row in `DECISIONS.md` was corrected in place rather than quietly amended. No
decision was added, removed, or reclassified by that correction — every entry was
correctly flagged in its own text and in `DECISIONS.md` §12 from the start.

### 0.2 The blocking decisions

**Resolved (2):** ~~`PD-004`~~ · ~~`PD-008`~~ — both **APPROVED · FINAL**, 2026-08-13

**Open (24):**
`PD-001` · `PD-005` · `PD-013` · `PD-014` · `PD-026` · `PD-031` ·
`PD-035` · `PD-037` · `PD-038` · `PD-040` · `PD-044` · `PD-051` · `PD-055` · `PD-056` ·
`PD-074` · `PD-081` · `PD-087` · `PD-088` · `PD-089` · `PD-090` · `PD-092` · `PD-093` ·
**`PD-094`** *(new)* · **`PD-095`** *(new)*

### 0.3 Confidence scale used in this brief

| Level | Meaning |
|---|---|
| **High** | The analysis rests on the specification, `CLAUDE.md`, and verified environment facts. I would defend the recommendation without further information. |
| **Medium** | The analysis is sound but the right answer depends on business information I do not have. The recommendation is a sensible default, not a conclusion. |
| **Low** | The decision is substantially a business or legal judgement. I can frame it; I should not be the one weighing it. |

---

# PART 1 — PRIMARY DECISIONS

---

# PD-004 — TARGET TERRITORIES — ✅ **APPROVED · FINAL**

> ## OUTCOME
>
> | Field | Value |
> |---|---|
> | **Decision** | **APPROVED** |
> | **Decision ID** | **PD-004** |
> | **Launch territory** | **Georgia** |
> | **Architecture** | **Multi-territory** |
> | **Future territories** | **Configurable** |
> | **Status** | **FINAL** |
> | **Approved on** | 2026-08-13 |
>
> **The approved decision goes further than the recommendation below.** The recommendation
> was Option B — a single primary territory with an enumerated secondary set. The approval
> adopts the single-launch-territory element (Georgia) and adds a binding architectural
> requirement the brief did not propose: **app distribution, service availability, and
> content rights are three separate concepts that must not be merged.** That separation is
> now specified in `PRODUCT_SPEC.md` §2.3.1 and is the source of an **eleventh playback
> authorization check** (service availability, distinct from content rights).
>
> Two consequential decisions arise and remain open: **PD-094** (app distribution scope) and
> **PD-095** (travelling-subscriber policy, required before Phase 4).
>
> The analysis below is retained as the record of what was considered. Sections 5–7 are
> superseded by the approval; the consequence analysis in sections 8–20 remains accurate
> and now describes the approved path.

**Register entry:** `DECISIONS.md` §1, *"PD-004 — Target territories — **APPROVED · FINAL**"*

## 1. Exact question that must be decided

**In which territories will KMS TV operate — that is, from which territories may
subscribers lawfully be served, and for which territories does the operator hold, or
intend to acquire, distribution rights?**

The answer must be an explicit, enumerated list of territories, distinguishing the primary
launch territory from any secondary territories, not a general statement of ambition.

## 2. Why this decision matters

It is the **most consequential open decision in the register**, because it is an input to
six independent workstreams that are otherwise unconstrained:

- **Rights** — every rights agreement is territorial. Without a territory list there is no
  way to state what must be negotiated, and no way to validate that what was negotiated is
  sufficient.
- **Geo-enforcement** — `CLAUDE.md` §12 requires server-side territorial enforcement where
  a rights agreement demands it. The enforcement mechanism can be built territory-agnostic,
  but the operational configuration cannot.
- **Privacy law** — which regime applies (PD-081) is downstream of where subscribers are.
- **Tax** — treatment of subscription revenue (PD-054) is jurisdictional.
- **Payment methods** — card penetration, local rails, and wallet preference vary sharply
  by market (PD-075).
- **Content classification** — which maturity/rating scheme applies (PD-035) is
  per-territory, and the data model must carry a scheme identifier as a consequence.

The confirmed language set — Georgian (primary), English, Russian, Spanish — is suggestive
but **is not a market definition**, and the presence of Spanish alongside Georgian and
Russian is unusual enough that inferring a market from it would be a fabrication. That
inference is itself an open question (PD-077).

## 3. Where it affects KMS TV

| Area | Effect |
|---|---|
| Rights model (Phase 4, 13) | Territory is a first-class attribute of every rights agreement |
| Entitlement engine (Phase 14) | Territory is one of the inputs to every decision |
| Playback authorization (Phase 15) | Server-side territory determination on every request |
| Origin & CDN (Phase 17) | Edge footprint, latency, and geo-blocking configuration |
| Payments (Phase 27) | Provider selection, methods, currency, tax |
| Privacy & security (Phase 6) | Applicable regime, data residency, cross-border transfer |
| Catalogue (Phase 4, 10, 25) | Classification scheme, per-territory availability |
| i18n (Phase 29) | Language priority and fallback chain |
| Admin (Phase 18) | Territory selection UI in rights management |

## 4. All realistic options

| Option | Description |
|---|---|
| **A** | **Single territory.** One named country. Simplest legal, tax, and payment surface. |
| **B** | **Single primary + enumerated secondary territories.** One launch market, with a short explicit list of additional territories the model must accommodate (possibly empty at launch). |
| **C** | **Multi-territory from launch.** Three or more markets served concurrently. |
| **D** | **Defer — build territory-aware, decide scope later.** Architecture carries territory everywhere; operational scope named later. |

**Option D deserves care.** The architecture **must** be territory-aware regardless of
which option is chosen — territoriality is inherent to rights, not a feature. So D is not
"no architectural decision", it is "no *commercial* decision", and it leaves rights
negotiation, tax, payments, privacy, and classification all unresolvable.

## 5. Recommended option

### ~~RECOMMENDATION — NOT APPROVED~~ → **SUPERSEDED BY APPROVAL**

~~**Option B — a single named primary launch territory, with an explicitly enumerated
secondary set that may be empty at launch.**~~

**Approved:** Georgia as the single launch territory, multi-territory architecture, future
territories configurable, with the three-concept separation as a binding constraint.

The model is built territory-aware unconditionally (which is not optional), while the
operational and commercial surface is kept to one market. Adding a territory later becomes
**data plus rights negotiation**, not redesign.

## 6. Advantages of the recommended option

- Smallest legal, tax, privacy, and payment surface at launch — one regime to get right
  rather than several to get approximately right
- Rights negotiation has a concrete, defensible scope
- Territory-aware data model means expansion is configuration, not migration
- Classification scheme (PD-035) resolves to one scheme initially, while the model still
  supports several
- The enumerated secondary list forces the diaspora question (PD-002) to be answered
  explicitly rather than assumed

## 7. Disadvantages

- Commits to a market before market validation, if none has been done
- If the true intent is genuinely multi-market at launch, B under-scopes the commercial and
  legal preparation and will feel like a delay later
- A single-territory launch may make some rights negotiations *less* attractive to
  rights holders than a multi-territory commitment would
- Does not by itself answer PD-002 (diaspora), which remains a separate [LEGAL] question

## 8. Technical consequences

Territory becomes an evaluated input in the authorization path, alongside entitlement and
rights. Territory determination must be **server-side and authoritative**; client-reported
location is never trusted (`CLAUDE.md` §12). The determination mechanism itself is
**[UNVERIFIED]** — no geo-determination method, accuracy, or provider is assumed anywhere
in the specification, and one must be selected and verified in Phase 6. **Post-approval:**
the determination now feeds two independent checks — service availability and content
rights — which must not share a single conflated result.

## 9. Database consequences

- Territory is an attribute of rights agreements, and territories form a reference set
- Assets relate to territories **through rights**, never directly — this keeps the single
  source of truth intact
- Package eligibility may be territory-scoped
- Pricing carries currency per territory if pricing varies (PD-014)
- Under Option B the schema is identical to Option C; only the reference data differs.
  **Choosing A instead does not simplify the schema** — it only reduces the data — which is
  why the recommendation costs nothing to reverse upward

## 10. API consequences

- Playback authorization responses include a territory-related denial reason
  (`TERRITORY_RESTRICTED`, already specified)
- Catalogue and channel-list endpoints are territory-filtered server-side
- Package listing is territory-filtered by eligibility
- **No endpoint accepts a client-supplied territory as authoritative.** A client may report
  a hint; the server ignores it for decisions
- No API shape change between options — only the data returned differs

## 11. Backend consequences

- The entitlement engine (Phase 14) evaluates territory as one of its inputs
- A territory-determination service sits behind an abstraction, per `CLAUDE.md` §4.2,
  validated against two candidate approaches before finalization
- Denials are logged as compliance evidence with the determined territory recorded
- Under Option A or B this is one code path with one configured territory; under C it is
  the same code path with more reference data

## 12. Streaming consequences

- Where a rights agreement requires geo-enforcement, it must be enforced **both** at
  authorization **and** at the edge (`CLAUDE.md` §12) — the edge cannot be trusted to
  inherit an authorization-time decision indefinitely
- CDN configuration carries geo-restriction rules per asset class
- CDN footprint and PoP selection follow the territory list; a single territory permits a
  much smaller and cheaper footprint
- Signed URL policy may need territory binding depending on the CDN's capabilities
  **[UNVERIFIED — PD-093]**

## 13. Admin-panel consequences

- Rights management requires territory selection when creating and editing agreements
- The rights expiration view must show territorial scope, since partial renewal by
  territory is a normal commercial outcome
- Playback session investigation (UF-25) displays the determined territory for each
  decision
- Under Option A the selector is trivially small; the UI is otherwise identical

## 14. Web / mobile / TV consequences

- Clients display neutral territory-denial messaging and **offer no workaround and no hint
  of one** (`CLAUDE.md` §1, already an invariant in every relevant flow)
- Language priority and default follow the territory
- Currency display follows the territory
- App store availability and metadata are configured per territory
- **No client contains territory logic.** Clients render outcomes; this decision does not
  change client architecture at all

## 15. Security consequences

- Territory determination becomes a **security-relevant control**, not a convenience — a
  weak determination is a rights-compliance failure
- Attempts to influence the determination via client input must be tested negatively
  (already specified: `AC-FR-AUT-03-1`)
- Fewer territories means a smaller attack surface for territorial evasion and simpler
  negative testing
- **No evasion countermeasure may itself become an evasion tool** — the platform must never
  contain a mechanism for defeating territorial controls (`CLAUDE.md` §2.2)

## 16. Operational consequences

- Support must be able to explain territorial denials without offering circumvention
- Compliance reporting is per rights holder and per territory
- Incident and status communications are per market
- Business hours, language coverage, and escalation paths follow the territory list

## 17. Scalability consequences

Minimal direct effect. Territory count influences CDN footprint and reference-data size,
neither of which is a scaling constraint at any plausible territory count. The genuine
scaling constraint remains concurrent sessions at live-event start (`CLAUDE.md` §20).

## 18. Cost implications

| Cost area | Option A/B | Option C |
|---|---|---|
| Legal & regulatory review | One regime | One per regime, and they interact |
| Rights negotiation | One territorial scope | Multiple, often with different holders |
| Payment integration | One market's methods | Several, likely several providers |
| CDN footprint | Smaller, cheaper | Wider, more PoPs |
| Tax compliance | One regime | Several, with cross-border complexity |
| Translation & QA | Driven by languages, not territories | Same |

**No cost figures are given.** None have been supplied and inventing them would violate
`CLAUDE.md` §1.

## 19. Migration difficulty if changed later

**Adding territories later: LOW.** The model is territory-aware from the start, so adding
one is reference data plus a rights negotiation plus a payment/tax/legal workstream. The
non-engineering work dominates.

**Removing a territory later: LOW technically, HIGH commercially** — it means withdrawing
service from paying subscribers.

**Changing from "no territory model" to a territory model later: SEVERE** — but that
scenario cannot arise here, because the territory model is mandatory regardless of this
decision. This is precisely why the recommendation is safe.

## 20. What happens if we postpone the decision

Phase 3 (architecture) can technically proceed, because the architecture is territory-aware
either way. But **five other decisions are downstream and all stall**: PD-081 (privacy
regime), PD-035 (classification scheme), PD-014 (pricing and currency), PD-075 (payment
methods), PD-054 (tax). PD-035 in particular blocks Phase 4, so postponing PD-004 postpones
the database design by transitivity.

Rights negotiation — which has the longest external lead time of anything in the programme
— cannot begin at all.

## 21. Confidence level

**Medium.** The technical analysis is high-confidence; the *choice* depends on business and
market intent I do not have, and I have deliberately not inferred a market from the
language set.

## 22. Can the decision safely be changed later?

**Yes, upward.** Adding territories is low-difficulty because the model is territory-aware
regardless. Withdrawing from a territory is technically easy and commercially painful.
**The unsafe path is not choosing**, because five downstream decisions and all rights
negotiation stall behind it.

---

# PD-008 — MULTI-TENANCY — ✅ **APPROVED · FINAL**

> ## OUTCOME
>
> | Field | Value |
> |---|---|
> | **Decision ID** | **PD-008** |
> | **Decision** | **Option A — Single-tenant KMS TV** |
> | **Status** | **APPROVED · FINAL** |
> | **Operator model** | **One operator** |
> | **Multi-tenancy** | **OUT OF SCOPE** |
> | **White-label** | **OUT OF SCOPE** |
> | **SaaS operator platform** | **OUT OF SCOPE** |
> | **Future multi-tenancy** | Possible future architectural decision, not part of current implementation |
> | **Approved on** | 2026-08-13 |
>
> **The approval is stricter than the recommendation below.** The recommendation was
> **Option B** — single-tenant code with deployment-per-operator, which contemplated a
> second deployment should a second operator appear. The approval selects **Option A**:
> one operator, full stop. It additionally rules out *"tenant-specific deployments managed
> by a shared control plane"* — which is the shape Option B would have grown into.
> Option A is therefore narrower than Option B, not a variant of it.
>
> **This resolves the rewrite-class risk identified in §19 and §22 below.** Those sections
> warned that B → C after Phase 4 is a rewrite. Under Option A that path is closed by
> decision: any future multi-tenancy is a **new architectural decision with its own ADR**,
> not a migration of this one.
>
> **TENANCY ≠ TERRITORY.** PD-008 (single-tenant) and PD-004 (multi-territory) are
> independent and both approved. One operator serving several territories is exactly the
> approved model and requires no tenancy concept at all.
>
> Sections 5–7 are superseded by the approval. The consequence analysis in 8–20 is retained
> as the record of what was considered; its **Option A/B columns now describe the approved
> path**, and its Option C/D columns describe paths that are out of scope.

**Register entry:** `DECISIONS.md` §2, *"PD-008 — Multi-tenancy — **APPROVED · FINAL**"*

## 1. Exact question that must be decided

**Will KMS TV serve exactly one operator, or must it serve multiple independent operators —
each with isolated subscribers, catalogue, rights, packages, branding, and reporting —
and if so, does that isolation come from separate deployments or from tenancy within one
deployment?**

## 2. Why this decision matters

It is, as recorded in the register, *"the most expensive decision on this list to defer."*

Tenancy is not a feature that can be added to a working system. It is a property of every
table, every query, every cache key, and every authorization check. Retrofitting it means
touching all of them, and the failure mode of an incomplete retrofit is **cross-tenant data
disclosure** — one operator seeing another's subscribers, catalogue, or rights.

A half-built tenant discriminator that is enforced in most places is materially **worse
than no tenancy at all**, because it creates the appearance of isolation without the
substance.

## 3. Where it affects KMS TV

Everywhere. Specifically: every entity in the Phase 4 data model; every query; every
authorization check (Phase 8, 9, 14, 15); cache key construction; the admin surface
(Phase 18); analytics segregation (Phase 29); backup and restore granularity (Phase 34);
and per-operator branding in every client (Phases 19–24).

## 4. All realistic options

| Option | Description |
|---|---|
| **A** | **Single-tenant.** One operator, one deployment, no tenancy concept anywhere. |
| **B** | **Single-tenant code, deployment per operator.** No tenant discriminator in the data model; isolation is physical. A second operator gets a second deployment. |
| **C** | **Multi-tenant, shared schema.** A tenant discriminator on every tenant-scoped entity, enforced centrally and tested for leakage. |
| **D** | **Multi-tenant, schema- or database-per-tenant.** One application, isolated storage per tenant. |

## 5. Recommended option

### ~~RECOMMENDATION — NOT APPROVED~~ → **SUPERSEDED BY APPROVAL**

~~**Option B — single-tenant code with deployment-per-operator — *unless* the business has a
concrete plan to sell KMS TV wholesale or white-label, in which case Option C must be
chosen now, not later.**~~

**Approved: Option A — single-tenant KMS TV. One operator, one product.** The conditional
in the recommendation is resolved: there is no wholesale or white-label plan, and both are
now explicitly out of scope.

This recommendation is conditional on purpose, and the condition is the whole point. The
decision is not really "how do we build it" but "what business are we in". If wholesale is
a real revenue line, C; if not, B.

## 6. Advantages of the recommended option

- Simplest possible data model, queries, and authorization — no discriminator to forget
- **Strongest possible isolation.** Physical separation cannot be defeated by a missing
  `WHERE` clause
- Fastest to build and easiest to reason about, which matters across a 38-phase programme
- A credible expansion story exists — a second operator gets a second deployment — without
  paying tenancy's cost up front
- Backup, restore, and incident blast radius are naturally per-operator
- Per-operator customization (branding, configuration) is straightforward

## 7. Disadvantages

- Infrastructure cost scales linearly with operator count — poor economics beyond a handful
- Operational overhead multiplies: deployments, migrations, monitoring, and on-call per
  instance
- Cross-operator reporting is not possible without a separate aggregation layer
- If the operator count grows unexpectedly, migrating from B to C is a **major** programme
- Per-operator configuration drift becomes a real operational risk over time

## 8. Technical consequences

- **Option B:** no tenancy concept in code. Configuration is per deployment. Deployment
  automation must be genuinely reproducible from version control (already required by
  `CLAUDE.md` §17), because it is now the tenancy mechanism.
- **Option C:** a tenant context must be established at the boundary of every request and
  every queue consumer, and enforced centrally rather than by convention. Every cache key
  is tenant-scoped. Cross-tenant access must be **impossible by construction**, not merely
  absent by discipline.

## 9. Database consequences

- **Option B:** the Phase 4 schema is exactly as specified today. No change.
- **Option C:** a tenant identifier on every tenant-scoped table, in every unique
  constraint and index, and in every foreign key path. Row-level enforcement is required.
  Reference data must be classified as global or tenant-scoped, entity by entity.
- **Option D:** schema management multiplies — migrations run per tenant, with partial
  failure as a real operational state.

**The database is where deferral hurts most.** Choosing C after Phase 4 means rewriting
the schema and every migration written up to that point.

## 10. API consequences

- **Option B:** unchanged. The deployment is the tenant.
- **Option C:** every request resolves a tenant, from host, path, or token claim.
  Tenant is **never** accepted as a client-supplied parameter — that would be a trivial
  cross-tenant escalation. The OpenAPI document must state tenant resolution for every
  endpoint.

## 11. Backend consequences

- **Option B:** modules stay as specified; no tenancy plumbing anywhere.
- **Option C:** every module gains a tenant dimension. Queue jobs carry tenant context.
  Scheduled jobs — notably the rights-expiry job (§15.4 of the spec) — must iterate tenants
  correctly, and a tenant missed by that job is a **compliance failure**, not a bug.

## 12. Streaming consequences

- **Option B:** origin, packaging, and CDN configuration are per deployment. Clean.
- **Option C:** stream sources, storage paths, and signed URL scopes must be tenant-scoped,
  and **a tenant's content must not be reachable using another tenant's signed URL**.
- Under either option the invariants hold unchanged: origin unreachable from the public
  internet; segments never proxied through the application tier.

## 13. Admin-panel consequences

- **Option B:** one operator's staff, one set of roles. As specified today.
- **Option C:** roles become tenant-scoped, and a new cross-tenant super-administrator
  concept appears — which is a **significant new privilege-escalation surface** requiring
  its own threat modelling and negative tests.

## 14. Web / mobile / TV consequences

- **Option B:** one branded app per operator, if a second ever exists — which is
  significant, because each app store listing is a separate submission, review, and
  certification cycle. This is the least visible cost of Option B and can be the largest.
- **Option C:** one app that resolves tenant by configuration or sign-in, with dynamic
  branding — more complex, but a single store listing per platform.

**This is the sharpest practical trade-off between B and C**, because store submissions are
external processes with their own lead times (see also PD-092 and blocker B-006).

## 15. Security consequences

- **Option B:** isolation is physical and therefore near-absolute. Cross-tenant leakage is
  not possible because there is no shared store.
- **Option C:** cross-tenant isolation becomes a **primary security control** requiring
  explicit negative testing on every endpoint — the Phase 33 security audit scope grows
  materially, and every new endpoint thereafter inherits the obligation.
- **The worst outcome is neither B nor C, but a partial C**: a discriminator present on
  most tables and enforced by convention. `CLAUDE.md` §6.1's deny-by-default principle
  applies here with particular force.

## 16. Operational consequences

- **Option B:** N deployments to monitor, migrate, patch, and be on call for. Runbooks must
  be tenant-parameterized. Rollout becomes a fleet operation.
- **Option C:** one deployment, but every incident is potentially every operator's incident,
  and blast radius is total.

Neither is obviously easier — B multiplies routine work, C concentrates risk.

## 17. Scalability consequences

- **Option B:** scales per operator independently, which is genuinely useful when operators
  differ greatly in size. Noisy-neighbour problems cannot occur.
- **Option C:** shared capacity is more efficient, but one operator's live-event spike
  becomes everyone's problem unless isolation is explicitly engineered.

## 18. Cost implications

- **Option B:** infrastructure cost multiplies by operator count; engineering cost lowest;
  operational cost grows linearly.
- **Option C:** infrastructure cost shared; engineering cost materially higher up front;
  security audit cost higher permanently.

The crossover point depends on operator count and size, neither of which is known. **No
figures are given** — none have been supplied.

## 19. Migration difficulty if changed later

| Transition | Difficulty |
|---|---|
| B → C (after Phase 4) | **SEVERE.** Every table, query, cache key, and authorization check. Effectively a rewrite of the data layer with a live migration of production data. |
| B → C (before Phase 4) | Moderate — it is a design decision, not a migration |
| C → B | Low technically; wasteful, having paid tenancy's cost for nothing |
| A → B | Trivial — B is A plus deployment automation |

**This asymmetry is the entire argument for deciding now.** The cost of deciding is one
conversation. The cost of deferring past Phase 4 is a rewrite.

## 20. What happens if we postpone the decision

Phase 3 cannot produce a correct architecture, and Phase 4 cannot produce a correct schema.
Work can proceed on the *assumption* of single-tenant — which is what deferral means in
practice — and if that assumption is later wrong, everything built in Phases 4 through 18
requires rework.

**Postponement is not neutral here. It is a silent choice of Option A**, taken without a
record, which is exactly what `CLAUDE.md` §1 and the STEP 1 brief prohibit.

## 21. Confidence level

**Medium-High** on the analysis and on the asymmetry. **Low** on which option is right,
because it depends entirely on commercial intent that has not been stated. Either answer is
defensible; silence is not.

## 22. Can the decision safely be changed later?

**No — this is the least safely reversible decision in the register.** B → C after Phase 4
is a rewrite of the data layer. It is the one decision where I would recommend recording an
answer even if the answer is provisional: *"single-tenant, and we accept that multi-tenancy
would be a major programme"* is a complete, useful decision. Silence is not.

---

# PD-092 — LAUNCH PLATFORM SCOPE

**Register entry:** `DECISIONS.md` §11, *"PD-092 — Launch platform scope **[BLOCKING — Phase 3]**"*

## 1. Exact question that must be decided

**Which of the six named client platforms — Web, Android, Android TV, iOS/iPadOS, Samsung
Tizen, LG webOS — ship in the first production release, and in what order are the remainder
built?**

## 2. Why this decision matters

- It sets the **critical path**. Two of the six carry external, non-engineering lead times:
  Samsung and LG developer registration and store certification (blocker **B-006**), and
  the Apple toolchain, which **cannot be installed** in the current environment and requires
  macOS hardware or hosted CI procurement (blocker **B-004**).
- It sizes the **QA matrix**, which is the dominant cost in client work — six platforms
  across four languages and multiple device generations.
- It determines **device procurement**, since `CLAUDE.md` §19 requires validation on the
  *minimum* supported device, not a development machine.
- It interacts with **PD-008**: under deployment-per-operator, each platform means a
  separate store listing per operator.

## 3. Where it affects KMS TV

Phases 19–24 directly. Phase 3 (architecture) needs it to sequence client work and to
decide the web framework (D-009). Phase 5 (API) benefits from knowing which client
validates the contract first. Phase 32 (load testing) needs the device matrix. Phase 33
(security audit) scope includes each shipped client.

## 4. All realistic options

| Option | Description |
|---|---|
| **A** | **Web only at launch.** Smallest scope; validates the API contract; no store dependency. |
| **B** | **Web + Android + Android TV.** One browser client and the Android family, which share a toolchain. |
| **C** | **Web + one TV platform.** Delivers the primary living-room use case with minimum client count. |
| **D** | **All six at launch.** Maximum reach, maximum concurrent risk. |
| **E** | **TV-first** — Android TV + Tizen + webOS, with Web and mobile following. |

## 5. Recommended option

### **RECOMMENDATION — NOT APPROVED**

**Option B — Web + Android + Android TV at launch, then iOS, then Samsung Tizen, then
LG webOS.**

With one qualification that matters as much as the order itself: **the non-engineering
prerequisites for iOS, Tizen, and webOS should start during Phase 3**, roughly seventeen
phases before those clients are built. Procurement and registration are calendar time, not
engineering time, and they are the only things in this programme that cannot be compressed
by working harder.

## 6. Advantages of the recommended option

- **Web validates the API contract fastest** and is the cheapest surface to iterate on
- **Android and Android TV share a toolchain** — Kotlin, Gradle, Media3 — so Android TV is
  the cheapest second client available. Both are already supported by the current
  environment: JDK 21 and Gradle 8.14.3 are installed and only the SDK is missing (B-005)
- **A TV client ships at launch.** This is a television product; a launch without a
  living-room client omits the primary use case (UC-01)
- Avoids both hard external blockers at launch — no Apple toolchain (B-004), no Samsung or
  LG certification (B-006)
- Tizen and webOS share technology with the web client, so they benefit from it settling
  first rather than being built in parallel with it
- Keeps the launch QA matrix to three platforms

## 7. Disadvantages

- **No iOS at launch.** In markets with high iOS share this is a material reach gap, and I
  have no market share data — this is exactly the kind of fact I will not invent
- Samsung and LG are the two largest Smart TV platforms by installed base **[UNVERIFIED]** —
  I have not confirmed this from a primary source, and it should be verified before relying
  on it. Deferring them may defer a large share of the living-room audience
- Three platforms at launch is still a substantial QA and release surface
- Android TV and Google TV device performance varies widely; the minimum-device constraint
  (PD-037) bites here first

## 8. Technical consequences

- The web framework decision (D-009) becomes urgent, and should be driven by **TV browser
  constraints** rather than desktop convenience, since Tizen and webOS clients later share
  that technology
- The Android and Android TV clients share domain logic, networking, and player integration
- The API contract is exercised by a browser client first, which surfaces contract problems
  early and cheaply
- **No client contains entitlement logic** under any option — this decision does not change
  client architecture, only sequence

## 9. Database consequences

**None.** Device class is already a first-class attribute in the specification (§6.1) and
is rights-relevant regardless of which platforms ship. The reference set of device classes
is the same whether or not a given client exists yet.

## 10. API consequences

**Minimal.** The API is specified to serve all six platforms. Sequencing determines which
client validates the contract first, not what the contract is. A risk worth naming: a
contract validated only against a browser client may under-serve TV clients' constraints —
which is an argument for building Android TV early, and is part of why Option B includes it.

## 11. Backend consequences

**None structural.** Device-class handling, rights enforcement per device class, and
per-platform QoE telemetry are all specified independently of launch scope.

## 12. Streaming consequences

- Transcoding ladders must cover the shipped device classes; fewer platforms means a
  narrower initial ladder and lower encoding and storage cost
- **[UNVERIFIED]** Per-platform codec and container support has not been confirmed from
  official documentation for any platform, and must be before Phase 16
- DRM platform coverage (PD-088) is scoped by which clients exist

## 13. Admin-panel consequences

**Minimal.** Device-class visibility rules and per-platform QoE dashboards exist regardless;
fewer platforms simply means fewer populated rows.

## 14. Web / mobile / TV consequences

This decision *is* the client consequence. Under Option B:

- **Web** — full functionality including account management, purchase, and profile
  administration, since it is the only launch surface where typing is comfortable
- **Android** — full mobile functionality
- **Android TV** — 10-foot UI, D-pad-only navigation, second-screen sign-in
  **[PROPOSED]**, and the tightest performance constraints in the programme
- **iOS, Tizen, webOS** — not at launch; their prerequisites nonetheless begin in Phase 3

## 15. Security consequences

- Each shipped client is in scope for the Phase 33 penetration test; fewer clients means a
  smaller, deeper audit rather than a broader, shallower one
- Token storage strategy must be settled per platform; three platforms at launch means
  three storage models to get right instead of six
- **[UNVERIFIED]** Per-platform secure storage and certificate pinning capabilities have
  not been confirmed and must be, in each client phase

## 16. Operational consequences

- Release cadence is per platform; store review adds latency outside the team's control
- Support must handle only shipped platforms at launch
- Incident diagnosis spans three client telemetry streams rather than six
- **Deferred platforms still need their calendar-time prerequisites started early** — this
  is the operational point the recommendation exists to protect

## 17. Scalability consequences

Launch platform count influences peak concurrency modestly, but the binding constraint
remains the live-event concurrency spike (`CLAUDE.md` §20), which is driven by audience
size, not client count.

## 18. Cost implications

- Each platform is a full client project: build, test, certify, release, maintain
- TV platforms additionally require **physical device procurement across model years**,
  because emulator results are explicitly insufficient for Phases 23–24
- iOS requires macOS hardware or hosted CI — a procurement cost that exists whenever iOS
  ships, and only the timing is in question
- Deferring a platform defers its build cost but **not** its registration lead time, which
  is why the recommendation starts those early

**No figures are given.** None have been supplied.

## 19. Migration difficulty if changed later

**LOW.** Adding a platform later is additive — a new client against an unchanged API. There
is no migration in the usual sense.

The two real risks are not technical: an API contract validated only against a narrow set
of clients may need revision when a very different client arrives (mitigated by including a
TV client at launch), and **certification lead time cannot be recovered** once it has been
allowed to become the critical path.

## 20. What happens if we postpone the decision

Phase 3 cannot sequence client work or settle the web framework (D-009). More seriously,
**the external lead times keep running while the decision does not** — Samsung and LG
registration, and macOS procurement, all take calendar time that postponement consumes
without producing anything.

Deferral here does not preserve optionality. It silently spends it.

## 21. Confidence level

**High** on sequencing logic, toolchain sharing, and the lead-time argument. **Medium** on
the specific launch set, because platform market share in the target territory is unknown —
and the territory itself is PD-004, which is also open. These two decisions inform each
other and are best taken together.

## 22. Can the decision safely be changed later?

**Yes.** This is the most reversible of the three primary decisions. Platforms can be added
or reordered at low technical cost. The caveat is procurement and certification lead time,
which is why the recommendation is to decide the *order* now and start the long-lead
prerequisites immediately, even for platforms scheduled late.

---

# PART 2 — ALL REMAINING BLOCKING DECISIONS

The 21 blocking decisions other than PD-004, PD-008, and PD-092. Wording is taken from
`DECISIONS.md`.

---

### PD-001 — Product identity and the pre-existing scaffold **[BLOCKING — Phase 1]**
- **Exact question.** Does KMS TV supersede the pre-existing repository identity, and is the
  orphaned Vite/React/Supabase scaffold deleted, archived, or partly retained?
- **Why it matters.** Two contradictory product definitions in one repository guarantee
  contradictory work. The scaffold is non-buildable (no `src/`), targets Vite rather than
  Next.js, declares a Supabase dependency contradicting Laravel 12 + PostgreSQL, and is
  titled for a different product entirely.
- **Options.** (a) Delete it, archiving on a branch; (b) delete outright; (c) retain part of
  it — which then requires the full `CLAUDE.md` §3 license intake, since its provenance is
  unassessed (L-007).
- **RECOMMENDATION — NOT APPROVED:** (a). Preserves history at no cost and removes the
  contradiction.
- **Blocks.** Phase 7 — code should not land beside it. Also tracked as `PROJECT_STATE.md`
  D-006 and `DECISION_LOG.md` L-007.

### PD-005 — Free tier **[BLOCKING — Phase 12] [LEGAL]**
- **Exact question.** Whether a free tier exists, and what it includes.
- **Why it matters.** Requires an entitlement path for unpaid accounts **and** a rights basis
  permitting free distribution — a **separate grant** from paid distribution. Also changes
  capacity planning, since free users consume delivery without revenue.
- **Options.** No free tier · limited free channel set · time-limited free access ·
  ad-supported free tier (couples to PD-006).
- **RECOMMENDATION — NOT APPROVED:** none offered. This is a commercial and rights judgement
  with no engineering-preferred answer, and the rights basis question is [LEGAL].
- **Blocks.** Phase 12. Couples to PD-009 (anonymous playback) and PD-006 (advertising).

### PD-013 — Package catalogue **[BLOCKING — Phase 12]**
- **Exact question.** Names, contents, allowances, and eligibility per package.
- **Why it matters.** The names *Free, Basic, Standard, Premium, Sports, Movies* appear in
  the brief as explicitly unapproved examples, so the specification uses no package names
  anywhere. Packages are the bridge between commerce and rights, and the entitlement model
  cannot be finalized without them.
- **Options.** Single package · simple tiers · tiers plus add-ons (couples to PD-049).
- **RECOMMENDATION — NOT APPROVED:** none on names or contents. On *structure*, resolve
  PD-049 before Phase 4 so the data model is built once.
- **Blocks.** Phase 12; structurally influences Phase 4.

### PD-014 — Pricing and currency **[BLOCKING — Phase 12] [LEGAL]**
- **Exact question.** Prices, currencies, and whether pricing varies by territory.
- **Why it matters.** Money is never floating-point and always carries a currency
  (`CLAUDE.md` §9); per-territory pricing changes the data model and the tax surface.
- **Options.** Single price and currency · per-territory pricing · per-territory currency
  with unified pricing.
- **RECOMMENDATION — NOT APPROVED:** none. Commercial and [LEGAL].
- **Blocks.** Phase 12. Depends on PD-004.

### PD-026 — Token revocation interval **[BLOCKING — Phase 8]**
- **Exact question.** What is the bounded, documented maximum interval between revoking a
  session or device and playback actually stopping?
- **Why it matters.** `CLAUDE.md` §7 requires a bounded interval — *"eventually" is not an
  interval*. This number is the difference between "the device was removed" and "the device
  was removed and can no longer watch."
- **Options.** A stated maximum in seconds. Shorter means more edge validation traffic;
  longer means a wider window in which a revoked device keeps playing.
- **RECOMMENDATION — NOT APPROVED:** the specification's **PROPOSED — REQUIRES VALIDATION**
  value of ≤ 60 seconds. It must be **measured** in Phase 15, not asserted.
- **Blocks.** Phase 8; verified in Phase 15.

### PD-031 — Deletion versus retention **[BLOCKING — Phase 9] [LEGAL]**
- **Exact question.** How is the conflict resolved between a user's deletion request and
  retention obligations for audit records, rights-compliance evidence, and financial records?
- **Why it matters.** A real, unavoidable tension. The product commitment is that retained
  records should not identify the person where that is achievable — but what is achievable,
  and what is required, is a legal determination, not an engineering one.
- **Options.** Anonymize and retain · delete with documented exceptions · tiered retention
  by record class.
- **RECOMMENDATION — NOT APPROVED:** none. Squarely [LEGAL], and depends on PD-081.
- **Blocks.** Phase 9; shapes the Phase 4 retention design.

### PD-035 — Content rating / maturity scheme **[BLOCKING — Phase 4] [LEGAL]**
- **Exact question.** Which classification scheme applies, per territory?
- **Why it matters.** Maturity limits gate entitlement on every browse, search, and
  recommendation surface. The data model must carry a **scheme identifier alongside the
  rating value** so multiple schemes can coexist across territories.
  **[UNVERIFIED]** No claim is made about which classification body or scheme applies in any
  market, and none has been assumed anywhere.
- **Options.** A single territory-appropriate scheme · multiple schemes with a mapping ·
  an internal scheme mapped to external ones.
- **RECOMMENDATION — NOT APPROVED:** on the scheme itself, none — it is [LEGAL] and
  territory-dependent. On the **model**, carry scheme identifier plus value regardless of
  which scheme is chosen, because that costs nothing now and is expensive to retrofit.
- **Blocks.** Phase 4. Depends on PD-004.

### PD-037 — Minimum supported OS versions and model years **[BLOCKING — Phase 19]**
- **Exact question.** Per platform, what is the minimum supported OS version and TV model
  year?
- **Why it matters.** `CLAUDE.md` §19 makes the **lowest** supported device the binding
  performance constraint. Every client performance target is meaningless until this is set,
  and TV platforms are where it bites hardest.
- **Options.** Aggressive (recent only, best performance, smallest reach) · moderate ·
  conservative (wide reach, hardest performance targets).
- **RECOMMENDATION — NOT APPROVED:** none on the values. Note that this decision and PD-087
  (performance targets) are **one decision in two parts** — a target without a device is
  meaningless, and a device without a target is untestable.
- **Blocks.** Phase 19 and every client phase; device procurement.

### PD-038 — Maximum registered devices per account **[BLOCKING — Phase 9]**
- **Exact question.** How many devices may an account register, and does the limit vary by
  package?
- **Why it matters.** Device limits are a primary anti-sharing control and a primary source
  of legitimate-user friction.
- **Options.** A number, uniform or per package.
- **RECOMMENDATION — NOT APPROVED:** **deliberately none.** The STEP 1 brief explicitly
  instructs that device limits must not be invented, and I have not proposed one anywhere.
- **Blocks.** Phase 9.

### PD-040 — Maximum concurrent streams **[BLOCKING — Phase 15]**
- **Exact question.** How many simultaneous streams may an account play, and does the limit
  vary by package?
- **Why it matters.** Note that a **rights-agreement concurrency cap may be lower than the
  package cap; the most restrictive applies** — so this decision sets a ceiling, not the
  effective limit.
- **Options.** A number, uniform or per package.
- **RECOMMENDATION — NOT APPROVED:** **deliberately none**, same reason as PD-038.
- **Blocks.** Phase 15.

### PD-044 — EPG retention and future horizon **[BLOCKING — Phase 11]**
- **Exact question.** How far back and how far forward does the guide extend?
- **Why it matters.** Drives storage volume, ingest cost, catch-up availability, and EPG
  query performance.
- **Options.** A backward window and a forward window, in days. The backward window
  interacts directly with catch-up (PD-058).
- **RECOMMENDATION — NOT APPROVED:** none on the values. Recommend deciding it **together
  with PD-058**, since a catch-up window longer than EPG retention is incoherent.
- **Blocks.** Phase 11.

### PD-051 — Grace behaviour during `past_due` **[BLOCKING — Phase 12]**
- **Exact question.** Whether playback continues while payment retries run, and for how long.
- **Why it matters.** A genuine tension: cutting access immediately on a failed card harms
  customers whose card merely expired; leaving it open indefinitely gives away the product.
  This is a commercial judgement, not an engineering default.
- **Options.** No grace · fixed grace period · grace with reduced entitlement.
- **RECOMMENDATION — NOT APPROVED:** none. Commercial judgement; the flows deliberately do
  not assume an answer.
- **Blocks.** Phase 12.

### PD-055 — Rights expiry warning lead time **[BLOCKING — Phase 13]**
- **Exact question.** How far ahead are operators warned that a rights window is closing?
- **Why it matters.** Too short and there is no time to renegotiate; too long and warnings
  become noise that gets ignored — which is the same as having none. `CLAUDE.md` §14
  mandates a "rights expiring within 24 hours" metric regardless of this value.
- **Options.** A lead time, possibly tiered (e.g. an early notice plus an urgent one).
- **RECOMMENDATION — NOT APPROVED:** on the value, none. On the **shape**, a tiered warning
  is more useful than a single threshold, because renegotiation and takedown have very
  different lead times.
- **Blocks.** Phase 13.

### PD-056 — In-progress viewing at the moment of rights expiry **[BLOCKING — Phase 15] [LEGAL]**
- **Exact question.** When a rights window closes while a viewer is watching: terminate
  immediately, or allow the current programme or title to complete?
- **Why it matters.** Different compliance profiles and different viewer costs. **The answer
  may be dictated by contract terms rather than chosen.**
- **Options.** Terminate immediately · allow the current item to complete · contract-dependent
  per agreement.
- **RECOMMENDATION — NOT APPROVED:** none on the policy. On the **model**, support
  per-agreement configuration, because contracts will differ and a single global policy will
  eventually conflict with one of them.
- **Blocks.** Phase 15. Referenced by UF-10, UF-11, UF-19, EC-13, EC-30, all of which
  explicitly decline to assume an answer.

### PD-074 — Payment providers **[BLOCKING — Phase 27]**
- **Exact question.** Which payment provider or providers will KMS TV integrate?
- **Why it matters.** **[UNVERIFIED]** No provider's capabilities, fees, supported methods,
  webhook semantics, or regional availability may be assumed until read from that provider's
  official documentation (`CLAUDE.md` §1). `CLAUDE.md` §4.2 requires the abstraction to be
  validated against **two** real candidates before it is finalized.
- **Options.** A named primary provider, plus at least one named candidate for abstraction
  validation.
- **RECOMMENDATION — NOT APPROVED:** none on the provider. Recommend naming **two
  candidates during Phase 3** purely so the abstraction can be validated against real
  models, even if the final selection is made much later.
- **Blocks.** Phase 27; abstraction design in Phase 3. Depends on PD-004 and PD-075.

### PD-081 — Applicable privacy regimes **[BLOCKING — Phase 6] [LEGAL]**
- **Exact question.** Which privacy laws apply, based on the territories decided in PD-004?
- **Why it matters.** **[UNVERIFIED]** This specification makes no claim about the privacy
  law of any jurisdiction. Determining applicability requires qualified advice, and it drives
  retention, deletion, export, consent, and children's-profile obligations.
- **Options.** Determined by qualified advice; not an engineering choice.
- **RECOMMENDATION — NOT APPROVED:** none. Recommend obtaining advice **immediately after
  PD-004**, since five other decisions (PD-082, PD-063, PD-083, PD-084, PD-086) are
  downstream of it.
- **Blocks.** Phase 6; shapes Phase 4 data classification.

### PD-087 — Performance targets **[BLOCKING — Phase 32]**
- **Exact question.** Are the proposed performance targets approved as the budgets against
  which the platform is validated?
- **Why it matters.** Every value in `PRODUCT_SPEC.md` §36 and `REQUIREMENTS.md` §B3 is
  **PROPOSED — REQUIRES VALIDATION**. None is approved and none is a guarantee.
- **Options.** Approve as proposed · adjust · defer until measurement exists.
- **RECOMMENDATION — NOT APPROVED:** adopt the proposed values as **working budgets** now,
  and treat Phase 32 as the point at which they are confirmed or revised against evidence.
  A provisional budget is far more useful than none, because it gives every earlier phase
  something to design toward.
- **Blocks.** Phase 32. Inseparable from PD-037.

### PD-088 — DRM provider(s) **[BLOCKING — Phase 30]**
- **Exact question.** Which DRM provider or providers will be integrated, for which
  platforms?
- **Why it matters.** **[UNVERIFIED]** No DRM provider's capabilities, platform coverage,
  licensing terms, or policy support may be assumed. `CLAUDE.md` §4.2 requires validation
  against two candidates. **Restated:** DRM-readiness means an integration point; it never
  means implementing, weakening, or working around any protection system.
- **Options.** A named provider or set, per platform.
- **RECOMMENDATION — NOT APPROVED:** none on the provider. Note the register lists this as
  blocking Phase 30, but **Phase 16 needs to know whether content is encrypted at packaging
  time** — so the encryption question should be answered well before the provider question.
- **Blocks.** Phase 30 nominally; Phase 16 in practice.

### PD-089 — Availability target **[BLOCKING — Phase 34]**
- **Exact question.** What numeric availability objective does KMS TV commit to?
- **Why it matters.** An undefined target cannot be met or measured, and availability drives
  redundancy, failover, and on-call design.
- **Options.** A numeric target, possibly differentiated between playback and
  non-playback surfaces.
- **RECOMMENDATION — NOT APPROVED:** on the number, none. On the **shape**, differentiate:
  playback availability matters far more than admin availability, and a single blended
  number hides that.
- **Blocks.** Phase 34; influences Phase 3 redundancy design.

### PD-090 — RPO and RTO **[BLOCKING — Phase 34]**
- **Exact question.** What are the numeric recovery point and recovery time objectives?
- **Why it matters.** `CLAUDE.md` §17 requires both numerically before production — *an
  undefined target cannot be met*. A restore drill that exceeds RTO is an incident.
- **Options.** Numeric RPO and RTO values.
- **RECOMMENDATION — NOT APPROVED:** none on the values. Note that **RPO in particular
  influences backup architecture in Phase 3–4**, so an indicative figure early is cheap
  insurance even if the committed figure comes later.
- **Blocks.** Phase 34. Also tracked as `PROJECT_STATE.md` D-011.

### PD-093 — CDN provider **[BLOCKING — Phase 17]**
- **Exact question.** Which CDN will deliver segments, and which second candidate validates
  the abstraction?
- **Why it matters.** **[UNVERIFIED]** No CDN's capabilities, signed-URL semantics, purge
  behaviour, origin shielding, or regional coverage may be assumed until read from official
  documentation. `CLAUDE.md` §4.2 requires validation against two candidates before the
  abstraction is finalized.
- **Options.** A named primary plus a named validation candidate.
- **RECOMMENDATION — NOT APPROVED:** none on the provider. Recommend naming **two candidates
  during Phase 3**, because signed-URL and purge semantics differ enough between CDNs that
  an abstraction designed against one will encode that one's model as universal — which is
  precisely what `CLAUDE.md` §4.2 exists to prevent.
- **Blocks.** Phase 17; abstraction design in Phase 3. Depends on PD-004 for footprint.

---

# PART 3 — DECISION ORDER CLASSIFICATION

Every blocking decision classified **A**, **B**, or **C**.

### Classification criteria

| Class | Criterion |
|---|---|
| **A — MUST DECIDE BEFORE ARCHITECTURE** | The decision changes the *shape* of the architecture or the data model. If it is taken after Phase 3–4, work already done must be redone. Deferring is not neutral: it silently selects a default. |
| **B — SHOULD DECIDE BEFORE IMPLEMENTATION** | The decision changes *values, policies, or vendors* within a shape that is already settled. It must be answered before the phase that implements it, but the architecture is correct either way. |
| **C — CAN BE DEFERRED** | The decision is needed late, and deferring costs little because it does not constrain earlier design. Some carry a caveat where an early indicative answer is cheap insurance. |

---

## Class A — MUST DECIDE BEFORE ARCHITECTURE (7)

| ID | Decision | Why Class A |
|---|---|---|
| **PD-001** | Product identity and the pre-existing scaffold | Repository must be coherent before any code lands beside it. Also gates D-009 (web framework), which is a Phase 3 output |
| ~~PD-004~~ | ~~Target territories~~ | ✅ **RESOLVED** — approved 2026-08-13 |
| **PD-095** | Travelling-subscriber policy *(new, from PD-004)* | Determines whether an account carries a home territory distinct from its determined current territory — a Phase 4 data-model question |
| ~~PD-008~~ | ~~Multi-tenancy~~ | ✅ **RESOLVED** — approved 2026-08-13, Option A single-tenant |
| **PD-092** | Launch platform scope | Sequences all client work, settles D-009, and starts the procurement and certification clocks that cannot be compressed later |
| **PD-035** | Content rating / maturity scheme | The Phase 4 data model must carry a scheme identifier alongside the rating value. Retrofitting a second scheme into a single-scheme model is a migration across every rated asset |
| **PD-081** | Applicable privacy regimes | Drives data classification, retention, and deletion design — all Phase 4 concerns, not Phase 6 afterthoughts |
| **PD-031** | Deletion versus retention | Determines whether records are deletable, anonymizable, or immutable. That is a schema property, decided once |

**Why these seven and not others.** Each changes what the system *is*, not what it is
configured to. A wrong answer discovered at Phase 12 costs a conversation; a wrong answer
discovered at Phase 20 costs the phases in between.

---

## Class B — SHOULD DECIDE BEFORE IMPLEMENTATION (14)

| ID | Decision | Needed before | Why Class B |
|---|---|---|---|
| **PD-026** | Token revocation interval | Phase 8 | A value within a settled design; the revocation mechanism exists regardless |
| **PD-038** | Maximum registered devices | Phase 9 | Configuration, not structure. The limit mechanism is specified either way |
| **PD-044** | EPG retention and horizon | Phase 11 | Sizing and partitioning parameter, not a schema shape. Decide with PD-058 |
| **PD-005** | Free tier | Phase 12 | An entitlement *outcome* through the existing engine, not a separate path |
| **PD-013** | Package catalogue | Phase 12 | Contents are data. **Caveat:** the structural sub-question (PD-049, add-ons vs tiers) should be settled before Phase 4 |
| **PD-014** | Pricing and currency | Phase 12 | Money handling is already specified; the values are data |
| **PD-051** | Grace behaviour during `past_due` | Phase 12 | A policy within the settled subscription state machine |
| **PD-055** | Rights expiry warning lead time | Phase 13 | A threshold; the warning mechanism is required regardless |
| **PD-040** | Maximum concurrent streams | Phase 15 | A ceiling value; atomic enforcement is specified regardless |
| **PD-056** | In-progress viewing at rights expiry | Phase 15 | A policy. **Caveat:** make it per-agreement configurable, which is a small Phase 4 model consequence |
| **PD-093** | CDN provider | Phase 17 | Behind an abstraction. **Caveat:** name two candidates in Phase 3 so the abstraction is not shaped by one vendor's model |
| **PD-037** | Minimum supported OS versions and model years | Phase 19 | Does not change client architecture, but does drive device procurement — start early |
| **PD-074** | Payment providers | Phase 27 | Behind an abstraction. **Caveat:** name two candidates in Phase 3, same reason as PD-093 |
| **PD-088** | DRM provider(s) | Phase 30 | Behind an abstraction. **Caveat:** whether content is encrypted must be known by **Phase 16**, ahead of the provider choice |

**Why these are not Class A.** In each case the *mechanism* is already specified and does
not change with the answer — only the value, policy, or vendor behind an abstraction does.
Four carry caveats where a small early answer prevents a later retrofit.

---

## Class C — CAN BE DEFERRED (3)

| ID | Decision | Why Class C | Caveat |
|---|---|---|---|
| **PD-087** | Performance targets | Validated in Phase 32 against real measurement; the proposed values already give earlier phases something to design toward | Adopt the proposed values as **working budgets** now — a provisional budget beats none |
| **PD-089** | Availability target | Needed before production (Phase 34); does not constrain Phases 4–18 | An indicative figure helps Phase 3 redundancy design |
| **PD-090** | RPO and RTO | Needed before production (Phase 34) | **RPO influences backup architecture in Phase 3–4.** An indicative figure early is cheap insurance |

**Why only three are deferrable.** Deferrable does not mean unimportant — `CLAUDE.md` §17
makes RPO and RTO mandatory before production, and a missed restore drill is an incident.
It means only that the *later* answer does not invalidate *earlier* work.

---

## Classification summary

| Class | Count | IDs |
|---|---:|---|
| **A — before architecture** | 6 open | PD-001, ~~PD-004 (RESOLVED)~~, ~~PD-008 (RESOLVED)~~, PD-031, PD-035, PD-081, PD-092, **PD-095** |
| **B — before implementation** | 15 | PD-005, PD-013, PD-014, PD-026, PD-037, PD-038, PD-040, PD-044, PD-051, PD-055, PD-056, PD-074, PD-088, PD-093, **PD-094** |
| **C — deferrable** | 3 | PD-087, PD-089, PD-090 |
| **Total** | **24 open** (PD-004 and PD-008 resolved; PD-094 and PD-095 added) | |

---

# PART 4 — RECOMMENDED DECISION ORDER

Ordered by dependency and by external lead time, not by importance alone. Several items
are not decisions at all but **clocks that should be started**, because calendar time is
the one resource that cannot be recovered later.

### Step 1 — Immediately (unblocks everything else)

| # | Item | Note |
|---|---|---|
| 1 | ~~**PD-004 — Territories**~~ | ✅ **APPROVED 2026-08-13** — Georgia launch, multi-territory architecture, future territories configurable. Downstream decisions PD-081, PD-035, PD-054, PD-075 now have a determinate input |
| 2 | ~~**PD-008 — Multi-tenancy**~~ | ✅ **APPROVED 2026-08-13** — Option A, single-tenant, one operator. The rewrite-class risk is closed by decision |
| 3 | **PD-092 — Launch platform scope** | Informed by PD-004 (platform share by market) but should not wait long behind it; it starts the procurement clocks |
| 4 | **PD-001 — Scaffold** | Trivial to decide, and blocks nothing until code lands — but it costs one minute now and confusion later |

### Step 2 — Start the long-lead clocks (not decisions; actions)

These consume calendar time regardless of engineering progress, and every week of delay is
a week added to the critical path.

- Begin **Samsung and LG developer registration** (blocker B-006) — weeks of external process
- Begin **macOS hardware or hosted CI procurement** for iOS (blocker B-004) — purchasing,
  not engineering, and cannot be resolved by installation
- Begin **rights negotiation scoping** once PD-004 lands
- Obtain **legal engagement** for the 25 [LEGAL]-flagged decisions

### Step 3 — Immediately after PD-004 (legal dependency chain)

| # | Item | Note |
|---|---|---|
| 5 | **PD-081 — Applicable privacy regimes** | [LEGAL]. Five further decisions are downstream (PD-082, PD-063, PD-083, PD-084, PD-086) |
| 6 | **PD-035 — Content rating scheme** | [LEGAL]. Blocks Phase 4 |
| 7 | **PD-031 — Deletion versus retention** | [LEGAL]. Depends on PD-081; shapes Phase 4 |

### Step 4 — Before Phase 3 completes (small answers, large savings)

| # | Item | Note |
|---|---|---|
| 8 | **PD-049 — Add-ons versus tiers** | Not itself blocking, but shapes the Phase 4 entitlement model. Cheap now, expensive later |
| 9 | **Name two candidates each for PD-093 (CDN) and PD-074 (payments)** | Selection can wait; *validation candidates* cannot, or the abstraction encodes one vendor's model as universal |
| 10 | **PD-088 — encryption at packaging** | The provider choice can wait for Phase 30; whether content is encrypted cannot wait past Phase 16 |
| 11 | **Indicative PD-090 (RPO) and PD-089 (availability)** | Provisional figures inform Phase 3 backup and redundancy design at almost no cost |

### Step 5 — Before their implementing phase (Class B remainder)

`PD-026` (Phase 8) → `PD-038` (9) → `PD-044` with `PD-058` (11) → `PD-005`, `PD-013`,
`PD-014`, `PD-051` (12) → `PD-055` (13) → `PD-040`, `PD-056` (15) → `PD-093` (17) →
`PD-037` (19) → `PD-074` (27) → `PD-088` (30)

### Step 6 — Confirm late (Class C)

`PD-087` at Phase 32 against measurement · `PD-089` and `PD-090` at Phase 34, committed and
proven in a timed restore drill.

---

## Closing note on how to answer

Three points, offered because they save the most time:

1. ~~**A provisional decision, recorded, beats an open one.**~~ **Resolved:** PD-008 was
   answered decisively — Option A, single-tenant, with multi-tenancy explicitly out of
   scope rather than merely unbuilt. That is a stronger answer than the provisional one
   this note anticipated, and it removes the rewrite-class risk entirely.

2. **The [LEGAL] decisions need a lawyer, not a longer analysis from me.** Twenty-five of
   the ninety-three are flagged [LEGAL], and no amount of further engineering work will
   resolve them. Engaging advisors early is the single highest-leverage action available.

3. **Some of what looks like decision-making is actually procurement.** Samsung and LG
   registration, macOS hardware, and TV devices for minimum-spec validation all take
   calendar time. They can be started today, before any of the above is settled.

---

**STEP 1 STATUS: WAITING FOR PRODUCT OWNER DECISIONS**

Phase 1 is **not** marked COMPLETE. STEP 2 has **not** started. No architecture, schema,
migration, or application code has been created.
