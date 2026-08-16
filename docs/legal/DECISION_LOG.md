# Legal and Licensing Decision Log — KMS TV

Every legal, licensing, or content-rights decision is recorded here with a date and a
rationale. Entries are **append-only**: a superseded decision is marked superseded and
kept, never deleted. Traceability is the point.

Related: `CONTENT_RIGHTS_POLICY.md`, `THIRD_PARTY_LICENSES.md`, `CLAUDE.md` §2–§3, and
the *Decisions* section of `PROJECT_STATE.md`.

---

## Entry format

```
### L-NNN — <short title>
- **Date:**
- **Phase:**
- **Decision:**
- **Rationale:**
- **Alternatives considered:**
- **Legal review required:** yes / no
- **Legal review status:** not required / pending / completed on <date> by <who>
- **Recorded by:**
- **Status:** Accepted / Rejected / Superseded by L-NNN / Open
```

---

## Log

### L-001 — Rights metadata is a mandatory attribute of every distributable asset
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** No asset may be distributable without complete rights metadata: holder,
  contract reference, territories, window, device classes, distribution modes, and
  concurrency limits. Enforced by the data model (Phase 4) and the rights system
  (Phase 13), not by process alone.
- **Rationale:** Rights cannot be retrofitted onto a catalogue built without them without
  effectively rewriting the entitlement and playback paths. Modelling it from the first
  schema is the only affordable time to do it.
- **Alternatives considered:** Rights as an optional annotation added later — rejected;
  it makes unauthorized distribution the default behaviour of the system.
- **Legal review required:** no (engineering design decision)
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-002 — Distribution modes are granted and enforced independently
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** Live, catch-up, restart, VOD, and download rights are separate grants.
  Holding one never implies another. Each is enforced separately at playback
  authorization.
- **Rationale:** Assuming that live rights extend to catch-up is the most common
  inadvertent breach in time-shifted television. Phase 26 tests this explicitly and
  negatively.
- **Legal review required:** no (restates standard rights practice; specific contracts
  govern specific assets)
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-003 — Rights expiry enforced by two independent mechanisms
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** Expired rights disable content via a scheduled job **and** via a re-check
  at each playback authorization request. Both are required; each is tested with the
  other disabled.
- **Rationale:** A single mechanism can fail silently, and silent failure here means
  serving content we have no right to serve. Two independent mechanisms make silent
  failure require two simultaneous faults.
- **Legal review required:** no
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-004 — No third-party component adopted in Phase 0
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** No dependency, library, image, or asset was installed or incorporated.
  `THIRD_PARTY_LICENSES.md` is intentionally empty.
- **Rationale:** Phase 0 is inspection and governance only. The intake procedure exists
  before the first component arrives, rather than being written retroactively around
  choices already made.
- **Legal review required:** no
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-005 — FFmpeg license review precedes FFmpeg installation
- **Date:** 2026-08-13
- **Phase:** 0 (applies at Phase 16)
- **Decision:** The specific FFmpeg build's license (LGPL vs. GPL) and enabled components
  are reviewed and recorded in `ffmpeg-license-review.md` **before** installation for
  production use. Nonfree builds must not be distributed.
- **Rationale:** FFmpeg's license depends on build configuration, and the distinction
  materially affects a proprietary product. Reviewing after adoption converts a choice
  into a problem.
- **Legal review required:** yes
- **Legal review status:** pending — due before Phase 16
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-006 — Codec and patent licensing treated as a separate obligation
- **Date:** 2026-08-13
- **Phase:** 0 (applies at Phases 16 and 37)
- **Decision:** Patent licensing for H.264/AVC, H.265/HEVC, AAC and similar technologies
  is tracked separately from software licensing and must be resolved before commercial
  launch.
- **Rationale:** Software freedom does not imply patent freedom. This obligation carries
  real commercial cost and is routinely discovered at launch rather than planned for.
- **Legal review required:** yes
- **Legal review status:** pending — due before commercial launch
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-011 — Travelling subscribers: subscription follows the subscriber; territory rights unchanged
- **Date:** 2026-08-13
- **Phase:** 1
- **Decision:** The product owner has approved **PD-095 — Option A**. An active subscription
  remains associated with the subscriber while travelling, but **confers no content access
  by itself**. Access remains subject to the **current** territory, service availability,
  applicable content rights, package entitlement, playback authorization, and platform or
  device policy.
- **Rationale for recording here:** the decision draws a line that matters legally.
  **Subscription ownership is a commercial relationship with the subscriber; content
  distribution rights are a territorial grant from a rights holder.** The approval keeps
  them separate, so a subscription can never be construed as a licence to receive content
  in a territory the operator has no rights for. This is precisely the confusion that
  produces unlicensed distribution, and the approval forecloses it.
- **Consistent with:** L-002 (distribution modes granted independently) and L-009 (service
  availability and content rights are independent conditions). PD-095 adds a third
  independence: **subscription validity is independent of territorial content rights.**
- **Alternatives considered:** home territory governs access; per-rights-agreement
  configuration; explicit travel allowance with a defined duration. Recorded in
  `docs/product/DECISIONS.md` PD-095.
- **Legal review required:** **yes — for the aspects the approval deliberately left open.**
  Outstanding and unresolved by this decision:
  - whether any applicable law or contract requires or restricts cross-border access for
    subscribers temporarily present in another territory
  - whether individual rights agreements permit reception outside the licensed territory in
    any circumstance
  - consumer-law and contract-term implications of a subscription whose usable content
    varies by location
  - privacy implications of determining a subscriber's current territory, which is location
    data (see PD-081, PD-086)
- **Legal review status:** pending — required before Phase 15 (playback authorization), and
  informed by PD-081.
- **[UNVERIFIED]** No claim is made about the law of any jurisdiction, nor about what any
  rights agreement permits. **No roaming duration, country list, percentage-of-time rule,
  VPN or IP rule, or travel-specific device restriction is defined or assumed** — the
  approval prohibits inventing them.
- **Recorded by:** Engineering agent, on the product owner's decision
- **Status:** Accepted

### L-010 — Single-operator product; not a SaaS platform for third parties
- **Date:** 2026-08-13
- **Phase:** 1
- **Decision:** The product owner has approved **PD-008 — Option A, single-tenant**. KMS TV
  is a platform for **one operator**. Multi-tenancy, white-label operators, and a SaaS
  operator platform are **out of scope** for the current product.
- **Rationale:** Recorded here because it materially narrows one licensing analysis.
  `CLAUDE.md` §3 requires third-party license compatibility to be determined against the
  **KMS TV distribution model**. That model is now confirmed as a proprietary server-side
  product plus proprietary client applications, operated by **one operator for its own
  subscribers** — not software offered to third parties as a hosted service. Network- and
  service-oriented copyleft terms turn on how software is *offered to others*, so
  confirming that KMS TV is not offered to third-party operators is a relevant input to
  that determination.
- **[UNVERIFIED]** No claim is made here about what any particular license requires. The
  license classification rules in `THIRD_PARTY_LICENSES.md` §2 are unchanged, and AGPL- and
  SSPL-family components still require explicit written legal approval before use. This
  entry records a **fact about the product**, not a conclusion about any license.
- **Alternatives considered:** deployment-per-operator (Option B) and shared-schema
  multi-tenancy (Option C), both recorded in `docs/product/DECISION_BRIEF.md` Part 1.
  Option A is narrower than both.
- **Legal review required:** no for the decision itself; the license classification work in
  `THIRD_PARTY_LICENSES.md` remains outstanding and unchanged
- **Consequence:** should multi-tenancy or white-label ever be revisited, it is a **new
  architectural decision requiring its own ADR**, and the license analysis above must be
  re-run, because the distribution model would have changed.
- **Recorded by:** Engineering agent, on the product owner's decision
- **Status:** Accepted

### L-008 — Launch territory: Georgia; multi-territory architecture
- **Date:** 2026-08-13
- **Phase:** 1
- **Decision:** The product owner has approved **PD-004**. KMS TV launches commercially in
  **Georgia**. The architecture is **multi-territory from day one**, and future territories
  are **configurable** without redesigning the core platform. **App distribution, service
  availability, and content rights are three separate concepts and must not be merged.**
- **Rationale:** Recorded here because the territory determines which legal regimes apply.
  The territory decision is commercial; its legal consequences are not, and they are
  enumerated below as outstanding.
- **Alternatives considered:** Multi-territory launch; deferring the territory decision.
  Both are recorded in `docs/product/DECISION_BRIEF.md` Part 1.
- **Legal review required:** **yes — for the consequences, not for the decision itself.**
  The following are now determinable and remain outstanding, each with a determinate input
  for the first time:
  - **PD-081** — which privacy/data-protection regime applies
  - **PD-035** — which content classification scheme applies
  - **PD-054** — tax treatment of subscription revenue
  - **PD-073** — invoice and receipt content requirements
  - **PD-075** — permissible payment methods
  - **PD-085** — children's privacy obligations
  - **PD-086** — data processing locations and cross-border transfers
  - **PD-095** — travelling-subscriber policy (new, arising from PD-004)
- **Legal review status:** pending — required before Phase 6 (threat model and privacy
  controls) and before Phase 12 (commercial model).
- **[UNVERIFIED]** **No claim is made in this or any KMS TV document about the regulatory,
  broadcasting, tax, or data-protection regime of Georgia or of any other country.** The
  territory being named does not make any legal fact about it known.
- **No future territory is named or assumed.** The approval cites the United States solely
  as an illustration that a further territory must be addable. Treating an illustration as
  a plan would violate `CLAUDE.md` §1, and no such inference has been made.
- **Rights consequence:** every rights agreement for launch must cover Georgia for the
  intended distribution modes. Serving any other territory requires both service
  availability being enabled there **and** rights covering it — two independent conditions
  (L-009).
- **Recorded by:** Engineering agent, on the product owner's decision
- **Status:** Accepted

### L-009 — Service availability and content rights are independent conditions
- **Date:** 2026-08-13
- **Phase:** 1
- **Decision:** Whether KMS TV is commercially available in a territory, and whether a
  given asset may be distributed in that territory, are **separate determinations with
  separate owners**. Neither implies the other, in either direction. Playback authorization
  evaluates them as two distinct checks producing two distinct denial reasons
  (`SERVICE_NOT_AVAILABLE` and `TERRITORY_RESTRICTED`).
- **Rationale:** Collapsing the two makes one of two real states unrepresentable: an asset
  licensed for a territory the operator does not serve, and — the dangerous one — a served
  territory assumed to carry a licensed catalogue. The second assumption is a direct route
  to unlicensed distribution, which `CLAUDE.md` §2 exists to prevent.
- **Alternatives considered:** A single combined territorial check. Rejected: it cannot
  express the states above, and it produces a denial reason that tells neither the viewer
  nor the operator which condition failed.
- **Legal review required:** no (design decision implementing L-008); the underlying rights
  determinations remain [LEGAL]
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-007 — Pre-existing repository scaffold: legal status unassessed
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** The repository contains a pre-existing, non-buildable Vite/React/Supabase
  starter of external origin (see `PROJECT_STATE.md` §2.1). Its provenance and license
  have **not** been assessed. It must not be treated as a foundation, and no code may be
  copied from it, until its origin and license are established.
- **Rationale:** Code of unknown provenance is code of unknown license. `CLAUDE.md` §3
  requires the license be known before incorporation, and "it was already in the
  repository" is not a determination of provenance.
- **Legal review required:** yes, **if** any part of it is retained or reused. Not
  required if it is removed.
- **Legal review status:** pending — resolve alongside decision D-006 in Phase 1
- **Recorded by:** Engineering agent
- **Status:** Open

### L-012 — PD-035 primary-source legal research blocked; source access required
- **Date:** 2026-08-16
- **Phase:** 1
- **Status:** **LEGAL RESEARCH BLOCKED — EXTERNAL SOURCE ACCESS REQUIRED**
- **Record:**

  > "Primary-source legal research for PD-035 could not be completed from the current
  > Claude environment because external HTTPS access to the required official sources was
  > denied by the environment network policy. No bypass was attempted."

- **Required primary sources:**
  - **Matsne** — the official legislative database of Georgia
  - **Georgian Communications Commission / ComCom**
- **What this entry does and does not say.** It records a **source accessibility** problem
  and nothing more. It does **not** claim that these sources do not exist. It does **not**
  claim that Georgian law on this subject is unknown, unknowable, or absent. The sources
  exist and are official; they could not be read from this environment.
- **Evidence.** Direct HTTPS to both hosts was refused by the environment's egress policy
  (gateway answered `403` to `CONNECT`) on 2026-08-16. A neutral control host was refused
  by the same policy in the same attempt, which establishes that the denial is a general
  allow-list restriction and **not** a restriction aimed at these sources. No circumvention,
  proxy chaining, or alternate route was attempted, and none may be — `CLAUDE.md` §1.

- **Sources requiring direct verification.** None of the following has been read from a
  primary source by the engineering agent. Each must be verified directly, with its URL,
  consolidated-version/publication identifier, and retrieval date recorded at the time of
  reading:
  1. **Law of Georgia on Broadcasting** — current consolidated version. The specific
     consolidated version previously supplied is **not identified by publication index**, so
     it cannot presently be confirmed as current.
  2. **ComCom normative acts concerning on-demand audiovisual media services.**
  3. **ComCom normative acts concerning protection of minors and age marking**, including
     any act defining a required marking format.
  4. **Other potentially applicable Georgian legal instruments identified during the
     research** and not yet triaged for relevance — candidate subject areas encountered
     were legislation on protection of minors from harmful influence, on protection of
     family values and minors, on advertising, and on electronic communications.
     **These were surfaced only as search-index references, not read.** Their existence,
     titles, current status, and applicability to KMS TV are all **unverified**, and per
     the evidence standard below they carry no weight until retrieved from the official
     source. They are listed solely as a retrieval agenda, because the source base PD-035
     was previously reasoned from covered one instrument only.

- **Evidence standard.** **Search-index results and search-engine summaries are NOT verified
  legal evidence and MUST NOT be recorded as legal facts.** They may be used only to
  identify candidate official documents to retrieve. A legal fact enters KMS TV
  documentation only when it has been read from the official source, with that source's URL
  and version identifier recorded.

- **Separation principle (binding on all KMS TV documentation).**
  **"Verified legal facts, product requirements, and legal interpretations must remain
  explicitly separated."** An unverified legal assumption MUST NOT be converted into a
  product requirement. Where a product requirement exists for product reasons, it is
  recorded as a product requirement and not attributed to law. Mirrored as
  `PROJECT_STATE.md` §7 **D-026**.

- **Effect on PD-035.** PD-035 remains **OPEN — LEGAL REVIEW REQUIRED**. It is **not**
  approved and no scheme has been selected. The technical model may proceed **conceptually**
  — see `PROJECT_STATE.md` §7 **D-025**, which records a **capability requirement only** and
  asserts no legal obligation.
- **Consequential decisions recorded, not resolved:** **PD-096**, **PD-097**, **PD-098** in
  `docs/product/DECISIONS.md` §4, all **OPEN**.
- **Alternatives considered:** none available within the environment. Resolution requires
  one of: allowing the two hosts in the environment's network policy; supplying the official
  texts directly with their provenance; or engaging qualified Georgian counsel with direct
  source access.
- **Legal review required:** **yes** — qualified Georgian legal advice is required regardless
  of source access, for interpretation as well as retrieval.
- **Legal review status:** **blocked pending source access**; required before Phase 4
  (database and ERD) can model classification data, and before any classification behaviour
  is specified.
- **[UNVERIFIED]** No claim is made in this entry about the content of Georgian law, about
  which classification scheme applies, about whether age marking, warnings, parental
  controls, or authorisation are legally required, or about how live, VOD, catch-up, and
  restart are classified. Those questions are open.
- **Recorded by:** Engineering agent
- **Status:** Open
