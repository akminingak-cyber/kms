# Bounded Contexts

A bounded context is a boundary inside which one model of the business holds, with one owner and one
vocabulary. Contexts are **logical**; they are not automatically services, databases, or teams. How
they map to deployment units is decided separately in
[`04-service-boundaries.md`](04-service-boundaries.md) — conflating the two is the classic way to
end up with a distributed monolith.

KMS TV has **17 contexts** in five groups.

Two of them have no module in `core-api` and no schema in the transactional database, deliberately:
**E2 Analytics & Telemetry** lives entirely in `telemetry-collector` and the analytics store
([ADR-0003](adr/ADR-0003-postgresql-system-of-record.md) §5), and **D4 Delivery Control** is the
thinnest context in the map — see the note at the end of Group D. So: **17 contexts, 16 modules,
16 schemas.** If those three numbers ever agree, something has been merged or added without a
decision.

---

## Group A — Customer

### A1. Identity & Access
**Owns:** account, credential, authentication session, refresh token lineage, MFA, password reset,
account lifecycle (active/suspended/closed), staff identity.
**Language:** *Account*, *Credential*, *Session*, *Token Family*, *Login Attempt*.
**Does not own:** what the account can watch (Entitlements), who is watching (Profiles).
**Key rule:** an Account is the billing and security subject. It is never the viewing subject.

### A2. Profiles & Personalization
**Owns:** profile, avatar, profile-level parental rating limit, PIN, language and subtitle
preferences, watchlist, resume points ("continue watching"), viewing history for personal features.
**Language:** *Profile*, *Watchlist Item*, *Resume Point*, *Parental Level*.
**Key rule:** all viewing state is per-profile, never per-account. Getting this wrong is very
expensive to correct later, because history and resume data are hard to re-attribute.

### A3. Device Management
**Owns:** device registration, device identity and fingerprint, device class (web / mobile / tablet /
Android TV / Tizen / webOS / STB), device naming, device limits per plan, device removal,
TV activation (pairing code) flows.
**Language:** *Device*, *Device Class*, *Registration*, *Activation Code*, *Device Slot*.
**Key rule:** device class is an input to rights and DRM decisions (max resolution, output
protection, security level), so it is authoritative platform data, not a UI label.

---

## Group B — Content

### B1. Catalog
**Owns:** the editorial description of on-demand content — Movie, Series, Season, Episode,
Collection; titles, synopses, credits, genres, images, age ratings, trailers; per-locale
translations.
**Language:** *Title*, *Series*, *Season*, *Episode*, *Collection*, *Artwork*, *Certification*.
**Does not own:** whether it can be played (Rights), whether the user may play it (Entitlements),
the video files themselves (Media Assets).

### B2. Channels & Schedule (EPG)
**Owns:** channel, channel lineup/ordering, schedule ingest, programme (broadcast event),
now/next, series-link data for broadcast, schedule change reconciliation.
**Language:** *Channel*, *Lineup*, *Programme*, *Broadcast Event*, *Schedule Revision*.
**Key rule:** schedules change after publication and providers correct history. The model must be
revision-aware, not last-write-wins, or catch-up recordings will silently point at the wrong content.

### B3. Media Assets & Processing
**Owns:** the physical media supply chain — mezzanine files, encoding profiles/ladders, renditions,
audio and subtitle tracks, packaging outputs, storage locations, checksums, processing job state,
QC results.
**Language:** *Asset*, *Mezzanine*, *Rendition*, *Ladder*, *Package*, *Track*, *Processing Job*.
**Key rule:** this context is the only one that knows file paths, bitrates and codecs. Catalog
must never store a URL.

### B4. Discovery & Merchandising
**Owns:** how content is presented — rails/rows, curated shelves, page layouts per platform,
editorial ordering, search index projection, badges ("New", "Leaving soon").
**Language:** *Page*, *Rail*, *Shelf*, *Slot*, *Ranking*, *Search Document*.
**Key rule:** this is a read-model context. It owns no truth; it projects Catalog + Rights +
Entitlements into what a specific device should render. It exists separately because TV UIs need
server-driven layout, and baking that into Catalog would corrupt the editorial model.

---

## Group C — Commercial

### C1. Product & Packaging
**Owns:** the sellable catalogue — Product, Package (channel/content bundle), Plan (price + billing
period + device/quality limits), price points per currency and territory, promotions, vouchers,
trials.
**Language:** *Product*, *Package*, *Plan*, *Price*, *Promotion*, *Voucher*, *Trial*.

### C2. Subscriptions & Billing
**Owns:** subscription lifecycle (trial → active → past-due → grace → cancelled → expired),
renewal scheduling, invoices, payment attempts, refunds, dunning, proration, tax treatment,
provider references.
**Language:** *Subscription*, *Billing Cycle*, *Invoice*, *Payment*, *Dunning Step*, *Refund*.
**Key rule:** this context integrates with the PSP but must remain the system of record for
subscription state. Provider webhooks are inputs to a state machine, never the state itself.

### C3. Entitlements
**Owns:** the derived answer to *"what does this account currently have access to?"* — grants from
subscriptions, one-off purchases (TVOD), vouchers, and their validity windows.
**Language:** *Grant*, *Entitlement Set*, *Validity Window*, *Source*.
**Key rule:** entitlements are **derived and cached, but explicitly materialised** — never
recomputed from scratch on the playback hot path. Every grant records its source so any decision can
be explained months later during a dispute.

---

## Group D — Access & Playback

### D1. Rights & Availability
**Owns:** contractual reality — licence agreements, windows (start/end), territories, permitted
platforms and device classes, permitted distribution modes (live / restart / catch-up / VOD /
download), monetization models (SVOD/TVOD/AVOD/FVOD), blackouts, maximum resolution, required output
protection, concurrency caps imposed by licensors, and reporting obligations.
**Language:** *Licence*, *Right*, *Availability Window*, *Territory*, *Blackout*, *Usage Rule*.
**This is the context most platforms model too late and too shallowly.** See
[`06-rights-management.md`](06-rights-management.md).

### D2. Playback Authorization
**Owns:** the decision. Given (profile, device, content, moment, network location), may playback
start, in what form, and with what tokens? Owns playback sessions, concurrency accounting,
heartbeats, and the decision audit log.
**Language:** *Play Request*, *Playback Session*, *Decision*, *Concurrency Slot*, *Heartbeat*,
*Playback Token*.
**Key rule:** it is a pure policy decision point. It reads from Entitlements, Rights, Profiles,
Devices — it owns none of them.

### D3. Content Protection (DRM)
**Owns:** content keys and their lifecycle, key rotation, DRM system registration, licence policy
templates (security level, HDCP, licence duration, offline rules), the licence proxy.
**Language:** *Content Key*, *Key ID*, *Key Rotation*, *Licence Policy*, *Licence Request*.
**Key rule:** the only context permitted to *resolve* key material by identifier. B3 Media Assets
also handles keys during packaging, but only ones pushed to it per job and never queryable — see
[`docs/security/secrets-and-key-management.md`](../security/secrets-and-key-management.md) §3.
Isolated in deployment and in credentials.

### D4. Delivery Control
**Owns:** the abstraction over origin and CDN — signed URL / token generation, CDN selection,
purge, edge configuration references, delivery health signals.
**Language:** *Delivery Target*, *Edge Token*, *Origin*, *Purge Request*, *CDN Adapter*.
**Key rule:** no other context knows a CDN vendor's name. See
[`adr/ADR-0008-cdn-and-origin-abstraction.md`](adr/ADR-0008-cdn-and-origin-abstraction.md).

> **Note — D4 is the thinnest context here, and that is worth admitting.** It owns a port, a token
> format and a small amount of configuration; it has almost no domain model of its own. It is kept
> separate because the *boundary* is what matters (nothing else may know a vendor's name), not
> because it is a rich domain. **If by Phase 10 it has not grown its own model — CDN health, cost
> signals, steering policy — it should be collapsed into D2 as a port and this context retired.**
> Reviewed at the Phase 10 gate.

---

## Group E — Platform

### E1. Administration & Audit
**Owns:** staff accounts, roles, permissions, the Control Center's own model, immutable audit trail
of administrative and support actions, approval workflows (4-eyes for rights and pricing).

### E2. Analytics & Telemetry
**Owns:** playback QoE events, business events, aggregation, reporting datasets, licensor usage
reports.
**Key rule:** never a synchronous dependency of anything in Group D. It receives, it is not asked.

### E3. Notifications
**Owns:** transactional email, push, SMS; templates, locales, delivery state, preferences and
consent.

---

## Context map

Arrows show direction of dependency (`A → B` = A depends on B). `[ACL]` marks an
anti-corruption layer, `[OHS]` an open host service with a published contract.

```
                       ┌───────────────────────────────────────────┐
                       │            CLIENT APPLICATIONS            │
                       │  web · android · androidtv · ios · tizen  │
                       │                 · webos                   │
                       └──────┬─────────────────────┬──────────────┘
                              │                     │
                  discovery + browse          play request
                              │                     │
                              ▼                     ▼
             ┌────────────────────────┐   ┌──────────────────────────┐
             │ B4 Discovery &         │   │ D2 Playback              │
             │    Merchandising [OHS] │   │    Authorization  [OHS]  │
             └───┬───────┬────────┬───┘   └──┬───┬────┬────┬────┬────┘
                 │       │        │          │   │    │    │    │
      ┌──────────┘       │        └───────┐  │   │    │    │    └────────┐
      ▼                  ▼                ▼  ▼   │    ▼    ▼             ▼
 ┌──────────┐    ┌──────────────┐   ┌──────────┐ │ ┌──────────┐   ┌────────────┐
 │B1 Catalog│    │B2 Channels & │   │C3 Entit- │ │ │A2 Profil-│   │D4 Delivery │
 │          │    │   EPG        │   │  lements │ │ │   es     │   │   Control  │
 └────┬─────┘    └──────┬───────┘   └────┬─────┘ │ └──────────┘   └─────┬──────┘
      │                 │                │       │                     │
      │                 │                ▼       ▼                     │[ACL]
      │                 │         ┌──────────┐ ┌──────────┐            ▼
      │                 │         │C2 Subs & │ │A3 Device │      ┌───────────┐
      │                 │         │  Billing │ │  Mgmt    │      │  CDN(s)   │
      │                 │         └────┬─────┘ └──────────┘      └───────────┘
      │                 │              │[ACL]
      │                 │              ▼
      │                 │        ┌──────────┐        ┌──────────────┐
      │                 │        │   PSP    │        │D3 Content    │
      │                 │        └──────────┘        │   Protection │
      │                 │                            └──────┬───────┘
      ▼                 ▼                                   │[ACL]
 ┌──────────────────────────────┐                           ▼
 │ D1 Rights & Availability     │◄──── consulted by D2  ┌──────────┐
 │    (upstream to everything   │      and B4           │DRM vendor│
 │     that shows or plays)     │                       └──────────┘
 └──────────────────────────────┘
      ▲
      │ constrains
 ┌────┴─────────┐   ┌──────────────┐   ┌──────────────┐
 │B3 Media      │   │C1 Product &  │   │A1 Identity & │
 │  Assets      │   │   Packaging  │   │   Access     │
 └──────────────┘   └──────────────┘   └──────────────┘

 E1 Administration & Audit ── writes into all control-plane contexts, under audit
 E2 Analytics & Telemetry  ◄─ receives events from all contexts (async, never blocking)
 E3 Notifications          ◄─ receives events from A1, C2, C3
```

### Rules that hold across the map

1. **Rights is upstream of everything that shows or plays content.** If Rights says no, no other
   context may say yes.
2. **Playback Authorization depends on many contexts and is depended upon by none.** It is a leaf in
   the control plane and a root in the decision plane. This is what makes it independently
   deployable and scalable.
3. **Entitlements is the only context Subscriptions talks to about access.** Playback never reads
   subscription state directly; it reads entitlements. This keeps billing changes away from the hot
   path.
4. **Analytics is never synchronous.** No context blocks on it.
5. **Cross-context references are by identifier only.** No foreign keys across context schemas —
   see [`docs/database/README.md`](../database/README.md).
6. **Every external system sits behind an ACL** owned by exactly one context.
