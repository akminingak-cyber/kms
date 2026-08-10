# Privacy and Compliance

> **This document sets engineering requirements, not legal advice.** The applicable regimes depend on
> launch markets (**OQ-1**), which are unresolved. Legal counsel must confirm the obligations; this
> document describes how the platform is built so that meeting them is straightforward rather than a
> retrofit.

## 1. Personal data held

| Category | Examples | Sensitivity |
|---|---|---|
| Account | Email, name, password hash, country | Moderate |
| Payment | **Provider token, brand, last four, expiry only** | High — but out of PCI scope by design |
| Device | Device identifier, class, model, IP, user agent | Moderate |
| **Viewing behaviour** | What was watched, when, how far, on what | **High** — viewing history is revealing personal data, more so than most product data |
| Profiles | Names (often children's), avatars, parental settings | **High** — likely to include children's data |
| Support | Ticket history, notes, act-on-behalf sessions | Moderate |
| Telemetry | QoE, errors, session metrics | Low once pseudonymised |

Two categories deserve particular care and are easy to underestimate: **viewing history**, which can
reveal beliefs, health conditions, sexuality and politics; and **children's data**, since family
profiles routinely identify minors and several regimes impose stricter rules on them.

## 2. Engineering requirements

### Minimisation
Collect only what a named feature needs. New personal data fields require a stated purpose recorded
at review. "It might be useful later" is not a purpose — it is an unbounded liability.

### Purpose limitation
Data collected for playback continuity is not silently repurposed for marketing. Consent, where
required, is per-purpose, recorded with a timestamp and a version of what was consented to, and is
as easy to withdraw as to give.

### Retention
Every category has a defined retention period, enforced **automatically**, not by an annual cleanup
that everyone forgets. See [`../database/scaling-and-retention.md`](../database/scaling-and-retention.md).

### Access control
Least privilege; support tooling surfaces what is needed rather than allowing free-form query; all
access to customer records is logged and reviewable.

### Encryption
In transit everywhere including internal calls; at rest for databases, backups and object storage;
field-level protection for the most sensitive attributes.

### No production data outside production
Absolute. Not anonymised, not "just the catalog", not "for one debugging session". Development and
staging use the seeded dataset ([`../architecture/07-testing-strategy.md`](../architecture/07-testing-strategy.md) §5).
This single rule prevents a large share of real-world personal-data incidents.

## 3. Data subject rights

Where applicable law grants them, these must be **implemented as features**, not performed manually
under time pressure:

| Right | Implementation requirement |
|---|---|
| **Access** | Export everything held about the account, in a portable format, across every store — including telemetry archives |
| **Rectification** | Self-service where possible; audited when done by staff |
| **Erasure** | Delete or pseudonymise across primary stores, read models, caches, search indexes, **backups**, and analytics archives |
| **Portability** | Machine-readable export |
| **Objection / restriction** | Ability to suppress processing for a purpose without deleting the account |
| **Withdraw consent** | As easy as giving it; effective immediately |

**Erasure is the hard one, and it must be designed for from Phase 1.** Personal data spreads into
read models, caches, search indexes, event streams, log archives, backups and analytics stores.
Consequences for the architecture:

- Every store that can hold personal data is **catalogued**, with an owner and an erasure procedure.
  A store nobody remembers is a store that leaks.
- **Events carry identifiers, not personal payloads**, wherever possible — an event stream with names
  and emails baked into historical events is close to un-eraseable.
- Telemetry is **pseudonymised at collection**, so analytics archives hold no direct identifiers and
  erasure means deleting one mapping rather than rewriting petabytes.
- **Backups** are addressed explicitly: either erasure is applied on restore, or retention is short
  enough that the backup ages out within an agreed period — decided with counsel and **documented**,
  not left ambiguous.

### Retention conflicts
An erasure request against a record with a legal retention obligation (financial records, for
example) needs a documented resolution — usually pseudonymisation that preserves the financial record
while removing the personal link. Agreed with legal, implemented as a **specific code path**, and
tested. It must not be improvised at request time.

## 4. Payments and PCI DSS

Design intent: **PCI DSS SAQ A** — card data is captured by provider-hosted fields or the platform's
own payment sheet and never touches KMS TV infrastructure
([ADR-0009](../architecture/adr/ADR-0009-payments-boundary.md)).

Engineering rules that keep it true:

- No PAN, CVV or track data in any system, any log, any error report, any support tool, in any
  environment.
- Only provider tokens plus display metadata are stored.
- **Any change that would cause card data to transit our systems is a scope change** requiring
  security and compliance review before implementation — not a discovery made during an audit.

## 5. Content compliance

- **Age ratings** vary by territory and rating system. A title carries per-territory certifications;
  parental controls enforce against the certification for the viewer's territory. A single global
  rating is wrong in most markets.
- **Accessibility** — subtitles, closed captions, audio description — is a legal requirement in some
  territories and a product requirement everywhere. It is a first-class part of the media model
  (`media.tracks`), not an add-on, and rights must be checked for it too: subtitle and audio-track
  rights are frequently separate grants.
- **Content warnings and editorial obligations** vary by market and are modelled as catalog data
  rather than hardcoded.

## 6. Cookies and tracking

- Strictly necessary cookies only, without consent.
- Analytics and marketing cookies require consent where applicable, with genuine granularity.
- Consent state is stored, versioned and respected across every platform — including TV applications,
  where consent UX is harder and is frequently done badly.

## 7. Vendors and data processing

- Every vendor with access to personal data needs a data processing agreement.
- Cross-border transfer implications are checked per vendor and per market.
- **Vendor selection considers data location**, which can constrain CDN, analytics and PSP choices in
  ways that surprise teams late. It is part of each verification checklist in
  [`../architecture/05-integration-boundaries.md`](../architecture/05-integration-boundaries.md).
- Sub-processors are enumerated and kept current — several regimes require publishing the list.

## 8. Records

Maintained from Phase 1, because reconstructing them later is far harder:

- **Data inventory:** what is held, where, why, for how long, who can access it.
- **Processing register:** purposes, legal bases, categories, recipients.
- **Consent records:** what, when, which version, how withdrawn.
- **Access log:** who accessed customer data and why.
- **Incident log:** including near misses.

Breach notification timelines in several regimes are measured in tens of hours from awareness. Meeting
them requires knowing in advance what data exists and where — which is the inventory's actual purpose,
not documentation for its own sake.
