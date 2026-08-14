# USER_FLOWS.md — KMS TV User and Operator Flows

**Phase:** 1 — Product specification
**Status:** DRAFT — awaiting product approval
**Version:** 1.0
**Date:** 2026-08-13
**Source specification:** `PRODUCT_SPEC.md` · **Requirements:** `REQUIREMENTS.md`
**Governing document:** `CLAUDE.md` (binding)

---

## 0. How to read these flows

Each flow defines: **actor**, **precondition**, **trigger**, the **happy path**,
**alternate paths**, **failure paths**, **postcondition**, and the **requirements** it
exercises.

Flows describe **product behaviour**, not implementation. No screen layout, no endpoint,
no component. Where a flow depends on an unmade decision, the decision is referenced as
`PD-nnn` and the flow states the dependency rather than assuming an answer.

**Convention:** every step that could be denied names the check that denies it, so the
flow doubles as a specification of failure — which, in this product, is where compliance
lives.

### Flow index

| # | Flow | Actor | Priority |
|---|---|---|---|
| UF-01 | Registration | Visitor | P0 |
| UF-02 | Login | Registered user | P0 |
| UF-03 | Password recovery | Registered user | P0 |
| UF-04 | Profile selection | Authenticated user | P0 |
| UF-05 | Add / remove device | Authenticated user | P0 |
| UF-06 | Browse Live TV | Viewer | P0 |
| UF-07 | Watch a channel | Viewer | P0 |
| UF-08 | Open the EPG | Viewer | P0 |
| UF-09 | Switch channel | Viewer | P0 |
| UF-10 | Restart a programme | Viewer | P1 |
| UF-11 | Catch-up playback | Viewer | P1 |
| UF-12 | Search | Viewer | P1 |
| UF-13 | Watch VOD | Viewer | P0 |
| UF-14 | Continue watching | Viewer | P0 |
| UF-15 | Subscribe | Registered user | P0 |
| UF-16 | Cancel subscription | Subscriber | P0 |
| UF-17 | Payment failure | Subscriber | P0 |
| UF-18 | Playback authorization failure | Viewer | P0 |
| UF-19 | Rights expiration | Viewer + system | P0 |
| UF-20 | Device-limit failure | Viewer | P0 |
| UF-21 | Admin — channel creation | Content Manager | P0 |
| UF-22 | Admin — EPG import | Content Manager | P0 |
| UF-23 | Admin — content publishing | Content Manager | P0 |
| UF-24 | Admin — rights expiration management | Administrator | P0 |
| UF-25 | Admin — playback session investigation | Support / Operations | P0 |

---

# PART A — ACCOUNT AND IDENTITY FLOWS

## UF-01 — Registration

**Actor** Anonymous visitor · **Priority** P0
**Precondition** Not authenticated · **Trigger** Selects "Create account"

### Happy path
1. Visitor opens the registration surface.
2. Visitor enters an identifier (email — or phone, per PD-020) and a password.
3. Visitor accepts terms and the privacy notice. *(Content and consent mechanics are
   [LEGAL — PD-066].)*
4. System validates format and password policy (PD-025) at the boundary.
5. System creates the account in `pending_verification`.
6. System sends a verification message out of band.
7. System returns a **generic success response**, identical whether or not the identifier
   was already registered.
8. Visitor opens the verification link.
9. System validates the token — time-limited and single-use — and sets the account
   `active`.
10. System authenticates the user and registers the current device (UF-05).

### Alternate paths
- **A1 — Identifier already registered.** Response is **identical** to success in content,
  status, and timing. A message is sent to the existing address advising that a
  registration was attempted. **No enumeration signal is emitted.**
- **A2 — Verification link expired.** User requests a new one; resend is rate-limited.
- **A3 — Registers on a TV device.** Second-screen or code-based registration is offered
  **[PROPOSED]**, since entering an email and password on a TV remote is punishing.

### Failure paths
- **F1 — Invalid format.** Field-level validation errors; no account created.
- **F2 — Password fails policy.** Specific, actionable guidance; no account created.
- **F3 — Rate limit exceeded.** Generic throttle response; no enumeration signal.
- **F4 — Verification token already used.** Rejected; no state change.

**Postcondition** Account exists, is `active` and verified, one device registered, session
issued. **Entitlement is empty** — registration grants no viewing capability.

**Requirements** FR-ACC-01, FR-ACC-02, FR-ACC-07, FR-DEV-01, NFR-SEC-07, NFR-PRV-01

---

## UF-02 — Login

**Actor** Registered user · **Priority** P0
**Precondition** Account exists · **Trigger** Submits credentials

### Happy path
1. User submits identifier and password.
2. System verifies credentials **server-side** against a memory-hard hash.
3. If MFA is enabled, system requests the second factor and verifies it.
4. System evaluates account status. `active` proceeds.
5. System identifies or registers the device (UF-05); enforces the device limit.
6. System issues a session bound to the device, with a short-lived access token and a
   rotating, revocable refresh token.
7. If the device is new, system sends a **security notification** that cannot be disabled.
8. System returns the profile list; user proceeds to UF-04.

### Alternate paths
- **A1 — MFA enabled.** Second factor requested; failures are rate-limited.
- **A2 — Device limit reached.** See UF-20.
- **A3 — Account `pending_verification`.** Login succeeds; privileged operations are
  restricted until verified (PD-028).
- **A4 — TV device.** Second-screen or code-based sign-in **[PROPOSED]**.

### Failure paths
- **F1 — Unknown identifier.** Generic failure, **identical in content and timing** to F2.
- **F2 — Wrong password.** Generic failure, identical to F1.
- **F3 — Repeated failures.** Progressive lockout; user informed generically; a security
  notification is sent to the account.
- **F4 — Account `suspended`.** Behaviour per PD-029 — **undecided**; the flow must not
  assume login is permitted or refused.
- **F5 — Account `closed`.** Login refused.

**Postcondition** Authenticated session bound to a registered device.

**Requirements** FR-ACC-03, FR-ACC-04, FR-ACC-10, FR-DEV-01, FR-NOT-01, NFR-SEC-04,
NFR-SEC-07, NFR-SEC-14

---

## UF-03 — Password recovery

**Actor** Registered user · **Priority** P0
**Precondition** Account exists · **Trigger** Selects "Forgot password"

### Happy path
1. User submits their identifier.
2. System returns a **generic response**: "If an account exists, instructions have been
   sent." — identical regardless of existence.
3. If the account exists, system generates a time-limited, single-use token and sends it
   out of band.
4. User opens the link; system validates the token.
5. User sets a new password meeting policy.
6. System updates the credential, **invalidates all existing sessions**, and sends a
   security notification.
7. User authenticates with the new password (UF-02).

### Alternate paths
- **A1 — Account does not exist.** Identical response; **no message is sent**; no record
  is created that could be probed.
- **A2 — Token expired.** User requests a new one; rate-limited.

### Failure paths
- **F1 — Token already used.** Rejected; no password change.
- **F2 — Token belongs to a different account.** Rejected; attempt logged.
- **F3 — Rate limit exceeded.** Generic throttle response.

### Lost access to the identifier
Not self-service. Routed to a **manual, audited support process** with an
identity-verification standard that is **[OPEN — PD-027]** and **[LEGAL]**. The flow
explicitly does not define a fallback, because an improvised one becomes the easiest way
into any account.

**Postcondition** Password changed; all prior sessions invalidated; user notified.

**Requirements** FR-ACC-08, FR-ACC-09, FR-ACC-14, NFR-SEC-07

---

## UF-04 — Profile selection

**Actor** Authenticated user · **Priority** P0
**Precondition** Authenticated session · **Trigger** Post-login, or "switch profile"

### Happy path
1. System returns the account's profiles with names and avatars.
2. User selects a profile.
3. If the profile is PIN-protected, system requests the PIN and **verifies it
   server-side**.
4. System sets the active profile for the session.
5. System loads profile context: language, maturity limit, history, favorites,
   resume points.
6. User lands on the home screen (§7), personalized to that profile.

### Alternate paths
- **A1 — First login, no profiles.** A default profile is created from the account.
- **A2 — Child profile selected.** Stricter defaults apply: reduced maturity ceiling, no
  account settings, no purchasing.
- **A3 — Exit a child profile.** Requires the **account PIN**, distinct from a profile PIN.
- **A4 — Create a profile.** Name, avatar, language, type, and maturity limit; subject to
  the per-account profile limit (PD-036).

### Failure paths
- **F1 — Incorrect PIN.** Rejected; attempts rate-limited; no indication of how close the
  guess was.
- **F2 — Profile limit reached.** Creation refused with a clear message.
- **F3 — Profile deleted concurrently on another device.** Session falls back to profile
  selection cleanly (EC-22).

### Invariant
**Profile switching is not authentication.** It grants no additional privilege, performs
no re-authentication, and can never elevate a session.

**Postcondition** Active profile set; personalization scoped to that profile.

**Requirements** FR-PRF-01, FR-PRF-02, FR-PRF-04, FR-PRF-05, FR-PRF-06, FR-PRF-07

---

## UF-05 — Add / remove device

**Actor** Authenticated user · **Priority** P0

### A. Add a device
1. User authenticates on a new device (UF-02).
2. System identifies the device: class, stable identifier, platform, version.
3. System checks whether the device is already registered — **registration is idempotent**
   for the same physical device.
4. If new, system checks the device limit for the account's package (PD-038).
5. Within the limit: system registers the device with a default editable name, records
   first-seen, and sends a **security notification**.
6. Session is issued and bound to the device.

**At the limit:** see UF-20.

### B. Remove a device
1. User opens device management (any platform).
2. System lists registered devices: name, class, last active, current-device marker.
3. User selects a device and confirms removal.
4. System invalidates that device's sessions.
5. **Playback on the removed device stops within the documented revocation interval**
   (PD-026).
6. The removed slot becomes available — subject to a cooling-off period if PD-039 is
   approved.
7. The removal is recorded in the security timeline.

### Failure paths
- **F1 — Removing the current device.** Permitted, with confirmation; the user is signed
  out on completion.
- **F2 — Device identifier unstable on a platform.** Falls back to the defined
  re-registration behaviour rather than silently consuming slots. **[UNVERIFIED]** —
  per-platform identifier stability must be confirmed from official documentation in each
  client phase, never assumed.

**Postcondition** Device set accurately reflects reality; removed devices cannot play.

**Requirements** FR-DEV-01, FR-DEV-02, FR-DEV-04, FR-DEV-05, FR-DEV-06, FR-NOT-01

---

# PART B — VIEWING FLOWS

## UF-06 — Browse Live TV

**Actor** Viewer · **Priority** P0
**Precondition** Authenticated, profile active · **Trigger** Selects "Live TV"

### Happy path
1. Viewer opens Live TV.
2. System returns the channel list **filtered by entitlement server-side** — unentitled
   channels are absent from the payload, not merely hidden.
3. Each channel shows logo, number, name, and current programme with progress.
4. Viewer navigates by category, group, or favorites.
5. Viewer selects a channel → UF-07.

### Alternate paths
- **A1 — No EPG for a channel.** The channel is listed and **playable**; the programme
  area shows "Programme information unavailable".
- **A2 — Favorites filter.** List narrows to the profile's favorite channels.
- **A3 — Sort change.** Order changes and the preference persists per profile.
- **A4 — TV platform.** D-pad traversal; focus always visible; channel numbers enterable
  directly where the platform allows.

### Failure paths
- **F1 — No entitled channels.** Explains what the account can access and offers a
  package path. **Never a blank screen.**
- **F2 — Channel list unavailable.** Error with retry; cached list shown if available and
  clearly marked as possibly stale.

**Postcondition** Viewer has a navigable, entitlement-accurate channel list.

**Requirements** FR-LIV-01, FR-LIV-02, FR-LIV-11, FR-EPG-05, FR-HOME-01

---

## UF-07 — Watch a channel

**Actor** Viewer · **Priority** P0 · **The central flow of the product.**

### Happy path
1. Viewer selects a channel.
2. Client requests **playback authorization** for the channel.
3. Backend evaluates **all ten checks** (`PRODUCT_SPEC.md` §12.1), in full, with no skip
   path:
   authenticated session · account status · subscription state · package contents ·
   entitlement decision · **rights window, territory, device class, and live distribution
   mode** · device registration · concurrency · geo policy · playback policy.
4. All checks pass. Backend issues a **playback session**: short-lived, bound to viewer,
   device, and channel, revocable.
5. Backend returns signed, short-lived delivery URLs.
6. Client requests the manifest; **the edge validates the token independently** — not
   relying on issuance alone.
7. Playback starts. Live indicator shows the live edge.
8. Client begins heartbeat to retain its concurrency slot.
9. Client reports QoE telemetry: startup time, bitrate, rebuffering, errors.
10. Backend records the authorization decision as **compliance evidence** — no credentials,
    no tokens.

### Alternate paths
- **A1 — Restart available.** A restart affordance appears only when restart rights exist
  → UF-10.
- **A2 — Subtitles / audio tracks available.** Selectable; preference persists per profile.
- **A3 — Viewer switches channel.** → UF-09.
- **A4 — Session nears expiry.** Client renews transparently before expiry.

### Failure paths
Every failure below returns a **stable reason code** and a **localized, non-technical
message**. See UF-18 for full handling.
- **F1** `SUBSCRIPTION_EXPIRED` — renewal path offered
- **F2** `NOT_IN_PACKAGE` — upgrade path offered
- **F3** `RIGHTS_EXPIRED` — neutral message, alternatives offered
- **F4** `TERRITORY_RESTRICTED` — neutral message, **no workaround offered or hinted**
- **F5** `DEVICE_CLASS_NOT_PERMITTED` — states which device types can play it
- **F6** `CONCURRENCY_LIMIT_REACHED` — → UF-20
- **F7** `DEVICE_REVOKED` — re-authentication prompted
- **F8** `ACCOUNT_SUSPENDED` — directed to support
- **F9 — Source unavailable.** `source_error` — **technical framing, retry offered,
  operations alerted. The viewer is never told their subscription is invalid.**
- **F10 — Network failure.** Client retries with backoff; slot preserved during the
  heartbeat grace period.

### Invariants
- **Possessing a manifest URL never permits playback.**
- **The client is never the authority** — it renders what the backend authorizes.
- **Client-reported location, device class, and time are never trusted.**

**Postcondition** Either authorized playback with an audited decision, or a denial with a
specific reason, a clear message, and an audited decision.

**Requirements** FR-AUT-01…FR-AUT-11, FR-PLY-01…FR-PLY-08, FR-LIV-03, FR-LIV-06,
NFR-PER-01, NFR-PER-04, NFR-OBS-04

---

## UF-08 — Open the EPG

**Actor** Viewer · **Priority** P0

### Happy path
1. Viewer opens the guide.
2. System returns the schedule grid for entitled channels around the current time.
3. All times are **computed in UTC** and rendered in the viewer's local timezone.
4. The current time position is marked; current programmes show progress.
5. Viewer navigates by channel and by time; days are pageable within the retained range
   (PD-044).
6. Viewer selects a programme to see details: title, description, time, duration, genre,
   rating, artwork.
7. Contextual actions appear **only where genuinely available**: watch (current), restart
   (UF-10), catch-up (UF-11), remind (favorites).

### Alternate paths
- **A1 — Programme currently live.** "Watch now" leads to UF-07.
- **A2 — Programme in the past with catch-up rights.** "Watch" leads to UF-11.
- **A3 — Programme in the past without catch-up rights.** Details shown; **no catch-up
  action offered** — the affordance is absent, not disabled with an error.
- **A4 — Future programme.** Reminder action where notifications permit.
- **A5 — TV platform.** Grid optimized for D-pad; fast day paging; low memory footprint.

### Failure paths
- **F1 — No EPG data for a channel.** Row renders with "Programme information
  unavailable"; the channel remains playable.
- **F2 — EPG stale beyond threshold.** Existing data served; operations alerted; user
  notified only where confusion is likely.
- **F3 — Schedule gap.** Rendered honestly as a gap; adjacent programmes are **not**
  stretched to conceal it.
- **F4 — EPG service unavailable.** Cached data served if present; otherwise the guide is
  unavailable while **playback continues to work**.

### Invariant
**EPG failure never blocks playback.** Live TV without a guide is diminished; live TV
that will not play because of the guide is broken.

**Requirements** FR-EPG-01…FR-EPG-05, FR-EPG-08, FR-EPG-09, NFR-AVL-03, NFR-PER-07

---

## UF-09 — Switch channel

**Actor** Viewer · **Priority** P0

### Happy path
1. Viewer changes channel — direct selection, up/down step, number entry, or from the
   guide.
2. Client **debounces** rapid stepping so only committed changes trigger authorization.
3. On commit, client requests a **new playback authorization** for the target channel.
4. Backend evaluates all ten checks afresh. The previous channel's authorization is
   **never reused or extended**.
5. Previous session is released, freeing its concurrency slot.
6. New playback session issued; playback starts.
7. Channel change completes within the target (PD-043, PROPOSED ≤ 2.0 s on the minimum
   TV device).

### Alternate paths
- **A1 — Previous channel.** One action returns to the immediately previous channel,
  itself a full new authorization.
- **A2 — Rapid zapping.** Only the settled channel is authorized; intermediate channels
  are not requested (EC-28).

### Failure paths
- **F1 — Target channel denied.** Viewer is returned to a **defined state** — remaining on
  the previous channel is preferred over a blank player — and shown the reason.
- **F2 — Target source unavailable.** Technical error with retry; previous channel offered
  as a fallback.
- **F3 — Concurrency limit reached during switch.** Should not occur, because the previous
  slot is released first; if it does, the previous channel is restored and the event is
  logged as a defect signal.

**Requirements** FR-LIV-04, FR-LIV-07, FR-LIV-08, FR-LIV-09, FR-AUT-02, NFR-PER-05

---

## UF-10 — Restart a programme

**Actor** Viewer · **Priority** P1

### Happy path
1. Viewer is watching a live programme already in progress.
2. **Restart is offered only if:** the channel has restart enabled, **restart distribution
   rights exist for this programme**, and the elapsed portion is retained.
3. Viewer selects restart.
4. Client requests playback authorization for **restart mode** specifically — live
   authorization is not sufficient.
5. Backend validates restart rights independently of live rights.
6. Playback transitions to the programme start. The live indicator changes to a non-live
   state.
7. "Go live" is available at all times.
8. Seeking is permitted within the elapsed portion; forward seek is bounded by the live
   edge.
9. The restart session consumes one concurrency slot, like any session (PD-040).

### Alternate paths
- **A1 — Programme ends during restart.** Playback continues to programme end; then
  catch-up rules apply if catch-up rights exist, otherwise the viewer returns to live.
- **A2 — Viewer selects "go live".** Playback jumps to the live edge; the indicator
  updates.

### Failure paths
- **F1 — No restart rights.** **The affordance is never shown.** A direct request is
  denied with a rights reason code.
- **F2 — Restart stream unavailable.** **Falls back to live playback** rather than failing
  the session.
- **F3 — Rights expire mid-restart.** Behaviour per **PD-056 — undecided**; the flow does
  not assume an answer.

**Requirements** FR-RST-01…FR-RST-05, FR-RGT-03, FR-AUT-02

---

## UF-11 — Catch-up playback

**Actor** Viewer · **Priority** P1

### Happy path
1. Viewer browses the guide to a past programme (UF-08).
2. **Catch-up is offered only if:** the channel has catch-up enabled, **catch-up
   distribution rights exist for this programme**, and it falls within the catch-up window
   (configurable per channel and per rights agreement — PD-058).
3. Viewer selects the programme.
4. Client requests playback authorization for **catch-up mode** specifically.
5. Backend validates catch-up rights independently of live rights.
6. Playback starts at the programme boundary, with configured padding (PD-059).
7. Seeking is permitted **within the programme only**.
8. On reaching the end, the viewer is offered the next programme or a return to browse.

### Alternate paths
- **A1 — Programme boundary imprecise due to EPG inaccuracy.** Padding absorbs the
  discrepancy. **An imperfect start point is acceptable; a playback failure is not.**
- **A2 — EPG corrected after recording.** Boundaries recomputed; playback does not fail
  because of the correction (EC-25).

### Failure paths
- **F1 — No catch-up rights.** **Affordance never shown.** Direct request denied with a
  rights reason code. *(This is the single most common inadvertent breach in time-shifted
  television — see `docs/legal/DECISION_LOG.md` L-002.)*
- **F2 — Outside the catch-up window.** Denied with a clear "no longer available" message.
- **F3 — Recording missing or incomplete.** Technical error with retry; operations alerted.
- **F4 — Window expires during playback.** Behaviour per PD-056.

**Requirements** FR-CUP-01…FR-CUP-06, FR-RGT-03, FR-AUT-02

---

## UF-12 — Search

**Actor** Viewer · **Priority** P1

### Happy path
1. Viewer opens search.
2. Before typing: recent searches, suggestions, and popular content are shown — never a
   blank field alone.
3. Viewer types a query in **Georgian, Cyrillic, or Latin script**.
4. Client debounces; system returns autocomplete suggestions.
5. Viewer submits; system returns results **filtered by entitlement server-side** and by
   the profile's maturity limit.
6. Results are grouped by type: channels, programmes, movies, series, episodes.
7. Viewer selects a result → UF-07 (channel), UF-11 (past programme), or UF-13 (VOD).

### Alternate paths
- **A1 — Typo in the query.** Fuzzy matching returns intended results across all scripts.
- **A2 — Filters applied.** Results narrow by type, genre, language, or availability.
- **A3 — TV platform.** Remote-navigable on-screen keyboard supporting all three scripts;
  voice input where the platform provides it **[UNVERIFIED — per-platform]**.

### Failure paths
- **F1 — No results.** Clear message plus alternatives: popular content, category entry
  points, spelling suggestions. **Never a dead end.**
- **F2 — Results exist but none entitled.** Handled as no results with a package-upgrade
  path — the existence of unentitled content is **not disclosed**.
- **F3 — Search service unavailable.** Error with retry; browse remains available.

**Requirements** FR-SCH-01…FR-SCH-06, FR-PRF-04, NFR-PER-11

---

## UF-13 — Watch VOD

**Actor** Viewer · **Priority** P0

### Happy path
1. Viewer browses or searches the catalogue (entitlement-filtered server-side).
2. Viewer opens a title's detail: synopsis, duration, rating, artwork, availability.
3. Viewer selects play.
4. Client requests playback authorization for **VOD mode** specifically.
5. Backend evaluates all ten checks, including **VOD distribution rights** for this title,
   territory, and device class.
6. Playback session issued; signed short-lived URLs returned; edge validates independently.
7. Playback starts; subtitles and audio tracks selectable where supplied.
8. Resume position is recorded periodically, per profile.
9. On completion, the next episode is offered for series content, respecting entitlement
   and maturity limits.

### Alternate paths
- **A1 — Series.** Season selection and episode list; "up next" on completion.
- **A2 — Trailer.** Playable where supplied **and rights-permitted** — a trailer is
  separately licensed.
- **A3 — Add to watchlist.** Saved per profile, synchronized immediately.
- **A4 — Title leaving soon.** Surfaced where the lead-time policy permits (PD-055).

### Failure paths
- **F1** `RIGHTS_EXPIRED` — title absent from browse; direct request denied neutrally
- **F2** `NOT_IN_PACKAGE` — upgrade path offered
- **F3** Maturity limit exceeded — denied; title absent from that profile's browse entirely
- **F4** `CONCURRENCY_LIMIT_REACHED` — → UF-20
- **F5** Source error — technical framing with retry

**Requirements** FR-VOD-01…FR-VOD-07, FR-AUT-02, FR-PRF-04

---

## UF-14 — Continue watching

**Actor** Viewer · **Priority** P0

### Happy path
1. Viewer opens home on any device.
2. System returns continue-watching for the **active profile**, derived from watch history
   using the defined thresholds (PD-062).
3. Each item shows title, artwork, and progress.
4. Viewer selects an item.
5. Client requests playback authorization afresh — **a resume point is not an entitlement**.
6. Playback resumes at the stored position.
7. The updated position synchronizes across devices.

### Alternate paths
- **A1 — Started on device A, resumed on device B.** Position synchronized server-side;
  resume is offered at the stored position.
- **A2 — Two devices playing the same title concurrently.** Conflict resolved by the
  defined rule; **never silent data loss without a rule** (EC-26).
- **A3 — Title completed.** Removed from continue-watching per the completion threshold.
- **A4 — Series.** Continue-watching advances to the next episode on completion.

### Failure paths
- **F1 — Entitlement lost since last watch.** Item remains visible but is marked
  unavailable, with an upgrade or renewal path. **Never silently removed** — silent
  removal looks like data loss to the viewer.
- **F2 — Rights expired since last watch.** Item removed from continue-watching;
  a direct request is denied neutrally.
- **F3 — Resume position unavailable.** Playback offers to start from the beginning.

**Requirements** FR-HIS-04, FR-HIS-05, FR-VOD-06, FR-HOME-02, FR-AUT-01

---

# PART C — COMMERCIAL FLOWS

## UF-15 — Subscribe

**Actor** Registered user · **Priority** P0

### Happy path
1. User opens the package selection surface.
2. System returns available packages, filtered by eligibility (territory, platform,
   promotion). *(Names, contents, and prices are [OPEN — PD-013, PD-014].)*
3. User selects a package and confirms.
4. System creates a subscription in `pending`.
5. System hands off to a **provider-hosted or tokenized payment flow**.
   **Card data never touches KMS TV systems.**
6. Provider processes payment and returns.
7. Provider sends a **signature-verified callback**; system processes it **idempotently**.
8. System transitions the subscription to `active` and records the transition in the
   append-only history.
9. System **invalidates cached entitlement immediately**.
10. System sends confirmation; the user can play newly entitled content at once.

### Alternate paths
- **A1 — Trial offered.** Per PD-011 — undecided; the flow does not assume trials exist.
- **A2 — Upgrade from an existing package.** Effective immediately; proration per PD-048.
- **A3 — Downgrade.** Timing per PD-048 — undecided.
- **A4 — App-store in-app purchase required.** Per PD-076 and platform policy
  **[UNVERIFIED]** — no store's current policy is assumed.

### Failure paths
- **F1 — Payment declined.** Subscription remains `pending`; user informed; retry offered;
  **no entitlement granted**.
- **F2 — User abandons the payment flow.** Subscription remains `pending`; expires per the
  defined timeout; no entitlement granted.
- **F3 — Callback duplicated.** Idempotent: no double charge, no double extension
  (EC-15).
- **F4 — Callback delayed or out of order.** Later state is not overwritten by an earlier
  callback (EC-16).
- **F5 — Callback never arrives.** Reconciliation detects and corrects the discrepancy;
  operations alerted (EC-17).
- **F6 — Package ineligible in the user's territory.** Not offered; a direct request is
  refused.

**Postcondition** Active subscription; entitlement effective immediately; every step
audited.

**Requirements** FR-SUB-01…FR-SUB-07, FR-PKG-01, FR-PKG-02, FR-PAY-01…FR-PAY-06

---

## UF-16 — Cancel subscription

**Actor** Subscriber · **Priority** P0

### Happy path
1. Subscriber opens subscription management.
2. System shows current package, state, renewal date, and price.
3. Subscriber selects cancel.
4. System explains the consequence: **access continues to period end** [PROPOSED],
   then ends. Retention offers may be presented **[OPEN — PD-012]**.
5. Subscriber confirms.
6. System transitions the subscription to `cancelled`, recording the transition.
7. System instructs the provider to stop future billing.
8. System sends confirmation stating the exact date access ends.
9. **Entitlement continues unchanged until period end.**
10. At period end the subscription becomes `expired` and entitlement is invalidated
    immediately.

### Alternate paths
- **A1 — Immediate cancellation requested.** Only if explicitly chosen by the user;
  refund policy applies (PD-091).
- **A2 — Reactivation before period end.** Returns to `active` with no billing interruption.
- **A3 — Cancellation via an app store.** State synchronizes from the store; KMS TV remains
  the authority on entitlement, the store on payment.

### Failure paths
- **F1 — Provider cancellation fails.** Subscription state is not changed optimistically;
  the failure is surfaced, retried, and alerted. **The subscriber must never be left
  billed with a cancelled record, or unbilled with an active one.**
- **F2 — Already cancelled.** Idempotent; current state confirmed.

**Requirements** FR-SUB-01, FR-SUB-02, FR-SUB-03, FR-SUB-06, FR-PAY-06

---

## UF-17 — Payment failure

**Actor** Subscriber (system-initiated) · **Priority** P0

### Happy path — recovery
1. A renewal payment fails at the provider.
2. Provider sends a signature-verified failure callback.
3. System transitions the subscription to `past_due` and records it.
4. System notifies the subscriber: what failed, what happens next, how to fix it.
5. **Grace behaviour applies per PD-051 — undecided.** Whether playback continues during
   `past_due` is a product decision with real revenue and goodwill consequences, and is
   not defaulted here.
6. System retries per the dunning schedule (PD-053).
7. Subscriber updates their payment method through the provider-hosted flow.
8. Payment succeeds; subscription returns to `active`; entitlement restored immediately.

### Failure paths
- **F1 — All retries exhausted.** Subscription transitions to `expired`; entitlement
  invalidated immediately; the subscriber is notified with a clear reactivation path.
- **F2 — Subscriber updates their method but the retry still fails.** Cycle continues
  within the dunning schedule until exhausted.
- **F3 — Duplicate failure callbacks.** Idempotent; no repeated state churn, no repeated
  notification storm.
- **F4 — Failure callback arrives after a successful payment.** Out-of-order handling
  prevents an active subscription being wrongly marked `past_due` (EC-16).

### Invariants
- The subscriber is **always told** what happened and how to fix it.
- **No card data appears in any notification, log, or error** (FR-PAY-02).
- Entitlement changes are **immediate** on each state transition.

**Requirements** FR-SUB-08, FR-PAY-03, FR-PAY-04, FR-PAY-05, FR-NOT-04

---

# PART D — FAILURE AND ENFORCEMENT FLOWS

## UF-18 — Playback authorization failure

**Actor** Viewer · **Priority** P0
**This flow is where compliance becomes visible to the viewer.**

### Flow
1. Viewer requests playback (UF-07, UF-11, UF-13).
2. Backend evaluates all ten checks.
3. One or more checks fail.
4. Backend returns a **denial with a stable, machine-readable reason code**.
5. Backend records the decision as **compliance evidence** with the full evaluation
   context, and **no credentials or tokens**.
6. Client maps the reason code to a **localized, plain-language message** and a
   **recovery action**.
7. Client emits an analytics event carrying the reason code.

### Reason handling

| Reason code | Message intent | Recovery action |
|---|---|---|
| `SUBSCRIPTION_EXPIRED` | Subscription has ended | Direct renewal path |
| `NOT_IN_PACKAGE` | Included in a different package | Direct upgrade path |
| `RIGHTS_EXPIRED` | **Neutral** — no longer available | Suggest alternatives |
| `TERRITORY_RESTRICTED` | **Neutral** — not available in your location | Suggest available alternatives. **No workaround is offered, suggested, or hinted at.** |
| `DEVICE_CLASS_NOT_PERMITTED` | Not available on this device type | State which device types can play it |
| `DEVICE_LIMIT_REACHED` | Device limit reached | Open device management → UF-20 |
| `CONCURRENCY_LIMIT_REACHED` | Already watching elsewhere | Stop another session (PD-047) or wait |
| `DEVICE_REVOKED` | This device is no longer authorized | Re-authenticate |
| `ACCOUNT_SUSPENDED` | Account issue | Contact support |
| `SESSION_EXPIRED` | Session ended | Silent retry once, then re-authenticate |
| Maturity restriction | Not available on this profile | Switch profile (account PIN required) |

### Invariants
- **Clients never parse human-readable messages** — the code is the contract.
- **Errors never expose internals**: no stack traces, URLs, host names, SQL, or vendor
  errors.
- **"You may not" and "we could not" are never confused.** Telling an entitled viewer their
  subscription is invalid during a CDN outage is both a support disaster and a trust
  failure.
- **Every denial is a dead end with a door** — a message with no path forward is a defect.

**Requirements** FR-AUT-04, FR-AUT-10, FR-PLY-03, FR-PLY-05, FR-ERR-01…FR-ERR-04

---

## UF-19 — Rights expiration

**Actors** System (primary), viewer, operator · **Priority** P0
**The flow that protects the operator's ability to keep doing business.**

### A. Approaching expiry (system + operator)
1. A rights window nears its end within the configured lead time (PD-055).
2. System surfaces the agreement on the rights expiration dashboard.
3. System raises an operator alert; the "expiring within 24 hours" metric increments.
4. Operator reviews → UF-24: renew, replace, or allow expiry.
5. **[PROPOSED]** Content may be surfaced to viewers as "leaving soon".

### B. At expiry (system)
1. The rights window end passes.
2. **Mechanism 1 — scheduled job:** sets affected assets to `disabled_by_rights`, removes
   them from browse, search, recommendations, and home surfaces, and invalidates cached
   entitlement.
3. **Mechanism 2 — per-request re-check:** every playback authorization independently
   re-validates the rights window and denies with `RIGHTS_EXPIRED`.
4. **Both mechanisms are mandatory and are tested independently.** Disabling either must
   not permit expired content to play.
5. Delivery caches are purged where the content was distributed via CDN. **Revocation is
   not complete until purge is confirmed** (EC-14).
6. The transition is recorded as compliance evidence.

### C. Viewer experience
- Content disappears from browse.
- A direct or bookmarked request is denied with `RIGHTS_EXPIRED` and a **neutral** message.
- Continue-watching entries for expired content are removed.
- Favorites entries remain saved but are marked unavailable — never silently deleted.

### D. In-progress viewing at the moment of expiry
**[OPEN — PD-056] and [LEGAL].** Terminate immediately, or allow the current programme or
title to complete? These carry different compliance profiles and different viewer costs,
and the answer may be dictated by contract rather than chosen. **This flow does not assume
an answer.**

### E. Revocation ahead of schedule
1. A rights agreement is revoked before its window end.
2. All of section B executes immediately rather than on schedule.
3. In-progress sessions are handled per PD-056.
4. Operations and content management are alerted.

### Invariant
**The system must not continue offering content after applicable rights expire** —
in browse, in search, in recommendations, in deep links, in caches, or at the edge.

**Requirements** FR-RGT-04…FR-RGT-11, FR-CHN-05, FR-AUT-02, EC-12, EC-13, EC-14

---

## UF-20 — Device-limit failure

**Actor** Viewer · **Priority** P0

### A. Device registration limit
1. Viewer authenticates on a new device.
2. System finds the account at its registered-device limit (PD-038).
3. Registration is refused with `DEVICE_LIMIT_REACHED`.
4. The viewer is shown their registered devices with names, classes, and last-active times.
5. The viewer removes a device (UF-05B).
6. The removed device's sessions are invalidated and its playback stops.
7. The slot frees — subject to a cooling-off period if PD-039 is approved.
8. The new device registers; the flow resumes.

### B. Concurrent stream limit
1. Viewer requests playback while at the concurrency limit (PD-040 — package limit and any
   rights-agreement limit; **the most restrictive applies**).
2. Authorization is denied with `CONCURRENCY_LIMIT_REACHED`.
3. The viewer is shown that another session is active. **Whether the specific devices are
   named is [OPEN — PD-047]** — genuinely useful, and also a privacy disclosure within a
   household.
4. **[PROPOSED]** The viewer may stop another session from the current device.
5. The freed slot allows the new session to be authorized.

### Alternate paths
- **A1 — Abandoned session holding a slot.** Released within the abandonment interval
  (PD-041); the viewer can retry successfully without support contact.
- **A2 — Rights-agreement concurrency limit is lower than the package limit.** The rights
  limit governs; the message remains a generic concurrency message and does **not** expose
  contract details to the viewer.

### Failure paths
- **F1 — Removal fails.** Error with retry; the device set is not left inconsistent.
- **F2 — Repeated limit hits.** Surfaced to operations as a possible credential-sharing
  signal, handled per PD-042 — **undecided**; no automated punitive action is assumed.

### Invariants
- Limits are enforced **server-side**, atomically, with **no race** yielding limit + 1
  (EC-19).
- Every limit error offers a path to resolve it without contacting support.

**Requirements** FR-DEV-04, FR-DEV-05, FR-DEV-06, FR-AUT-06, FR-AUT-11, FR-AUT-12

---

# PART E — OPERATOR FLOWS

## UF-21 — Admin: channel creation

**Actor** Content Manager · **Priority** P0

### Happy path
1. Operator opens Channels and selects create.
2. Operator enters metadata: name, slug, description, logo, category, language, country,
   provider, EPG identifier, sort order.
3. Operator attaches a **stream source**. **The source cannot be created without recorded
   provenance** — who supplied it, under what agreement, with what identifier.
4. Operator attaches a **rights reference** — the agreement permitting distribution.
5. Operator sets the playback policy: restart, catch-up, recording permissions, quality
   ceiling, DRM requirement.
6. Operator sets visibility: which packages and device classes may see the channel.
7. Operator saves as `draft`.
8. Operator validates: source reachable, EPG identifier resolving, rights window active.
9. Operator publishes → UF-23.
10. Every step is audited with actor, timestamp, and before/after state.

### Failure paths
- **F1 — No rights reference.** **Publication is rejected** with a validation error naming
  the missing reference. The channel stays unpublished. *(This is a system-enforced
  validation rule, not a checklist.)*
- **F2 — Source has no provenance.** **Source creation is rejected.** No record is created.
- **F3 — Rights window not yet started.** Channel may be created and scheduled but does not
  become available until the window opens — automatically, with no manual step required.
- **F4 — Rights window already expired.** Publication rejected.
- **F5 — Duplicate slug or channel number.** Rejected with a specific validation error.
- **F6 — Insufficient permission.** Denied **server-side**, even if the UI offered the
  action; the attempt is audited.

**Requirements** FR-CHN-01…FR-CHN-06, FR-ADM-01, FR-ADM-02, FR-ADM-07

---

## UF-22 — Admin: EPG import

**Actor** Content Manager · **Priority** P0

### Happy path
1. Operator opens EPG and selects an ingest source, or ingest runs on schedule.
2. System retrieves the source data through a format adapter behind an abstraction.
3. System **validates and sanitizes** — ingested EPG is untrusted input.
4. System normalizes: **all times converted to UTC**, DST handled, durations computed.
5. System detects overlaps, gaps, and duplicates, resolving them by the documented rule
   (PD-045) and flagging conflicts for review.
6. System matches programmes to channels by EPG identifier.
7. **Ingest is idempotent** — re-ingesting identical data changes nothing.
8. System commits the result and updates the freshness metric.
9. Operator reviews the ingest report: counts, conflicts, unmatched channels, warnings.

### Alternate paths
- **A1 — Retrospective correction.** Accepted; affected programmes updated; catch-up
  boundaries recomputed (EC-25).
- **A2 — Unmatched channel identifier.** Flagged for operator mapping; other channels are
  unaffected.
- **A3 — Partial source coverage.** Covered channels update; uncovered channels retain
  existing data.

### Failure paths
- **F1 — Source unreachable.** Ingest fails cleanly; **existing data remains intact and
  servable**; operations alerted.
- **F2 — Malformed feed.** Rejected; **existing data is never replaced with partial data**;
  the failure is reported with detail.
- **F3 — Ingest fails partway.** No partial commit. Prior data intact (EC-08).
- **F4 — Duplicate programmes in the feed.** Deduplicated; the conflict is flagged (EC-09).
- **F5 — Ingest freshness threshold breached.** Alert raised; existing data continues to be
  served; **playback is unaffected**.

### Invariant
**A bad feed must never damage good data.** Ingestion is idempotent, transactional in
effect, and never destructive on failure.

**Requirements** FR-EPG-06, FR-EPG-07, FR-EPG-10, FR-ADM-12, EC-08, EC-09, EC-10

---

## UF-23 — Admin: content publishing

**Actor** Content Manager · **Priority** P0

### Happy path
1. Operator selects a `draft` asset — channel, movie, series, season, or episode.
2. System runs **publication validation**:
   - required metadata complete
   - artwork present at required sizes
   - **rights reference present and its window valid**
   - **distribution modes on the rights agreement cover the intended availability**
   - stream or media asset processed and playable
   - category and classification assigned
3. All checks pass. Operator confirms publication.
4. System sets status to `published`.
5. Availability follows the **rights window**, not the publish action — content scheduled
   ahead of its window becomes available automatically when the window opens.
6. Caches invalidate; the asset appears in browse, search, and recommendations for
   entitled profiles.
7. The action is audited with actor, timestamp, and before/after state.

### Alternate paths
- **A1 — Scheduled publication.** Publishes at a future time; still gated by the rights
  window.
- **A2 — Unpublish.** Removes from viewer surfaces; retains the record; audited.
- **A3 — Bulk publish.** Requires **preview and explicit confirmation**; validation runs
  per asset; partial success is reported per asset rather than failing the batch silently.

### Failure paths
- **F1 — Missing rights reference.** **Publication rejected.** Non-negotiable.
- **F2 — Rights window expired.** Publication rejected.
- **F3 — Distribution mode not granted.** E.g. publishing to VOD when only live rights
  exist → rejected, naming the missing mode.
- **F4 — Media not processed.** Rejected; the operator is shown processing status.
- **F5 — Missing required metadata or artwork.** Rejected with a specific field list.
- **F6 — Insufficient permission.** Denied server-side; audited.

**Requirements** FR-CHN-02, FR-VOD-05, FR-RGT-01, FR-RGT-03, FR-ADM-01, FR-ADM-02,
FR-ADM-17

---

## UF-24 — Admin: rights expiration management

**Actor** Administrator · **Priority** P0

### Happy path
1. Operator opens Rights → Expiration.
2. System lists agreements expiring within the configured horizon, with affected assets,
   windows, contract references, and rights holders.
3. The dashboard shows the "rights expiring within 24 hours" metric.
4. Operator reviews an agreement and its full asset impact.
5. Operator takes one of three actions:
   - **Renew** — extend the window against a new or amended contract reference
   - **Replace** — attach a different agreement covering the same assets
   - **Allow expiry** — accept that the content will become unavailable
6. Every change is audited with actor, timestamp, contract reference, and before/after
   state.
7. If renewed, availability continues seamlessly; caches update.
8. If allowed to expire, UF-19 section B executes automatically at the window end.

### Alternate paths
- **A1 — Partial renewal.** Some assets or some modes renewed, others not. Mode-level
  granularity is required, because renewing live without catch-up is a normal commercial
  outcome.
- **A2 — Territory change on renewal.** Some territories continue, others end. Enforcement
  follows automatically.
- **A3 — Emergency revocation.** Immediate effect; UF-19 section E.

### Failure paths
- **F1 — Renewal recorded without a contract reference.** Rejected. **Every rights record
  traces to an agreement.**
- **F2 — Overlapping or conflicting agreements.** Conflict surfaced for resolution; the
  system does not silently choose one.
- **F3 — Renewal recorded after expiry has already executed.** Content is restored on the
  new window, and the gap is visible in the audit trail rather than concealed.
- **F4 — Insufficient permission.** Denied server-side; audited. *(Whether Content
  Managers may edit rights at all is [OPEN — PD-068].)*

### Invariant
No operator action can keep expired content available. `disabled_by_rights` cannot be
overridden while the window is closed (FR-CHN-05).

**Requirements** FR-RGT-09, FR-RGT-10, FR-ADM-08, FR-ADM-09, FR-ADM-02

---

## UF-25 — Admin: playback session investigation

**Actor** Support operator or Operations · **Priority** P0

### Happy path
1. A subscriber reports "it won't play".
2. Operator opens Playback Sessions and searches by account, device, or session.
3. System returns the session and authorization history: timestamps, asset, device class,
   determined territory, applicable rights reference, decision, and **reason code**.
4. Operator identifies the cause — for example `NOT_IN_PACKAGE` rather than a technical
   failure.
5. Operator explains it to the subscriber and takes the appropriate action:
   - device limit → guide the subscriber through removal (UF-05B)
   - concurrency → identify the active session; terminate if the subscriber requests it
   - subscription issue → route to Finance
   - rights or territory → explain neutrally; **offer no workaround**
   - technical failure → escalate to Operations with the correlation ID
6. Every lookup and action is **audited** — support tooling is a common exfiltration path
   and is treated as one.

### Alternate paths
- **A1 — Multiple denials across many accounts.** Indicates a systemic issue; escalated to
  Operations; the dashboard denial-reason breakdown confirms the pattern.
- **A2 — Rights-related denial.** Escalated to content management to verify whether the
  rights state is correct.
- **A3 — Suspected credential sharing.** Surfaced per PD-042 — **no automated punitive
  action is assumed**.

### Failure paths
- **F1 — Session not found.** May predate retention; retention is stated to the operator
  rather than presented as an error.
- **F2 — Insufficient permission.** Denied server-side; audited.
- **F3 — Operator attempts to view credentials or payment data.** **No such view exists.**
  The data is not present in any admin surface.

### Invariants
- Support can see **why** playback was denied without seeing credentials, tokens, or card
  data.
- Support **cannot** grant entitlement, extend rights, or bypass authorization to "fix" a
  problem. The only remedies are correcting the underlying commercial or technical state.
- Viewing history access follows the privacy rules (PD-067) — investigating a playback
  failure does not license browsing what someone watches.

**Requirements** FR-ADM-10, FR-ADM-11, FR-ADM-04, FR-USR-05, FR-AUT-10, NFR-PRV-04

---

## Appendix A — Cross-flow invariants

These hold in **every** flow above. A flow that violates one is specified incorrectly.

| # | Invariant | Source |
|---|---|---|
| 1 | Playback always requires a backend authorization decision; a URL is never permission | `CLAUDE.md` §12 |
| 2 | The client is never the authority on entitlement | `CLAUDE.md` §4.2 |
| 3 | Client-reported location, device class, and time are never trusted | `CLAUDE.md` §12 |
| 4 | Every denial carries a stable machine-readable reason code | `CLAUDE.md` §10 |
| 5 | Every denial offers a path forward — a dead-end error is a defect | `PRODUCT_SPEC.md` §31 |
| 6 | Territory and rights denials offer no workaround and no hint of one | `CLAUDE.md` §1 |
| 7 | Errors never expose internals | `CLAUDE.md` §10 |
| 8 | Distribution modes are enforced independently | `DECISION_LOG.md` L-002 |
| 9 | Rights expiry is enforced by two independent mechanisms | `DECISION_LOG.md` L-003 |
| 10 | Every mutating operator action is audited with before/after state | `CLAUDE.md` §6.1 |
| 11 | No admin surface displays credentials, tokens, or card data | `CLAUDE.md` §6.2 |
| 12 | Profile switching is not authentication | `PRODUCT_SPEC.md` §3.1.6 |
| 13 | Entitlement changes invalidate caches immediately | `CLAUDE.md` §19 |
| 14 | Authentication responses never enable account enumeration | `CLAUDE.md` §7 |
| 15 | "You may not" is never confused with "we could not" | `PRODUCT_SPEC.md` §11.3 |

## Appendix B — Flows deliberately not specified

| Flow | Reason |
|---|---|
| Anonymous playback | Depends on PD-009; not assumed to exist |
| Trial signup | Depends on PD-011 |
| Advertising playback | Depends on PD-006; no ad capability assumed |
| Offline download | Depends on PD-016 and download rights |
| Recording / network DVR | Depends on PD-015 and recording rights |
| Support impersonation | Depends on PD-022 |
| Social login | Depends on PD-033 |
| Transactional purchase (PPV) | Depends on PD-007 |
| Multi-tenant operator onboarding | Depends on PD-008 |
