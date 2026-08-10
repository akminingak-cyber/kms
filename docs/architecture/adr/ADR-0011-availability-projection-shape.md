# ADR-0011: Factorised availability projection

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture
**Supersedes the projection shape described in** `06-rights-management.md` **as first written**

## Context

[`../06-rights-management.md`](../06-rights-management.md) established that rights must be
**materialised** into an `Availability` projection rather than evaluated per request, so that the
playback hot path reads a precomputed answer and every decision can name the version it used.

That is right. But the projection was specified as a full cross-product:

> for a **subject × exploitation × platform × territory × monetization**, the intervals in which it
> is available

Taken literally, that does not scale, and the arithmetic is not marginal:

| Dimension | Realistic cardinality |
|---|---|
| Subjects (titles, episodes, channels, programmes in the active window) | ~50,000 |
| Exploitations (`live`, `restart`, `catchup`, `vod`, `download`, `preview`) | 6 |
| Device classes / platforms | ~7 |
| Territories | ~50 |
| Monetization models | 5 |

**50,000 × 6 × 7 × 50 × 5 ≈ 525 million rows**, before counting multiple intervals per combination.

Row count is the smaller half of the problem. The larger half is **recompute cost**. Rights changes
are rarely narrow: a licensor adds a territory exclusion across their whole catalog, or a platform
restriction is applied to an entire agreement. Under a full cross-product, one such edit invalidates
tens of millions of rows, so the recompute is proportional to **catalog size** rather than to the
size of the change. That makes projection lag unbounded exactly when it is most dangerous —
a rights correction is precisely the change that must take effect quickly, because until it does the
platform is serving content it is not licensed to serve.

## Options considered

**A. Full cross-product, as originally written.** Simplest possible read: one lookup, no evaluation.
Unworkable write side, as above.

**B. No materialisation — evaluate rules per request.** Cheap writes, but it puts an agreement join
on the hot path and, worse, makes decisions non-reproducible: replaying a recorded decision against
"the rules" gives today's answer, not the answer that was given. That defeats
[`../06-rights-management.md`](../06-rights-management.md) §5.

**C. Factorise.** Materialise only the dimensions that are high-cardinality and expensive to derive;
keep the low-cardinality dimensions as compact, **interned** structures evaluated at read time in
microseconds.

## Decision

**Option C.** The projection is keyed on **subject × exploitation** only. The remaining three
dimensions live on the row as compact values:

```
rights.availability
  PK: (subject_ref, exploitation, interval_start)
  ├─ interval_start, interval_end          -- resolved, including transmission-relative windows
  ├─ territory_rule_set_id   ─┐
  ├─ usage_rule_set_id       ─┤  interned: FK into small, shared, immutable tables
  ├─ platform_mask            │  bitmask over device classes
  ├─ monetization_mask        │  bitmask over monetization models
  ├─ availability_version    ─┘
  └─ source_rule_ids[]                     -- what produced this row, for audit
```

Row count becomes `subjects × exploitations × intervals` — **low millions, not hundreds of
millions** — and a lookup is one indexed read plus two mask tests and a small, highly cacheable rule-set
resolution.

### Interning is the part that matters

`territory_rule_set` and `usage_rule_set` are **interned**: identical rule sets are stored once and
shared by every availability row that uses them. There are hundreds of distinct rule sets in a real
platform, not millions, because rights are written per agreement and reused across everything in
that agreement.

The consequence is the point of this ADR: **an agreement-level change updates one interned rule-set
row, not millions of projection rows.** Recompute becomes proportional to what actually changed.
A licensor-wide territory correction propagates in one write.

Rule sets are **immutable and versioned** — a change creates a new rule set and repoints the
affected rows, which is what preserves the ability to replay a historical decision exactly.

### Recompute rules

1. **Incremental, never wholesale.** A rights change enqueues recompute for the affected subjects
   only. Full rebuild exists as a recovery tool and is expected to be slow.
2. **Prioritised by proximity to air.** Live and near-term content recomputes first; a change to a
   title airing in three months can wait behind one airing in ten minutes.
3. **Transmission-relative windows resolve on schedule events.** A catch-up window of "7 days after
   broadcast" materialises when the broadcast's as-run boundaries are known, not when the right is
   entered ([`../../streaming/catchup-restart-npvr.md`](../../streaming/catchup-restart-npvr.md)).
4. **Projection lag is an SLO with an alert**, not a background detail. Lag means the platform is
   enforcing yesterday's contracts.
5. **Blackouts are not projected.** They are evaluated at read time against a small, hot, in-memory
   set. Blackouts are few, urgent, and frequently applied minutes before they take effect — pushing
   one through a projection pipeline is the one place where materialisation is exactly the wrong
   tool.

### What does not change

- Availability remains **versioned**, and every decision records the version it used.
- Decisions remain **reproducible**: rule sets are immutable, so replaying against the recorded
  version and rule-set ids reproduces the original answer.
- **Default deny** still holds: no row means prohibited.

## Consequences

**Accepted costs**
- The read is no longer a single value lookup; it is a lookup plus mask tests plus a cached rule-set
  resolution. Measured in microseconds, and it stays inside the p99 ≤ 150 ms budget with room to
  spare — but it is no longer "free", and the evaluation code is now on the hot path and must be
  tested as such.
- Rule-set interning adds machinery: hashing, deduplication, and a garbage-collection story for
  rule sets no longer referenced.
- Two representations of a territory rule now exist — the authored form in `rights.territory_rules`
  and the interned form. They must be kept consistent by construction, i.e. the interned form is
  *derived*, never edited.

**Made easier**
- Recompute is proportional to the change, so a broad rights correction propagates in seconds rather
  than hours. This is the property that makes rights corrections operationally safe.
- The projection fits comfortably in cache, so the hot path rarely touches disk.
- Adding a dimension later (a new monetization model, a new device class) is a mask widening, not a
  table multiplication.

**Revisit when**
- Territory or platform cardinality grows by an order of magnitude, at which point masks may need to
  become rule-set references too.
- Measurement shows read-time evaluation, rather than projection lag, is the binding constraint.
