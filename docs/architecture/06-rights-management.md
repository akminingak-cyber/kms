# Rights Management Architecture

## 1. Rights are not DRM

These are routinely conflated, and the confusion produces platforms that cannot answer a licensor's
questions.

| | **Rights** | **DRM** |
|---|---|---|
| Nature | Contractual | Technical |
| Question answered | *May this content be shown here, now, on this kind of device, in this business model?* | *Can this device decrypt this stream, and under what output constraints?* |
| Source of truth | Licence agreements with content owners | Content keys and licence policies |
| Owner | D1 Rights & Availability | D3 Content Protection |
| Failure mode | Contract breach, financial penalty, loss of content deal | Content leakage |
| Changes | When contracts are signed or renegotiated | When keys rotate or policy changes |

**DRM enforces some of what Rights decides. It cannot enforce most of it.** DRM has no concept of a
licence window, a territory, or a monetization model. A platform that relies on DRM for rights
compliance is not compliant — it is merely encrypted.

Rights is also distinct from **Entitlements** (C3), which answer *"what did this customer buy?"*
The access decision is the conjunction of all of them:

```
CAN PLAY  =  Rights allow it            (contractual — D1)
          ∧  Entitlement grants it       (commercial — C3)
          ∧  Parental level permits it   (household — A2)
          ∧  Device/concurrency permits  (plan + licensor limits — A3, D2)
          ∧  Territory matches           (contractual + technical — D1, D2)
```

Any single false → deny, with a **specific** reason code. Generic "not available" errors make
support impossible and hide bugs; see [`docs/api/conventions.md`](../api/conventions.md).

## 2. The rights model

### Core concepts

| Concept | Meaning |
|---|---|
| **Licence Agreement** | The contract with a rights holder. Reference, counterparty, term, reporting obligations, financial terms (reference only — finance is not in this system) |
| **Right** | One grant within an agreement: a specific subject may be exploited in a specific way |
| **Subject** | What the right applies to: a title, a series, a season, a collection, a channel, or a programme on a channel |
| **Exploitation** | *How* it may be used: `live`, `restart`, `catchup`, `vod`, `download`, `preview` |
| **Monetization** | `svod`, `tvod`, `est`, `avod`, `fvod` |
| **Window** | Validity in time: start, end, and whether it is absolute or relative to broadcast (catch-up windows are usually "N hours after transmission") |
| **Territory Rule** | Included/excluded territories, evaluated as an ordered ruleset, not a flat list |
| **Platform Rule** | Permitted device classes and platforms |
| **Usage Rule** | Technical constraints the licensor requires: max resolution, HDCP level, output protection, security level, concurrency cap, download retention |
| **Blackout** | A time-boxed prohibition overriding an otherwise valid right, scoped by territory and/or channel — most common in sport |
| **Availability** | The **computed** projection, keyed on **subject × exploitation**, carrying resolved intervals plus interned territory/usage rule sets and platform/monetization masks. See [ADR-0011](adr/ADR-0011-availability-projection-shape.md) — a full cross-product of all five dimensions does not scale |

### Why availability is computed and materialised

Evaluating raw rights rules on the playback hot path would mean joining agreements, windows,
territory rulesets, platform rules and blackouts per request — expensive, and worse, non-repeatable:
the answer changes as data is edited, so an incident cannot be reconstructed.

Instead: **`Availability` is a materialised projection**, recomputed when any input changes, with a
version number. Playback authorization reads the projection and records **which availability version
and which rule identifiers** produced the decision.

That last point is the one that matters commercially. When a licensor asks *"why was our film
playable in Territory X on 12 March?"*, the answer must be a record, not a re-derivation from
today's data.

### But not as a full cross-product

Materialising `subject × exploitation × platform × territory × monetization` would produce hundreds
of millions of rows, and — more damagingly — would make recompute proportional to catalog size rather
than to the size of the change. A licensor-wide territory correction is exactly the change that must
take effect in seconds, and under a full cross-product it would take hours.

The projection is therefore **factorised**: keyed on `subject × exploitation`, carrying interned
territory and usage rule sets plus platform and monetization bitmasks, evaluated at read time in
microseconds. **Blackouts are not projected at all** — they are evaluated against a small hot set,
because they are few, urgent, and often applied minutes before taking effect.

Full reasoning and structure: [ADR-0011](adr/ADR-0011-availability-projection-shape.md).

### Precedence

Evaluated in this order; the first prohibition wins:

1. **Blackout** — always overrides
2. **Explicit territory exclusion**
3. **Platform/device-class exclusion**
4. **Window** (not yet started / already ended)
5. **Exploitation not granted**
6. **Monetization not granted**
7. Otherwise: allowed, with the **most restrictive** usage rules from all applicable rights

Usage rules combine restrictively: if any applicable right caps resolution at 720p, the cap is 720p.
Never take the maximum.

## 3. How rights flow through the system

```
   Licence agreement (paper)
            │  entered by a rights manager in the Control Center,
            │  4-eyes approval, fully audited
            ▼
   ┌────────────────────┐
   │ D1 Rights          │  Right + Window + Territory/Platform/Usage rules + Blackouts
   └─────────┬──────────┘
             │  recompute on change
             ▼
   ┌────────────────────┐
   │ Availability       │  materialised, versioned, time-indexed projection
   │ projection         │
   └───┬──────────┬─────┘
       │          │
       │          └──────────────────────────────┐
       ▼                                         ▼
┌──────────────┐                        ┌──────────────────┐
│B4 Discovery  │  what may be *shown*   │D2 Playback Auth  │  what may be *played*
│              │  (hide vs. show-       │                  │  + usage rules →
│              │   as-unavailable is    │                  │  licence policy
│              │   an editorial choice) │                  │
└──────────────┘                        └────────┬─────────┘
                                                 │ usage rules
                                                 ▼
                                        ┌──────────────────┐
                                        │D3 Content        │  DRM licence policy:
                                        │  Protection      │  security level, HDCP,
                                        └──────────────────┘  max res, duration
                                                 │
                                                 ▼
                                        ┌──────────────────┐
                                        │E2 Analytics      │  usage reports to licensors
                                        └──────────────────┘
```

Four consumers, one source. Note that **licensor reporting is a first-class output**, not a
by-product: most content agreements carry reporting obligations, and reports that cannot be
reconciled with the rights that were in force are a contractual problem.

## 4. Catch-up and restart are rights, not features

A frequent and costly mistake is treating catch-up and restart as pure engineering capabilities. They
are separately licensed exploitations, typically with their own windows and often with different
territory or platform scope than the live broadcast.

Consequences the architecture must respect:

- A programme may be live-viewable but **not** restartable, or restartable but not catch-up-able.
- Catch-up windows are usually **relative to transmission** ("7 days after broadcast"), so
  availability must be computed per broadcast event, not per title.
- Rights may **change during a broadcast** (a blackout starting mid-event). Playback authorization
  is therefore re-evaluated on heartbeat, not only at start — see
  [`docs/security/playback-authorization.md`](../security/playback-authorization.md).
- Some content must be **excluded from the recording buffer entirely**, not merely hidden. If the
  right to record does not exist, keeping the bytes is itself a breach. This affects the nDVR
  design — see [`docs/streaming/catchup-restart-npvr.md`](../streaming/catchup-restart-npvr.md).

## 5. Auditability requirements

These are architectural requirements, not nice-to-haves:

1. **Every rights change is versioned and attributed.** Who changed what, when, from what to what,
   under which agreement, approved by whom. Rights data is append-only with effective-dating; rows
   are never destructively updated.
2. **Every playback decision records its inputs**: availability version, rule identifiers,
   entitlement grant identifiers, territory determined and how, device class, timestamp.
3. **Decisions are reproducible.** Given a recorded decision, replaying it against the recorded
   versions must yield the same result. This is a testable property and will be tested.
4. **Retention of decision records is a contractual parameter**, not an engineering preference —
   typically longer than analytics retention (**OQ-19**).

## 6. Operational realities to design for

- **Rights data arrives late, incomplete and in spreadsheets.** The Control Center needs bulk import
  with validation, dry-run preview, and a clear diff before commit. Assume the input is messy.
- **Rights expire silently.** Expiry is not an event a provider sends; it is time passing. The system
  needs proactive "expiring in N days" reporting so content is not pulled from under viewers
  unexpectedly, and so renewals are negotiated in time.
- **Content is often ingested before its rights are entered.** The pipeline must support content
  existing with no rights — invisible and unplayable by default. **Default deny is the invariant**:
  absence of a right is a prohibition, never a permission.
- **Territory determination is imperfect.** IP geolocation is probabilistic, VPN use is common, and
  the contractual definition of "where the user is" may differ from where the packet came from
  (billing address, SIM country). The determination method is recorded with every decision, and the
  rules for which signal wins are a **commercial decision** (**OQ-20**), not an engineering default.
