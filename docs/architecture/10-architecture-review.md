# Architecture Review — Phase 0

**Date:** 2026-08-10
**Reviewer role:** principal architect, reviewing the Phase 0 foundation as written
**Scope:** internal consistency, security boundaries, scalability, database, streaming, API,
licensing, third-party risk, Smart TV compatibility, observability, disaster recovery
**Outcome:** 25 findings. **All corrected in this repository.** 1 new decision record
([ADR-0011](adr/ADR-0011-availability-projection-shape.md)), 1 amended
([ADR-0005](adr/ADR-0005-cmaf-multi-drm.md)), 2 new open questions.

This document is kept because the corrections are only comprehensible alongside what they replaced,
and because several findings encode reasoning that would otherwise be re-litigated later.

---

## Severity key

| | Meaning |
|---|---|
| **S1** | Would have caused an incident, a breach, or a rewrite |
| **S2** | Would have caused significant rework or a wrong decision |
| **S3** | Inconsistency or gap; cheap now, expensive later |

---

## A. Architectural inconsistencies

### A1 · S3 · The context count was wrong
`02-bounded-contexts.md` defined **17** contexts (A1–A3, B1–B4, C1–C3, D1–D4, E1–E3) while six
documents said "16" or "sixteen". Behind the arithmetic sat a real gap: **E2 Analytics & Telemetry
had no module in the `core-api` list and no schema in the database list**, and nothing said whether
that was deliberate.

**Corrected.** 17 contexts, 16 modules, 16 schemas — and the discrepancy is now stated as intentional,
with the reason (Analytics lives in `telemetry-collector` and the analytics store; putting the
largest and least valuable dataset in the transactional database would be the worst storage decision
available). If those three numbers ever agree, something has been merged without a decision.

### A2 · S1 · The playback authorizer needed a grant it was denied
The authorization sequence began *"Session and device valid — source A1, A3"*. A1 is Identity, whose
schema the authorizer's database role explicitly does not include. As written, check #1 could not
execute.

**Corrected**, and in the direction that keeps the boundary rather than widening it: access token
validation is **stateless** — signature plus a small replicated revocation set — so the authorizer
holds **no `identity` grant at all**. Had it been resolved the other way, the identity store would
have become a hard dependency of the playback hot path, scaling with play requests instead of
sign-ins, and a compromise of the authorizer would have reached credential data.

### A3 · S1 · "The licence proxy is the only workload that can read content keys" was false
Asserted in ten places. But the **packager must have keys in order to encrypt** — which the secrets
document itself acknowledged in a single line about "a controlled path". A security control described
inaccurately cannot be audited, and this one is the platform's most important.

**Corrected** to a statement that is precise and therefore testable: two components touch key
material, in opposite directions. The packager is on the **write path** and receives keys **pushed**
per job with no ability to query the vault; the licence proxy is the only component that can
**resolve a key by identifier**, which is the request-driven capability an attacker would want. Both
sit in the protected zone. The distinction is now a P13 verification item: *if the chosen vault
cannot express it, the model is not enforceable and must be redesigned around what the vault can
actually do.*

### A4 · S3 · Delivery Control is a very thin context
D4 owns a port, a token format and some configuration — no real domain model. It earns its boundary
(nothing else may know a vendor's name), not its status as a context.

**Corrected** by admitting it in place, with a review trigger: if by Phase 10 it has not grown its own
model — CDN health, cost signals, steering policy — it collapses into D2 as a port and is retired.

---

## B. Scalability

### B1 · S1 · The availability projection did not scale — the most serious finding
`Availability` was specified as a materialised **cross-product** of
`subject × exploitation × platform × territory × monetization`. At realistic cardinalities that is
**~525 million rows** before multiple intervals per combination.

Row count was the lesser problem. **Recompute cost was proportional to catalog size rather than to
the size of the change.** Rights edits are rarely narrow — a licensor adds a territory exclusion
across their whole catalog — so one edit would invalidate tens of millions of rows. Projection lag
would therefore be worst exactly when it is most dangerous, because a rights correction is precisely
the change that must take effect quickly: until it does, the platform is serving content it is not
licensed to serve.

**Corrected** by [ADR-0011](adr/ADR-0011-availability-projection-shape.md): the projection is keyed
on `subject × exploitation` only, carrying **interned** territory and usage rule sets plus platform
and monetization **bitmasks** evaluated at read time. Interning is the load-bearing part — an
agreement-wide change rewrites one rule-set row rather than millions of projection rows, so recompute
becomes proportional to what changed. Blackouts are removed from the projection entirely and
evaluated against a small hot set, because they are few, urgent, and frequently applied minutes
before taking effect.

### B2 · S1 · No connection pooling
PHP-FPM is process-per-request. Four deployment profiles scaled horizontally would exhaust
PostgreSQL's connection limit long before its CPU — and connection exhaustion presents as a **total
outage**, not as gradual slowdown. Nothing in the documents mentioned a pooler.

**Corrected**: a pooler from Phase 1, **a separate pool per deployment profile** (a shared pool would
undo the isolation ADR-0003 exists to provide), transaction-pooling constraints stated up front
because they are cheap to adopt and expensive to retrofit, and saturation alerting. Added to ADR-0003
as rule 8.

### B3 · S2 · The decision audit write sat on the hot path without a stated mechanism
"If the audit record cannot be written, playback does not start" is correct and non-negotiable — but
it puts a durable write inside a 150 ms p99 budget on the highest-volume control-plane path, and
nothing said how that stays affordable.

**Corrected**: a single append into a time-partitioned table **with no indexes beyond the partition
key and `decision_id`** (query indexes live on an offline copy), no synchronous replica wait, and —
the significant one — **heartbeats write a decision record only when the decision changed.** Writing
one per heartbeat would multiply the platform's largest control-plane write by the heartbeat rate for
no evidential gain.

---

## C. Security boundaries

### C1 · S1 · The playback request accepted a client-asserted `device_id`
The request body carried `device_id`, and `X-KMS-Device-Id` was also specified as a header. Device
class is an **input to the rights and DRM decision**, so a caller presenting another device's
identifier could inherit its class. That is privilege escalation, not an inconsistency.

**Corrected**: account, profile and device all come from the device-bound access token; the body
carries none of them. `capabilities` remains client-asserted and is explicitly constrained to
**narrowing only** — a client claiming a capability that would raise its entitlement is ignored,
because those bounds come from rights and from the server-side device class.

### C2 · S1 · Failing open on concurrency contradicted licensor caps
Degraded-mode handling allowed playback when the concurrency store was unavailable, reasoning that
over-permissiveness is "a revenue nuisance, not a contract breach". But §4 of the same document
states that concurrency caps also come from **rights usage rules** — where exceeding one *is* a
breach, exactly like ignoring a territory.

**Corrected**: the two sources fail differently. A plan limit may fail open, logged and alerted; a
licensor-mandated cap fails closed. The concurrency check now returns **which** limit bound the
request, and that is recorded in the decision.

### C3 · S2 · Telemetry ingestion had no threat treatment
`telemetry-collector` is the platform's largest client-supplied write surface and appeared in the
threat model nowhere. Three distinct exposures: amplification, **data poisoning** (fabricated events
corrupting the metrics decisions are made on — and, if telemetry ever feeds licensor reporting, the
numbers we are contractually bound by), and log injection downstream.

**Corrected**: added as threat T13, with session-token attribution, per-session volume limits, a
bounded schema rejected rather than truncated, and the governing rule — **telemetry events are
claims, not facts.** Anything used for billing, licensor reporting or entitlement is derived from
server-side records.

---

## D. Database

### D1 · S2 · The identifier type was left ambiguous
"ULID, stored as `char(26)` **or** `uuid`" — an identifier type decided per table is one that will
eventually be inconsistent, and inconsistent identifier types are painful to unwind once foreign
references exist.

**Corrected** to a single decision: **UUIDv7 in a native `uuid` column.** Time-ordered like ULID, so
index locality and cursor pagination are unaffected, but 16 bytes rather than 26-plus on every unique
index and every cross-context reference, and no rendering layer. Verification note added, since
generation support in the chosen framework version is unconfirmed and the fallback is to generate in
application code rather than to change identifier type.

---

## E. Streaming

### E1 · S1 · The CMAF fallback covered the wrong axis
ADR-0005 provided a `cenc` fallback for devices that cannot do `cbcs`. But the larger legacy risk is
**container, not scheme**: older HLS implementations have expected MPEG-TS segments rather than
fMP4. A device that cannot play fMP4 under HLS is not helped at all by re-encrypting it.

**Corrected**: two **independent** fallback axes — scheme (`cbcs`/`cenc`) and container (fMP4/TS) —
both selectable per device class, with HLS-with-fMP4 added to the blocking verification checklist as
a separate item. Conflating them would have meant discovering during Phase 9 that the fallback we
built cannot serve the devices needing one, after the pipeline was finished.

### E2 · S1 · The delivery token would have destroyed cache offload
Delivery tokens go in the URL. A query parameter is part of the CDN cache key by default, so **every
viewer would receive a private copy of every segment and offload would collapse to zero** — raising
origin egress, the largest cost line in the platform, by roughly the offload ratio the whole CMAF
single-encode decision exists to protect.

**Corrected**: an explicit configuration requirement on every CDN adapter — validate the token at the
edge, **exclude it from the cache key**, keep segment paths identical across viewers — plus offload
monitoring, since this misconfiguration is invisible until an invoice arrives.

### E3 · S2 · GOP and segment alignment across the ladder was never stated
ABR requires closed GOPs with IDR frames at identical timestamps across every rendition. Nothing said
so. Misalignment fails *only at switch points*, so it looks fine on a fast connection and breaks for
viewers on variable networks — the population ABR exists to serve — and is invisible to any test that
plays one rendition end to end.

**Corrected**: stated as a correctness requirement, added to automated post-encode QC, and required
to survive encoder failover.

---

## F. API

### F1 · S1 · `public` caching plus per-viewer filtering is a cross-account leak
Two rules were each individually correct and jointly unsafe: *"a client never receives an item it may
not see"* (server-side filtering) and *"catalog metadata: `public, max-age=300`"*. A shared cache
would serve one viewer's filtered response to another.

**Corrected** by splitting the response rather than weakening either rule: **catalog metadata**
(varies by locale and territory only — publicly cacheable, territory in the **cache key**, not a
`Vary` header) and a **personalised availability overlay** (`private`). This also forced a
distinction the product needs anyway — content that must be **absent** from a response versus content
that is **present but not playable**, which is what makes upsell and "leaving soon" possible.

Related: `X-KMS-Client*` headers must not vary a cacheable response, or every client build gets its
own cache entry.

### F2 · S3 · Cursor pagination was mandated where it does not fit
EPG queries are `channel × time range` — a bounded window over a time-partitioned table, not an
open-ended scroll. **Corrected**: EPG takes an explicit time range with a documented maximum span.

---

## G. Licensing and dependencies

### G1 · S2 · A version conflict was already latent in the verified table
`pestphp/pest` v5 requires PHP `^8.4`; Laravel 13's floor is `^8.3`. Pinning 8.3 silently forces
Pest 4. Found by reading the constraints against each other rather than individually.

**Corrected**: raised on the ADR-0007 checklist with a recommendation to **pin PHP 8.4** — the test
framework should not be what decides the runtime version.

### G2 · S2 · The FFmpeg licensing note understated the practical case
It correctly said `--enable-gpl` changes FFmpeg's licence, but implied that path is exceptional. The
common open-source H.264/H.265 encoders **are** GPL-or-commercial, so `--enable-gpl` is the normal
path, not an edge case. Separately, **copyright licence and patent licence are different obligations
to different parties** — and the documents blurred them.

**Corrected**: both distinctions stated explicitly, with counsel review required before a codec or
encoder is committed to, ahead of Phase 5 rather than after the pipeline is built around a choice.

### G3 · S3 · No vendor exit plan
Ports make vendors replaceable in principle. Nothing required anyone to check.

**Corrected**: an **exit plan** is now the seventh mandatory part of every integration, written when
the adapter is built while the answers are known — not during the renegotiation where it is needed.

---

## H. Smart TV compatibility

### H1 · S1 · "TLS 1.3 everywhere" would have locked out part of the device matrix
Correct for current browsers, wrong for televisions. A device that cannot negotiate TLS 1.3 does not
degrade — it never connects.

Worse, and not mentioned at all: **root-store expiry**. An old device trusts an old set of root
certificates; when one expires, or when the chain moves to a CA the device has never heard of, that
device stops connecting permanently, with no server-side error to alert on and no way to update it.
This has removed large streaming services from older TVs before.

**Corrected**: TLS 1.2 floor at the public edge with 1.3 preferred and cipher suites chosen from
measured device support; 1.3 mandatory internally. The certificate chain and its root are now a
**device-compatibility decision**, verified against the oldest devices in the matrix and re-verified
before any CA change.

### H2 · S2 · Build targets and subtitle formats were unaddressed
Tizen and webOS applications run in the TV's browser engine, which may be years behind current.
Nothing said that build target and polyfill set are derived from the device matrix, nor that a
dependency shipping only modern syntax may be unusable despite installing cleanly. Subtitle format
support also varies by platform and model year, and the packaging pipeline implicitly assumed one
format.

**Corrected**: build target as a matrix output with transpilability as an adoption criterion for
TV-facing packages; multiple subtitle formats emittable from one source track, selected from the same
device-class mapping as container and scheme.

---

## I. Observability

### I1 · S1 · Everything was passive
Every signal measured what real viewers had already experienced. For failures that are regional,
device-specific, or occur only at the moment someone presses play, that is too late — and at 03:00
there may be no viewers on a channel to produce a signal at all.

**Corrected**: a **synthetic probe fleet** performing the full journey — authorize, manifest, DRM
licence, segments, decode — on every live channel continuously, from multiple regions, including
**denial paths** (a silent authorization regression that starts allowing everything produces no error
metric whatsoever). Probe accounts are synthetic and excluded from business metrics.

Also added: **telemetry ingestion falling to zero must page.** Dashboards going green and blind
simultaneously is the most dangerous state a monitored system can be in.

### I2 · S2 · Missing alerts on the new failure modes
Added: decision-audit write failure, connection pool saturation, and a sharp drop in cache offload
ratio (the observable symptom of E2, otherwise invisible until billing).

---

## J. Disaster recovery

### J1 · S1 · The key vault had no recovery story — the largest gap found
Backups covered PostgreSQL. **Losing the content key vault makes every encrypted asset permanently
unplayable** — a larger single-event loss than the application database, since mezzanines survive but
re-encoding a full catalog is weeks of work and already-recorded nDVR content is simply gone.

It was missed for the usual reason: key management is filed under security, and disaster recovery
looks under availability.

**Corrected**: key material backed up under a separate root of trust in a separate failure domain;
unseal material escrowed under split control (no single person can reconstruct it, and no single
person's absence can prevent reconstruction); **RPO ≈ 0**, since provisioning a key and backing it up
are one operation; quarterly rehearsed restore that includes issuing a licence from restored
material.

### J2 · S2 · Media and buffer recovery were unstated
Durability and availability were treated as one thing. **Corrected** with a per-asset table:
mezzanines need durability (a supplier may be unable to redeliver a master); renditions need only
availability (regenerable); **the nDVR buffer is recoverable from nothing at all** — which makes
buffer-writer isolation a data-protection control, not merely a reliability one.

### J3 · S3 · Projection rebuild time was assumed, not measured
"It is rebuildable" only reassures once someone has measured the rebuild on production-sized data.
**Corrected**: rebuild time is part of RTO and is on the launch-readiness checklist. Regional failover
is now explicitly out of scope until OQ-16, rather than left implied.

---

## K. Unnecessary complexity removed

| | Removed or deferred | Reason |
|---|---|---|
| K1 | **Per-PR preview environments in Phase 1** → Phase 2 | They need dynamic DNS, on-demand database provisioning, seeding and teardown, and in Phase 1 there is no UI to look at. They would review nothing the end-to-end API tests do not cover. Infrastructure work masquerading as rigour |
| K2 | **The full availability cross-product** | See B1 — the simpler-looking design was the unaffordable one |
| K3 | **`device_id` in the playback request** | Redundant with the token binding and actively harmful (C1) |

Deliberately **not** removed, having been re-examined: the four API surfaces (the partner surface is
reserved, not built, and retrofitting it onto the client surface would bind partner obligations to
the client surface's change rate); ten-plus ADRs before code (each records a decision that would
otherwise be re-argued); and the 17 contexts, with D4 flagged for review at Phase 10 (A4).

---

## New open questions

| # | Question | Needed by |
|---|---|---|
| **OQ-29** | **What licence and copyright apply to this repository itself?** There is no `LICENSE` file. It affects contributor terms, any open-sourcing of shared packages, and what may be included in a distributed client application | Phase 1 |
| **OQ-30** | **Does telemetry ever feed licensor reporting?** If yes, T13's data-poisoning exposure moves from "corrupts our metrics" to "corrupts contractual numbers", and reporting must be derived exclusively from server-side decision records | Phase 4 |

---

## Consistency check

After the corrections, the following are aligned across all documents:

- [x] 17 contexts / 16 modules / 16 schemas, with the difference stated as intentional
- [x] Key access described identically in all ten places it appears
- [x] `playback-authorizer` database grants match what the authorization sequence actually needs
- [x] Concurrency degradation matches the two sources of concurrency limits
- [x] Cache directives match the personalisation rules
- [x] One identifier type, everywhere, including examples
- [x] Fallback axes in ADR-0005 match the packaging and device-matrix documents
- [x] TLS position matches the Smart TV constraints
- [x] Every alert added has a corresponding failure mode documented
- [x] Every disaster-recovery asset has a stated loss profile
- [x] All internal links resolve

**The architecture is internally consistent as of this review.** Nothing in it has been implemented,
and the ⛔ open questions in [`../../ARCHITECTURE.md`](../../ARCHITECTURE.md) still gate Phase 1.
