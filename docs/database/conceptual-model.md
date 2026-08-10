# Conceptual Data Model

This is a **conceptual** model: the aggregates each context owns and the relationships that matter
architecturally. It is not a schema. Column-level design happens in the phase that builds each
context, against real requirements.

Its purpose is to make the shape of the domain reviewable now, and to fix the cross-context reference
directions before any table exists — because those directions are what determine whether contexts stay
separable.

Notation: `→` reference by identifier across a context boundary (no foreign key);
`──` relationship within a context (foreign key allowed).

---

## Customer

```
identity.accounts
  ├── identity.credentials            (password, provider identities)
  ├── identity.sessions ── identity.refresh_tokens   (rotating family, reuse detection)
  └── identity.account_status_history (append-only)

profile.profiles → identity.accounts
  ├── profile.preferences             (language, subtitles, audio, accessibility)
  ├── profile.parental_settings       (max rating, PIN)
  ├── profile.watchlist_items         → catalog.titles
  └── profile.resume_points           → catalog.titles / schedule.programmes

device.devices → identity.accounts
  ├── device.registrations            (device class, fingerprint, first/last seen)
  └── device.activation_codes         (TV pairing: short-lived, single-use)
```

Notes:
- An account is the **security and billing** subject; a profile is the **viewing** subject. All
  viewing state hangs off `profile`, never `account`. Retrofitting this is close to impossible once
  history exists.
- `refresh_tokens` model a **family** so reuse of a rotated token can be detected and the whole
  family revoked — see [`../security/identity-and-access.md`](../security/identity-and-access.md).

## Content

```
catalog.titles                        (movie | series | season | episode — one table, typed)
  ├── catalog.title_relations         (series → season → episode)
  ├── catalog.translations            (per locale: name, synopsis, tagline)
  ├── catalog.images                  → media.assets
  ├── catalog.credits ── catalog.people
  ├── catalog.genres  (many-to-many)
  └── catalog.certifications          (age rating per rating system per territory)

schedule.channels
  ├── schedule.lineups ── schedule.lineup_entries  (ordering per market/platform)
  ├── schedule.programmes             (broadcast events: channel, start, end, title ref)
  │     → catalog.titles              (nullable — not every broadcast maps to a catalog title)
  └── schedule.revisions              (append-only ingest history, provider payload retained)

media.assets                          (logical media item)
  ├── media.mezzanines                (source files, checksums, storage location)
  ├── media.renditions                (per-ladder-rung outputs)
  ├── media.tracks                    (audio, subtitle, caption — language, role, forced)
  ├── media.packages                  (HLS/DASH outputs, encryption scheme, key id ref)
  └── media.processing_jobs           (state machine, attempts, failure reasons)

discovery.pages ── discovery.rails ── discovery.rail_items
  → catalog.titles, schedule.channels     (projection; rebuildable)
discovery.search_documents               (projection; rebuildable)
```

Notes:
- `catalog` holds **no URL and no bitrate**. `media` holds no editorial text. A `catalog.title`
  points at `media.assets`; the reverse pointer does not exist, so media can be reprocessed without
  touching editorial data.
- `schedule.programmes` is partitioned by `starts_at`; it is the largest control-plane table and is
  almost always queried by time range.
- `schedule.revisions` retains the raw provider payload. Schedule providers issue corrections, and
  without the original payload a mis-mapping cannot be diagnosed or reprocessed.
- `discovery.*` is entirely derived. Losing it is a rebuild, never a data loss.

## Commercial

```
product.products ── product.plans ── product.prices    (currency × territory × period)
  ├── product.packages ── product.package_contents     → catalog.titles, schedule.channels
  ├── product.promotions
  └── product.vouchers

billing.subscriptions → identity.accounts, product.plans
  ├── billing.subscription_periods    (append-only; one row per cycle)
  ├── billing.invoices ── billing.invoice_lines
  ├── billing.payments ── billing.payment_attempts     (idempotency key, provider ref)
  ├── billing.refunds
  ├── billing.dunning_steps
  └── billing.provider_events         (raw webhooks: immutable, signature-verified)

entitlement.grants → identity.accounts
  · source: subscription | purchase | voucher | promotion | manual
  · source_ref, valid_from, valid_until, scope (package | title | channel)
entitlement.snapshots → identity.accounts
  · materialised effective entitlement set + computed_at + version
```

Notes:
- `subscription_periods` is append-only. Mutating a current-period row destroys the history needed
  for proration, refunds and disputes.
- `provider_events` stores the raw webhook before any interpretation — the difference between
  "we can explain this charge" and "we cannot".
- `entitlement.snapshots` is what the playback hot path reads. It is derived, versioned, and carries
  `computed_at` so staleness is measurable and alertable.
- Every grant records **why it exists**. Six months later, "why can this account watch this?" must be
  answerable from a row, not from reasoning.

## Rights

```
rights.agreements                     (counterparty, reference, term, reporting obligations)
  └── rights.rights                   (one grant within an agreement)
        ├── subject: title | series | collection | channel | programme
        ├── exploitation: live | restart | catchup | vod | download | preview
        ├── monetization: svod | tvod | est | avod | fvod
        ├── rights.windows            (absolute, or relative to transmission)
        ├── rights.territory_rules    (ordered include/exclude ruleset)
        ├── rights.platform_rules     (device classes, platforms)
        └── rights.usage_rules        (max resolution, HDCP, security level,
                                       concurrency cap, download retention)
rights.blackouts                      (time-boxed prohibition; territory and/or channel scoped)

rights.availability                   ★ materialised projection, versioned
  · subject × exploitation × platform × territory × monetization → intervals
  · availability_version, computed_at, source rule ids
```

Notes:
- All rights tables are **effective-dated and append-only**. A right is never updated in place;
  a correction creates a new version. Licensor disputes are about what was true on a date, not what
  is true now.
- `rights.availability` is the only rights table the hot path reads.
- **Absence of a row means prohibited.** Default deny is a database-level invariant, not application
  logic.

## Playback and protection

```
playback.sessions → profile.profiles, device.devices
  · content ref, started_at, last_heartbeat_at, ended_at, end_reason
playback.decisions                    ★ append-only audit
  · decision_id, outcome, reason_code
  · availability_version, rights rule ids, entitlement grant ids
  · territory + determination method, device class, client + version
playback.concurrency_slots            (durable mirror of the Redis counter)

protection.content_keys               (key id, wrapped key ref in vault — NEVER the key itself)
protection.key_rotations
protection.licence_policies           (derived from rights.usage_rules)
protection.licence_requests           (audit: who asked for what, when, and the outcome)
```

Notes:
- `playback.decisions` is the compliance record. Partitioned by time, retained per contractual
  requirement (**OQ-19**), and **never deleted on a whim**.
- `protection.content_keys` stores a **reference** to vault material. If a key value can be selected
  out of PostgreSQL, the design has failed.

## Platform

```
admin.staff_users ── admin.roles ── admin.permissions
admin.audit_log                       (append-only, partitioned; every admin and support action)
admin.approvals                       (4-eyes workflow for rights and pricing changes)

notification.templates ── notification.deliveries (state, provider ref)
notification.preferences → identity.accounts       (consent, channel opt-in)

shared.outbox                         (transactional outbox for domain events)
```

---

## Cross-context reference directions

The directions below are deliberate. Reversing any of them would couple a fast-changing context to a
slow-changing one, or put a control-plane dependency on the playback hot path.

| From | To | Why this direction |
|---|---|---|
| `profile` → `identity` | Profiles belong to an account | Identity must not know about viewing |
| `entitlement` → `billing` | Entitlements derive from billing facts | **Billing never reads entitlements**; if it did, the hot path would inherit a billing dependency |
| `playback` → `entitlement`, `rights` | The decision reads both | Neither knows playback exists |
| `discovery` → everything | It is a projection | Nothing reads from discovery except clients |
| `catalog` → `media` | Editorial points at media | Media can be reprocessed without touching editorial |
| `schedule` → `catalog` (nullable) | Broadcasts may map to a title | Not every broadcast has one; the nullability is the point |
| `rights` → subjects | Rights annotate content | Content does not know its own rights; only the availability projection joins them |
