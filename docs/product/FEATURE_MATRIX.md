# FEATURE_MATRIX.md — KMS TV Feature × Platform × Priority Matrix

**Phase:** 1 — Product specification
**Status:** DRAFT — awaiting product approval
**Version:** 1.1
**Date:** 2026-08-13 (rev. 1.1 — PD-004 approved)
**Source:** `PRODUCT_SPEC.md` · `REQUIREMENTS.md`
**Governing document:** `CLAUDE.md` (binding)

---

## 0. How to read this matrix

### 0.1 Cell values

| Symbol | Meaning |
|---|---|
| **●** | **Required** — the feature must exist on this platform at the stated priority |
| **○** | **Reduced** — a deliberately limited form is acceptable on this platform |
| **—** | **Not applicable** — the platform has no meaningful expression of this feature |
| **?** | **Undecided** — depends on an open decision; the referenced `PD-nnn` must be resolved |

### 0.2 Platform columns

| Column | Platform |
|---|---|
| WEB | Browser (desktop and mobile web) |
| AND | Android phone and tablet |
| ATV | Android TV / Google TV |
| iOS | iPhone and iPad |
| TIZ | Samsung Tizen |
| WOS | LG webOS |
| ADM | Admin Control Center (web) |
| BE | Backend (server-side capability) |

### 0.3 Priority

`P0` mandatory for initial production · `P1` important · `P2` later ·
`P3` optional/future. Defined in `REQUIREMENTS.md` §0.2.

### 0.4 The launch-scope caveat

**Which platforms ship at launch is [OPEN — PD-092] and is not decided here.** This matrix
states what each platform must eventually provide and at what priority. A `P0` in a
platform column means *"P0 for that platform when that platform ships"*, not *"that
platform ships first"*.

**[PROPOSED]** launch order — Web → Android → Android TV → iOS → Tizen → webOS — reflects
build cost, certification lead time, and the fact that Tizen and webOS share technology
with the web client and benefit from it settling first. **Requires approval (PD-092).**

---

## 1. Account and identity

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Registration | P0 | ● | ● | ○ | ● | ○ | ○ | — | ● |
| Second-screen / code sign-in | P1 | — | — | ● | — | ● | ● | — | ● |
| Login | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Logout | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Log out everywhere | P0 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Email/identifier verification | P0 | ● | ● | ○ | ● | ○ | ○ | — | ● |
| Password reset | P0 | ● | ● | ○ | ● | ○ | ○ | — | ● |
| Account recovery (manual, audited) | P1 | — | — | — | — | — | — | ● | ● |
| Account status enforcement | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Account deletion | P0 | ● | ● | — | ● | — | — | ● | ● |
| Data export | P1 | ● | ○ | — | ○ | — | — | ● | ● |
| Active session list & termination | P1 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Security event timeline | P1 | ● | ● | — | ● | — | — | ● | ● |
| MFA — administrative accounts | P0 | — | — | — | — | — | — | ● | ● |
| MFA — subscribers | P2 | ● | ● | ○ | ● | ○ | ○ | — | ● |
| Social login | P3 | ? | ? | ? | ? | ? | ? | — | ? |

**Rationale for TV reductions.** Text entry on a TV remote is genuinely hostile. Account
creation, password reset, and deletion are reduced on TV platforms and routed to
second-screen or another device. Login must work everywhere; everything else that needs
typing should not have to.
PD-033 governs social login.

---

## 2. Profiles

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Profile selection | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Profile switching | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Profile creation / editing | P1 | ● | ● | ○ | ● | ○ | ○ | ○ | ● |
| Profile deletion | P1 | ● | ● | — | ● | — | — | ○ | ● |
| Avatar selection | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Per-profile language | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Maturity limit enforcement | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Child profile | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Parental controls | P1 | ● | ● | ○ | ● | ○ | ○ | ○ | ● |
| Profile PIN | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Account PIN (exit child profile) | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Cross-profile history isolation | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Profile limit enforcement | P1 | ● | ● | ● | ● | ● | ● | ● | ● |

Profile limit value is **[OPEN — PD-036]**. Maturity scheme is **[OPEN — PD-035]**
and **[LEGAL]** — no rating system is assumed for any territory.

---

## 3. Devices and sessions

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Device registration | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Idempotent re-registration | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Device class recorded & enforced | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Device limit enforcement | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Device list & removal | P0 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Session revocation effect on playback | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Concurrency enforcement | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Abandoned-session slot release | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Show active sessions on concurrency denial | P1 | ? | ? | ? | ? | ? | ? | — | ? |
| Suspicious-behaviour detection | P1 | — | — | — | — | — | — | ● | ● |
| Device slot cooling-off | P2 | ? | ? | ? | ? | ? | ? | ? | ? |

Device limit **[OPEN — PD-038]**, concurrency limit **[OPEN — PD-040]**, session display
on denial **[OPEN — PD-047]**, cooling-off **[OPEN — PD-039]**.

---

## 4. Home and discovery

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Home screen (entitlement-filtered) | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Continue watching row | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Live now row | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Categories | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Independent section loading | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Skeleton loading (no layout shift) | P1 | ● | ● | ● | ● | ● | ● | — | — |
| Populated empty state for new profiles | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Hero / editorial promotion | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Popular channels row | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Recommended row | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Movies / Series rows | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Recently added row | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Favorites row | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Operator-configurable section order | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Upcoming programmes row | P2 | ● | ● | ● | ● | ● | ● | — | ● |

---

## 5. Live TV

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Channel list (entitlement-filtered) | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Channel groups & categories | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Channel playback | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Channel up/down stepping | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Now/next during playback | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Live edge indicator | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| New authorization per channel change | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Zapping debounce | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Previous channel | P1 | ● | ● | ● | ● | ● | ● | — | — |
| Channel number entry | P1 | ○ | ○ | ● | ○ | ● | ● | — | — |
| Channel search within Live TV | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Favorites filter | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Channel sort preference | P2 | ● | ● | ● | ● | ● | ● | — | ● |

Channel number entry is **required** on TV platforms — it is how people actually change
channels on a television — and reduced elsewhere, where it is a niche affordance.

---

## 6. EPG

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Now/next per channel | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Schedule grid | P0 | ● | ○ | ● | ○ | ● | ● | ● | ● |
| UTC storage, local display | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| DST correctness | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Missing EPG never blocks playback | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Idempotent ingestion | P0 | — | — | — | — | — | — | ● | ● |
| Failed ingest preserves good data | P0 | — | — | — | — | — | — | ● | ● |
| Programme details | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Day navigation | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Gap / overlap detection & review | P1 | — | — | — | — | — | — | ● | ● |
| Freshness monitoring & alerting | P1 | — | — | — | — | — | — | ● | ● |
| Programme search | P2 | ● | ● | ● | ● | ● | ● | ● | ● |

Mobile shows a **reduced** grid (○) — a full channels × time grid is not usable on a phone
and a per-channel or vertical-list presentation is the correct form, not a compromise.

---

## 7. Player

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Adaptive playback | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Volume / mute / fullscreen | P0 | ● | ● | ● | ● | ● | ● | — | — |
| Defined playback states | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Error: message + recovery + code | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| No internals in errors | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| "May not" vs "could not" distinction | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Silent re-authorization on expiry | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Buffering tolerance | P0 | ● | ● | ● | ● | ● | ● | — | — |
| QoE telemetry | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Subtitles | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Audio tracks | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Seek (VOD / catch-up / restart) | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Manual quality selection | P2 | ● | ● | ○ | ● | ○ | ○ | — | — |
| Picture-in-picture | P2 | ? | ● | — | ● | — | — | — | — |
| AirPlay / cast (rights-gated) | P2 | — | ● | — | ● | — | — | — | ● |
| Live pause | P2 | ? | ? | ? | ? | ? | ? | — | ? |

PiP on web **[UNVERIFIED — per-browser]**. Live pause **[OPEN — PD-046]**.
**AirPlay and casting must be gated by rights metadata** — they are distribution, not
a UI convenience.

---

## 8. Playback authorization — backend-dominant

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Backend authorization required for all playback | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| All eleven checks, no skip path | P0 | — | — | — | — | — | — | — | ● |
| Service availability check, distinct from content rights | P0 | — | — | — | — | — | — | ● | ● |
| Client-reported data never authoritative | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Stable machine-readable reason codes | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Short-lived, bound, revocable sessions | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Atomic server-side concurrency | P0 | — | — | — | — | — | — | — | ● |
| Server-side geo-enforcement | P0 | — | — | — | — | — | — | — | ● |
| Signed short-lived delivery URLs | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| **Edge token validation** | P0 | — | — | — | — | — | — | — | ● |
| Compliance-evidence logging | P0 | — | — | — | — | — | — | ● | ● |
| Heartbeat & slot release | P0 | ● | ● | ● | ● | ● | ● | — | ● |

**The dominance of the BE column in this table is the point.** Every client cell here is
"transport the decision and render it," never "make the decision."

---

## 9. VOD, catch-up, restart

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| VOD catalogue browse | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Title detail | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Series / season / episode navigation | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Rights-governed VOD availability | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Resume position (cross-device) | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Next episode / up next | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Categories & genres | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Filters | P1 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Trailers (rights-permitted) | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Watchlist | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| **Catch-up playback** | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Catch-up window configuration | P1 | — | — | — | — | — | — | ● | ● |
| Catch-up rights validation (independent) | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| **Restart TV** | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Restart rights validation (independent) | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Return to live | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Restart failure → fall back to live | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Sorting | P2 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Network recording / DVR | P3 | ? | ? | ? | ? | ? | ? | ? | ? |
| Offline download | P3 | — | ? | — | ? | — | — | ? | ? |

Recording **[OPEN — PD-015]**, download **[OPEN — PD-016]** — both require distribution
rights that are **separate grants** and must not be assumed.

---

## 10. Search, favorites, history, recommendations

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Entitlement-filtered results | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Global search across all types | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Georgian / Cyrillic / Latin support | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Autocomplete | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Typo tolerance | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Helpful no-result state | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Result filters | P2 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| TV on-screen keyboard (all scripts) | P2 | — | — | ● | — | ● | ● | — | — |
| Voice search | P3 | ? | ? | ? | ? | ? | ? | — | ? |
| Cross-script transliteration | P3 | ? | ? | ? | ? | ? | ? | — | ? |
| Favorite channels | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Watchlist | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Favorites sync across devices | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Unentitled favorite retained, marked | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Favorite reordering | P2 | ● | ● | ○ | ● | ○ | ○ | — | ● |
| Watch history (per profile) | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| History deletion (item & bulk) | P0 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| History as sensitive data | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Continue-watching derivation | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Resume conflict resolution | P1 | — | — | — | — | — | — | — | ● |
| History deletion → analytics | P1 | — | — | — | — | — | — | ● | ● |
| Entitlement-safe recommendations | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Deterministic recommendations | P1 | — | — | — | — | — | — | — | ● |
| Per-profile computation | P1 | — | — | — | — | — | — | — | ● |
| Recorded recommendation reason | P1 | — | — | — | — | — | — | ● | ● |
| ML recommendations | P3 | — | — | — | — | — | — | — | ? |

Voice search **[UNVERIFIED]** per platform — availability must be confirmed from official
platform documentation, never assumed. Transliteration **[OPEN — PD-060]**.

---

## 11. Commerce

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Package listing | P0 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Subscribe | P0 | ● | ● | ○ | ● | ○ | ○ | — | ● |
| Immediate entitlement on activation | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Immediate entitlement loss on expiry | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Subscription state machine | P0 | — | — | — | — | — | — | ● | ● |
| Append-only subscription history | P0 | — | — | — | — | — | — | ● | ● |
| Cancel subscription | P0 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Exact monetary arithmetic | P0 | — | — | — | — | — | — | ● | ● |
| **No card data on KMS TV systems** | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Signature-verified callbacks | P0 | — | — | — | — | — | — | — | ● |
| Idempotent callbacks | P0 | — | — | — | — | — | — | — | ● |
| Out-of-order callback handling | P0 | — | — | — | — | — | — | — | ● |
| Payment provider abstraction | P0 | — | — | — | — | — | — | — | ● |
| Upgrade / downgrade | P1 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Payment-failure handling & dunning | P1 | ● | ● | ○ | ● | ○ | ○ | ● | ● |
| Refunds (permission-gated, audited) | P1 | — | — | — | — | — | — | ● | ● |
| Reconciliation | P1 | — | — | — | — | — | — | ● | ● |
| Invoices / receipts | P2 | ? | ? | — | ? | — | — | ● | ? |
| In-app purchase | P2 | — | ? | ? | ? | — | — | — | ? |
| Trials | P3 | ? | ? | ? | ? | ? | ? | ? | ? |
| Promotions | P2 | ? | ? | ? | ? | ? | ? | ? | ? |

Purchase flows are **reduced** on TV — payment entry on a remote is poor and often
restricted by store policy. **[UNVERIFIED]** No store's current policy is assumed;
PD-076 governs.

---

## 12. Rights management — backend and admin only

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Territory configurable, never hard-coded | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| App distribution / service availability / content rights kept separate | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| "Service not available yet" state (not an error) | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Rights metadata on every distributable asset | P0 | — | — | — | — | — | — | ● | ● |
| Answer "why may we serve this?" | P0 | — | — | — | — | — | — | ● | ● |
| **Independent distribution-mode enforcement** | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Automatic activation at window start | P0 | — | — | — | — | — | — | ● | ● |
| **Automatic disabling at window end** | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| **Dual-mechanism expiry enforcement** | P0 | — | — | — | — | — | — | — | ● |
| Immediate revocation | P0 | — | — | — | — | — | — | ● | ● |
| Immediate cache invalidation | P0 | — | — | — | — | — | — | — | ● |
| Rights audit trail | P0 | — | — | — | — | — | — | ● | ● |
| Expiry warnings & dashboard | P1 | — | — | — | — | — | — | ● | ● |
| "Leaving soon" surfacing | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| CDN purge as part of revocation | P0 | — | — | — | — | — | — | ● | ● |

Client cells are marked ● where the client must **honour** the outcome — absent
affordances, absent content, neutral denials — not where it decides anything.

---

## 13. Admin Control Center

| Feature | Pri | ADM | BE |
|---|:--:|:--:|:--:|
| Server-side permission gating on every action | P0 | ● | ● |
| Audit with actor / timestamp / before-after | P0 | ● | ● |
| Append-only, immutable audit log | P0 | ● | ● |
| No credentials, tokens, or card data in any view | P0 | ● | ● |
| No self-escalation by any role | P0 | ● | ● |
| MFA for all admin accounts | P0 | ● | ● |
| Channel management with mandatory rights reference | P0 | ● | ● |
| Rights & contract management | P0 | ● | ● |
| Rights expiration management | P0 | ● | ● |
| Playback session investigation | P0 | ● | ● |
| Device & session administration | P0 | ● | ● |
| EPG ingest management & review | P0 | ● | ● |
| VOD catalogue management | P1 | ● | ● |
| Package management | P1 | ● | ● |
| Subscription & payment administration | P1 | ● | ● |
| Streaming health monitoring | P1 | ● | ● |
| Bulk operations with preview & confirmation | P1 | ● | ● |
| Operations dashboard | P1 | ● | ● |
| Category, asset & artwork management | P2 | ● | ● |
| Notification templates & announcements | P2 | ● | ● |
| Promotions | P2 | ? | ? |
| Support impersonation | P3 | ? | ? |

---

## 14. Cross-cutting quality

| Feature | Pri | WEB | AND | ATV | iOS | TIZ | WOS | ADM | BE |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| No hard-coded strings | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Georgian / English / Russian / Spanish | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Georgian, Cyrillic, Latin rendering | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| UTC computation, local rendering | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Currency always shown with amount | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Per-profile language persistence | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Content metadata localization | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Single visible focus, never lost/trapped | P0 | ● | — | ● | — | ● | ● | ● | — |
| All functions reachable by D-pad | P0 | — | — | ● | — | ● | ● | — | — |
| Full keyboard navigation | P0 | ● | — | — | — | — | — | ● | — |
| Subtitles displayed where supplied | P0 | ● | ● | ● | ● | ● | ● | — | ● |
| Contrast minimums | P1 | ● | ● | ● | ● | ● | ● | ● | — |
| Screen reader support | P1 | ● | ● | ? | ● | ? | ? | ● | — |
| Configurable subtitle appearance | P1 | ● | ● | ● | ● | ● | ● | — | ● |
| Audio description | P2 | ? | ? | ? | ? | ? | ? | — | ? |
| Every error: message + recovery + code | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| No internals leaked in errors | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Neutral rights/territory denials, no workaround hint | P0 | ● | ● | ● | ● | ● | ● | ● | ● |
| Localized error messages | P1 | ● | ● | ● | ● | ● | ● | ● | ● |
| Attribution / notices screen | P0 | ● | ● | ● | ● | ● | ● | ● | — |

TV screen-reader support is **[UNVERIFIED]** — availability differs per platform and must
be confirmed from official documentation during each client phase (`CLAUDE.md` §1).
The attribution screen is a **license obligation** (`CLAUDE.md` §3.2), not a nicety.

---

## 15. Security, privacy, observability — backend-dominant

| Feature | Pri | Clients | ADM | BE |
|---|:--:|:--:|:--:|:--:|
| Deny by default | P0 | — | ● | ● |
| Boundary input validation | P0 | ○ | ● | ● |
| Parameterized queries only | P0 | — | — | ● |
| Memory-hard password hashing | P0 | — | — | ● |
| No secrets in the repository | P0 | ● | ● | ● |
| TLS everywhere including internal | P0 | ● | ● | ● |
| Rate limiting on sensitive endpoints | P0 | — | — | ● |
| Privileged-action audit logging | P0 | — | ● | ● |
| Security headers | P0 | ● | ● | ● |
| **No authentication bypass on any branch** | P0 | ● | ● | ● |
| Bounded revocation interval | P0 | ● | ● | ● |
| Least-privilege service accounts | P0 | — | — | ● |
| CI dependency & container scanning | P0 | ● | ● | ● |
| CSRF / fixation / replay protection | P0 | ● | ● | ● |
| Stated purpose per personal-data field | P0 | — | ● | ● |
| Automatic retention enforcement | P0 | — | ● | ● |
| Tested account deletion incl. analytics & backups | P0 | ● | ● | ● |
| Viewing history as sensitive data | P0 | ● | ● | ● |
| No sensitive data in any log | P0 | ● | ● | ● |
| Stricter child-profile defaults | P0 | ● | ● | ● |
| Third-party SDK review before inclusion | P0 | ● | ● | ● |
| Structured logging with correlation ID | P0 | ○ | ● | ● |
| Health vs. readiness endpoints | P0 | — | — | ● |
| Domain metrics incl. rights expiring in 24h | P0 | — | ● | ● |
| Every alert has an owner and a runbook | P0 | — | ● | ● |
| Client QoE reporting | P0 | ● | — | ● |
| Distributed tracing | P1 | — | — | ● |
| Operational dashboards | P1 | — | ● | ● |

---

## 16. P0 feature count by platform

| Platform | P0 features required | Principal risk |
|---|---:|---|
| Backend (BE) | ~120 | Authorization correctness and rights enforcement — the whole product rests here |
| Admin (ADM) | ~45 | Permission gating, audit immutability, no credential exposure |
| Web (WEB) | ~60 | Broad browser matrix; token storage strategy |
| Android (AND) | ~58 | Device fragmentation; network transitions |
| iOS (iOS) | ~58 | **Toolchain unavailable in the current environment (B-004)** — a procurement blocker, not an engineering one |
| Android TV (ATV) | ~55 | D-pad completeness; memory on constrained devices |
| Tizen (TIZ) | ~53 | Oldest supported model performance; Georgian font coverage; certification lead time |
| webOS (WOS) | ~53 | Dual input models; oldest supported model; certification lead time |

Counts are indicative, derived from the ● cells above. They are a planning signal, not a
commitment, and will firm up once launch scope (PD-092) is decided.

---

## 17. Platform-specific risk register

| Risk | Platform | Impact | Mitigation |
|---|---|---|---|
| **Georgian script font coverage on TV** | TIZ, WOS, ATV | Core UI unreadable in the primary language | Verify font availability on real hardware **during Phase 3**, not Phase 23. Bundle a licensed font if platform coverage is absent — and check that font's license (`CLAUDE.md` §3). |
| **Apple toolchain unavailable** | iOS | Phase 22 cannot begin | Procure macOS hardware or hosted CI. Purchasing lead time — start at Phase 3 (B-004). |
| **Store certification lead time** | TIZ, WOS, iOS, AND | Launch date slips on external timelines | Begin developer registration at Phase 3 (B-006). |
| **Oldest-model performance** | TIZ, WOS, ATV | Budgets met on new hardware, missed on real | Acquire minimum-spec devices early; validate against them, never a development box. |
| **Emulator insufficiency** | TIZ, WOS | Emulator passes, hardware fails | Real-hardware verification is a gate condition for Phases 23–24. |
| **Text entry on TV** | ATV, TIZ, WOS | Registration and payment unusable | Second-screen sign-in **[PROPOSED]**; reduce typing-dependent flows on TV. |
| **Memory constraints** | TIZ, WOS, ATV | EPG grid and long sessions crash | Memory budgets in Phase 32; soak testing on minimum devices. |
| **Device identifier stability** | all clients | Device slots silently consumed | **[UNVERIFIED]** — confirm per-platform behaviour from official documentation in each client phase. |
| **Browser DRM variation** | WEB | Protected playback fails on some browsers | Phase 30; no browser capability assumed until verified. |

---

## Appendix — Legend recap and open dependencies

**● required · ○ reduced · — not applicable · ? undecided**

Every `?` in this matrix traces to a decision in `DECISIONS.md`. The matrix cannot be
finalized until at least the following are resolved:

| Decision | Blocks |
|---|---|
| ~~PD-004~~ | **APPROVED** — Georgia launch, multi-territory architecture, future territories configurable |
| PD-094 | App distribution scope — which territories the app is listed in, distinct from service availability |
| PD-095 | Travelling-subscriber policy — home vs. current territory in authorization |
| PD-092 | Launch platform scope — the column set that matters first |
| PD-005 / PD-006 | Free tier and advertising — several rows exist only if approved |
| PD-015 / PD-016 | Recording and download rows |
| PD-036 / PD-038 / PD-040 | Limit enforcement values |
| PD-046 / PD-047 | Player and concurrency display behaviour |
| PD-035 | Maturity scheme — affects every entitlement-filtered surface |
| PD-076 | In-app purchase — reshapes the commerce rows on mobile and TV |
| PD-008 | Multi-tenancy — architectural; affects every backend row |
