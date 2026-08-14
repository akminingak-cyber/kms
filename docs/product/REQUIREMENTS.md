# REQUIREMENTS.md — KMS TV Requirements and Acceptance Criteria

**Phase:** 1 — Product specification
**Status:** DRAFT — awaiting product approval · **PD-004 APPROVED (territories)**
**Version:** 1.1
**Date:** 2026-08-13 (rev. 1.1 — PD-004 approved: territory domain added)
**Source specification:** `PRODUCT_SPEC.md`
**Governing document:** `CLAUDE.md` (binding)

---

## 0. How this document works

### 0.1 Identifiers
- **Functional requirements:** `FR-<DOMAIN>-<nn>`
- **Non-functional requirements:** `NFR-<CATEGORY>-<nn>`
- **Acceptance criteria:** `AC-<requirement-id>-<n>`, in GIVEN / WHEN / THEN form
- **Edge cases:** `EC-<nn>`

Identifiers are permanent. A withdrawn requirement is marked withdrawn, never deleted and
never reused.

### 0.2 Priority classification

| Priority | Definition | Test |
|---|---|---|
| **P0** | **Mandatory for initial production.** Without it the platform cannot launch lawfully, safely, or credibly. | "Would launching without this expose the operator to legal, security, or commercial failure — or make the product not recognisably a TV service?" |
| **P1** | **Important.** Launch is possible without it, but the product is materially weaker and it should follow quickly. | "Does its absence visibly degrade the experience without endangering the operator?" |
| **P2** | **Later.** Valuable, planned, not launch-critical. | "Would a reasonable viewer not miss this at launch?" |
| **P3** | **Optional / future.** Directional; may never be built. | "Is this speculative or dependent on an unmade decision?" |

**The P0 principle for this product:** the P0 set is dominated by *authorization, rights
enforcement, and live television fundamentals*, not by feature count. A TV platform that
plays channels beautifully but cannot prove compliance is not launchable. A TV platform
with no recommendations engine is merely plainer.

### 0.3 Status markers
`[CONFIRMED]` · `[PROPOSED]` · `[OPEN — PD-nnn]` · `[LEGAL]` · `[UNVERIFIED]` — defined in
`PRODUCT_SPEC.md` §0.1.

### 0.4 Coverage rule
**Acceptance criteria are provided for every P0 requirement**, per the brief — all 190 of
them. They appear in two places:

- **In the domain sections (Parts A and B)** for the P0 requirements whose criteria
  carry the most design weight, where the criterion earns discussion.
- **In Part D** for the remainder, stated compactly. These are no less binding; they
  are separated only to keep the domain sections readable.

P1–P3 requirements carry acceptance criteria where they are already unambiguous. The
remainder are written when the requirement is scheduled, since criteria written against
undecided inputs (see `DECISIONS.md`) must be rewritten once the decision lands.

---

## 1. Requirement summary

Counts below are **derived by counting the requirement rows in this document**, not
estimated.

| Domain | P0 | P1 | P2 | P3 | Total |
|---|---:|---:|---:|---:|---:|
| Users & roles (USR) | 4 | 1 | 0 | 1 | 6 |
| Account (ACC) | 11 | 3 | 1 | 0 | 15 |
| Profiles (PRF) | 4 | 4 | 1 | 0 | 9 |
| Devices (DEV) | 7 | 2 | 1 | 1 | 11 |
| Home (HOME) | 3 | 4 | 1 | 0 | 8 |
| Live TV (LIV) | 7 | 4 | 1 | 0 | 12 |
| Channels (CHN) | 5 | 1 | 0 | 0 | 6 |
| EPG (EPG) | 7 | 3 | 1 | 0 | 11 |
| Player (PLY) | 8 | 3 | 2 | 0 | 13 |
| Playback authorization (AUT) | 11 | 1 | 0 | 0 | 12 |
| Packages (PKG) | 4 | 2 | 0 | 0 | 6 |
| Subscriptions (SUB) | 7 | 2 | 0 | 1 | 10 |
| Rights (RGT) | 9 | 2 | 0 | 0 | 11 |
| VOD (VOD) | 6 | 5 | 1 | 0 | 12 |
| Catch-up (CUP) | 0 | 6 | 0 | 0 | 6 |
| Restart (RST) | 0 | 5 | 0 | 0 | 5 |
| Search (SCH) | 1 | 5 | 2 | 1 | 9 |
| Favorites (FAV) | 0 | 4 | 1 | 0 | 5 |
| Watch history (HIS) | 3 | 3 | 0 | 0 | 6 |
| Recommendations (REC) | 1 | 3 | 1 | 1 | 6 |
| Notifications (NOT) | 3 | 3 | 1 | 0 | 7 |
| Admin (ADM) | 12 | 6 | 3 | 0 | 21 |
| Analytics (ANL) | 3 | 3 | 1 | 0 | 7 |
| Payments (PAY) | 6 | 2 | 1 | 0 | 9 |
| i18n (I18N) | 5 | 2 | 1 | 1 | 9 |
| Accessibility (A11Y) | 4 | 3 | 1 | 0 | 8 |
| Errors (ERR) | 4 | 1 | 0 | 0 | 5 |
| **Territories (TER)** — *added rev. 1.1* | **8** | **6** | **0** | **0** | **14** |
| **Functional total** | **143** | **89** | **21** | **6** | **259** |
| Security (NFR-SEC) | 14 | 2 | 0 | 0 | 16 |
| Privacy (NFR-PRV) | 8 | 2 | 1 | 0 | 11 |
| Performance (NFR-PER) | 9 | 3 | 0 | 0 | 12 |
| Scalability (NFR-SCL) | 6 | 2 | 0 | 0 | 8 |
| Observability (NFR-OBS) | 7 | 2 | 0 | 0 | 9 |
| Availability (NFR-AVL) | 3 | 1 | 0 | 0 | 4 |
| **Non-functional total** | **47** | **12** | **1** | **0** | **60** |
| **GRAND TOTAL** | **190** | **101** | **22** | **6** | **319** |

**Where the P0 weight sits.** Playback authorization (11), Rights (9), Security (14),
Admin (12), Account (11), Privacy (8), and Territories (8) together account for 73 of the
190 P0 requirements — well over a third — while Catch-up, Restart, and Favorites contribute
none. That distribution is the specification's central claim about this product: the
launch-critical work is authorization, rights, and accountability, not features.

---

# PART A — FUNCTIONAL REQUIREMENTS

## A1. Users and roles (USR)

| ID | Requirement | Priority |
|---|---|---|
| FR-USR-01 | The platform distinguishes viewer identities (accounts/profiles) from operator identities (staff with roles). The two systems are never conflated. | **P0** |
| FR-USR-02 | Entitlement (what a viewer may watch) and role (what a staff member may do) are separate authorization systems. | **P0** |
| FR-USR-03 | An anonymous visitor has no playback capability unless anonymous free playback is explicitly approved (PD-009). | **P0** |
| FR-USR-04 | No operator role grants viewing entitlement. A staff member wishing to watch requires an entitlement like any viewer. | **P0** |
| FR-USR-05 | Support staff can view a subscriber's playback denial reasons without accessing credentials or payment data. | P1 |
| FR-USR-06 | Support impersonation of a subscriber account. **[OPEN — PD-022]** | P3 |

**Acceptance criteria**

`AC-FR-USR-02-1`
GIVEN a staff member holding the Super Administrator role and no active subscription
WHEN they request playback authorization for any channel
THEN the platform denies playback with reason `NOT_IN_PACKAGE` or `SUBSCRIPTION_EXPIRED`
AND the denial is recorded in the compliance audit log.

`AC-FR-USR-03-1`
GIVEN an unauthenticated visitor
WHEN they request playback authorization for any asset
THEN the platform denies the request with an authentication error
AND no manifest, segment, or signed URL is returned.

`AC-FR-USR-04-1`
GIVEN any operator role
WHEN the role's permission set is evaluated
THEN it contains no permission that grants or implies content viewing entitlement.

---

## A2. Account (ACC)

| ID | Requirement | Priority |
|---|---|---|
| FR-ACC-01 | A visitor can register an account with an identifier and a password. | **P0** |
| FR-ACC-02 | Registration must not disclose whether an identifier already exists. | **P0** |
| FR-ACC-03 | A registered user can log in and receive a session bound to a device. | **P0** |
| FR-ACC-04 | Login must not disclose whether an identifier exists, by response content or by timing. | **P0** |
| FR-ACC-05 | A user can log out, ending the session and invalidating its tokens server-side. | **P0** |
| FR-ACC-06 | A user can end all sessions across all devices ("log out everywhere"). | **P0** |
| FR-ACC-07 | The account identifier is verified before privileged account operations. | **P0** |
| FR-ACC-08 | A user can reset a forgotten password via a time-limited, single-use, out-of-band token. | **P0** |
| FR-ACC-09 | A successful password reset invalidates all existing sessions and notifies the user. | **P0** |
| FR-ACC-10 | Account status (`pending_verification`, `active`, `suspended`, `closed`, `pending_deletion`) is evaluated at every playback authorization, not only at login. | **P0** |
| FR-ACC-11 | A user can view their active sessions and terminate any of them. | P1 |
| FR-ACC-12 | A user can view a security event timeline. | P1 |
| FR-ACC-13 | A user can request account deletion, which is implemented, tested, and effective. | **P0** |
| FR-ACC-14 | Account recovery beyond password reset is a manual, audited support process, never self-service. | P1 |
| FR-ACC-15 | The identity model supports multiple authentication methods per account without redesign (social login, MFA). | P2 |

**Acceptance criteria**

`AC-FR-ACC-02-1`
GIVEN an identifier that is already registered
WHEN a visitor submits it to the registration endpoint
THEN the response is indistinguishable in content, status code, and timing envelope from
the response for an unregistered identifier.

`AC-FR-ACC-04-1`
GIVEN an unregistered identifier and a registered identifier with a wrong password
WHEN each is submitted to login
THEN both produce identical error codes, identical messages, and response times within
the same statistical envelope.

`AC-FR-ACC-05-1`
GIVEN an authenticated session with playback in progress
WHEN the user logs out
THEN the session's tokens are rejected on their next use
AND playback stops within the documented revocation interval (PD-026).

`AC-FR-ACC-06-1`
GIVEN a user with active sessions on three devices
WHEN the user selects "log out everywhere"
THEN all three sessions are invalidated
AND all three devices' playback stops within the revocation interval.

`AC-FR-ACC-08-1`
GIVEN a password-reset token that has already been used once
WHEN it is submitted a second time
THEN the reset is rejected
AND no password change occurs.

`AC-FR-ACC-08-2`
GIVEN a password-reset request for an identifier that does not exist
WHEN the request is submitted
THEN the response is identical to that for an existing identifier
AND no message is sent.

`AC-FR-ACC-09-1`
GIVEN a user with four active sessions
WHEN the user completes a password reset
THEN all four sessions are invalidated
AND a security notification is delivered.

`AC-FR-ACC-10-1`
GIVEN an account whose status changes to `suspended` while a session is active
WHEN that session next requests playback authorization
THEN the request is denied with reason `ACCOUNT_SUSPENDED`.

`AC-FR-ACC-13-1`
GIVEN a user who has requested account deletion and whose grace period has elapsed
WHEN the deletion is executed
THEN personal data is removed or irreversibly anonymized per the retention policy
AND watch history is deleted
AND the deletion propagates to analytics
AND records retained for compliance no longer identify the person where achievable.

---

## A3. Profiles (PRF)

| ID | Requirement | Priority |
|---|---|---|
| FR-PRF-01 | An account supports multiple profiles, each with its own name, language, history, favorites, and resume points. | **P0** |
| FR-PRF-02 | Profile switching is not authentication and grants no additional privilege. | **P0** |
| FR-PRF-03 | Watch history is not exposed across profiles within an account. | **P0** |
| FR-PRF-04 | A profile carries a maturity limit that constrains which content it may access. | **P0** |
| FR-PRF-05 | A child profile receives stricter defaults and cannot access account settings or purchasing. | P1 |
| FR-PRF-06 | Changing parental controls or exiting a child profile requires the account PIN. | P1 |
| FR-PRF-07 | A profile may be PIN-protected, with the PIN verified server-side. | P1 |
| FR-PRF-08 | Profile preferences (language, avatar) synchronize across all devices. | P1 |
| FR-PRF-09 | Profile avatar selection from an operator-provided set; user image upload **[OPEN — PD-034]**. | P2 |

**Acceptance criteria**

`AC-FR-PRF-02-1`
GIVEN a session authenticated on a standard profile
WHEN the session switches to any other profile on the same account
THEN no new authentication occurs
AND the session's privileges are unchanged
AND no administrative capability is gained.

`AC-FR-PRF-03-1`
GIVEN profile A has watched a title
WHEN profile B on the same account requests its watch history or continue-watching list
THEN the title watched by profile A does not appear.

`AC-FR-PRF-04-1`
GIVEN a profile with a maturity limit below a title's rating
WHEN that profile requests playback authorization for the title
THEN the request is denied with a maturity-restriction reason code
AND the title is excluded from that profile's browse, search, and recommendation results.

---

## A4. Devices (DEV)

| ID | Requirement | Priority |
|---|---|---|
| FR-DEV-01 | A device is registered to an account on first authenticated use, recording class, identifier, name, platform, and version. | **P0** |
| FR-DEV-02 | Device registration is idempotent for the same physical device where the platform permits stable identification. | **P0** |
| FR-DEV-03 | Device class is recorded and used as a rights-relevant attribute in authorization. | **P0** |
| FR-DEV-04 | A maximum registered device count per account is enforced server-side. **[value OPEN — PD-038]** | **P0** |
| FR-DEV-05 | A user can view and remove registered devices. | **P0** |
| FR-DEV-06 | Removing a device invalidates its sessions and stops its playback within the revocation interval. | **P0** |
| FR-DEV-07 | A client-supplied device identifier is never accepted as proof of identity. | **P0** |
| FR-DEV-08 | Device registration is rate-limited. | P1 |
| FR-DEV-09 | Suspicious device behaviour is detected and surfaced to operations. | P1 |
| FR-DEV-10 | A cooling-off period on device slot reuse. **[OPEN — PD-039]** | P2 |
| FR-DEV-11 | Automated response to suspicious device behaviour. **[OPEN — PD-042]** | P3 |

**Acceptance criteria**

`AC-FR-DEV-02-1`
GIVEN a device already registered to an account
WHEN the application is reinstalled and the user signs in again on the same physical device
THEN no additional device slot is consumed
AND the existing registration is reused.

`AC-FR-DEV-04-1`
GIVEN an account at its device limit
WHEN a new device attempts registration
THEN registration is refused with reason `DEVICE_LIMIT_REACHED`
AND the user is offered the device-management surface.

`AC-FR-DEV-06-1`
GIVEN a device with playback in progress
WHEN the user removes that device from another device
THEN the removed device's playback stops within the documented revocation interval
AND its subsequent authorization requests are denied with `DEVICE_REVOKED`.

`AC-FR-DEV-07-1`
GIVEN a request carrying a device identifier belonging to a different account
WHEN authorization is evaluated
THEN the identifier is ignored as an identity claim
AND the session token alone determines identity.

---

## A5. Home experience (HOME)

| ID | Requirement | Priority |
|---|---|---|
| FR-HOME-01 | The home screen presents entitlement-filtered content, filtered server-side. | **P0** |
| FR-HOME-02 | The home screen shows "continue watching" for the active profile when in-progress items exist. | **P0** |
| FR-HOME-03 | The home screen shows currently live channels with their current programme. | **P0** |
| FR-HOME-04 | Sections render independently; one failed section does not blank the page. | P1 |
| FR-HOME-05 | Loading uses skeleton placeholders that preserve final layout without shift. | P1 |
| FR-HOME-06 | A new profile with no history receives a populated home screen, never an empty one. | P1 |
| FR-HOME-07 | Home section order and composition are operator-configurable without a client release. | P1 |
| FR-HOME-08 | The home screen surfaces upcoming programmes starting soon. | P2 |

**Acceptance criteria**

`AC-FR-HOME-01-1`
GIVEN a profile not entitled to a given channel
WHEN the home screen is requested
THEN that channel does not appear in any home section
AND the filtering occurred server-side, not in the client.

`AC-FR-HOME-02-1`
GIVEN a profile with a VOD title watched between the start and completion thresholds
WHEN the home screen loads
THEN the title appears in continue watching with its stored resume position.

`AC-FR-HOME-03-1`
GIVEN entitled channels currently broadcasting
WHEN the home screen loads
THEN each shows its current programme title and progress derived from EPG data in UTC.

---

## A6. Live TV (LIV)

| ID | Requirement | Priority |
|---|---|---|
| FR-LIV-01 | The channel list shows entitled channels in operator-defined order with logo, number, and current programme. | **P0** |
| FR-LIV-02 | Channels are organized into operator-defined groups and categories. | **P0** |
| FR-LIV-03 | A viewer can select a channel and begin playback. | **P0** |
| FR-LIV-04 | A viewer can step channels up and down. | **P0** |
| FR-LIV-05 | Current and next programme information is available during playback without leaving playback. | **P0** |
| FR-LIV-06 | A live indicator unambiguously shows whether playback is at the live edge. | **P0** |
| FR-LIV-07 | Every committed channel change issues a new playback authorization. | **P0** |
| FR-LIV-08 | Rapid channel stepping debounces authorization requests rather than issuing one per keypress. | P1 |
| FR-LIV-09 | A viewer can return to the previous channel with one action. | P1 |
| FR-LIV-10 | A viewer can search channels by name and number within Live TV. | P1 |
| FR-LIV-11 | A viewer can filter the channel list to favorites. | P1 |
| FR-LIV-12 | A viewer can choose channel sort order, persisted per profile. | P2 |

**Acceptance criteria**

`AC-FR-LIV-01-1`
GIVEN a profile entitled to a subset of channels
WHEN the channel list is requested
THEN only entitled channels are returned
AND unentitled channels are absent from the response payload entirely, not merely hidden.

`AC-FR-LIV-07-1`
GIVEN a viewer watching channel A under a valid authorization
WHEN the viewer switches to channel B
THEN a new playback authorization is requested and evaluated for channel B
AND channel A's authorization is not reused or extended.

`AC-FR-LIV-07-2`
GIVEN a viewer watching channel A and not entitled to channel B
WHEN the viewer switches to channel B
THEN authorization is denied with the applicable reason code
AND the viewer is returned to a defined state rather than a blank player.

`AC-FR-LIV-06-1`
GIVEN playback of a live channel at the live edge
WHEN the viewer restarts the programme or seeks backwards
THEN the live indicator changes to a non-live state
AND a "go live" action becomes available.

---

## A7. Channels (CHN)

| ID | Requirement | Priority |
|---|---|---|
| FR-CHN-01 | A channel carries the product metadata set defined in `PRODUCT_SPEC.md` §9. | **P0** |
| FR-CHN-02 | **A channel cannot reach `published` status without a rights reference.** | **P0** |
| FR-CHN-03 | A channel's stream source cannot be created without recorded provenance. | **P0** |
| FR-CHN-04 | A channel carries a playback policy stating restart, catch-up, and recording permissions. | **P0** |
| FR-CHN-05 | The system sets `disabled_by_rights` automatically on rights expiry; an operator cannot override it back to `published` while the window is closed. | **P0** |
| FR-CHN-06 | Channel visibility is constrained by package and device class. | P1 |

**Acceptance criteria**

`AC-FR-CHN-02-1`
GIVEN a channel with no rights reference
WHEN an operator attempts to publish it
THEN publication is rejected with a validation error naming the missing rights reference
AND the channel remains unpublished.

`AC-FR-CHN-03-1`
GIVEN a stream source submission with no recorded provenance
WHEN it is created
THEN creation is rejected
AND no stream source record exists.

`AC-FR-CHN-05-1`
GIVEN a published channel whose rights window has just ended
WHEN an operator attempts to set its status back to `published`
THEN the change is rejected
AND the channel remains `disabled_by_rights` until a valid rights window exists.

---

## A8. EPG (EPG)

| ID | Requirement | Priority |
|---|---|---|
| FR-EPG-01 | The guide provides current and next programme per channel. | **P0** |
| FR-EPG-02 | The guide provides a schedule grid of channels against time. | **P0** |
| FR-EPG-03 | All programme times are stored and computed in UTC and displayed in the viewer's local timezone. | **P0** |
| FR-EPG-04 | DST transitions are handled correctly in both directions. | **P0** |
| FR-EPG-05 | Missing EPG data for a channel does not prevent playback of that channel. | **P0** |
| FR-EPG-06 | EPG ingestion is idempotent — re-ingesting identical source data changes nothing. | **P0** |
| FR-EPG-07 | A failed ingest never replaces good data with partial data. | **P0** |
| FR-EPG-08 | Programme details are available: title, description, time, duration, genre, rating, artwork. | P1 |
| FR-EPG-09 | The viewer can navigate the guide by day within the retained range. | P1 |
| FR-EPG-10 | Schedule gaps and overlaps are detected, resolved by a documented rule, and flagged for operator review. | P1 |
| FR-EPG-11 | The viewer can search programmes by title within the retained range. | P2 |

**Acceptance criteria**

`AC-FR-EPG-03-1`
GIVEN a programme stored with a UTC start time
WHEN it is displayed to viewers in two different timezones
THEN each sees the correct local time for the same underlying instant.

`AC-FR-EPG-04-1`
GIVEN a schedule spanning a DST transition in the viewer's timezone
WHEN the guide is rendered across that transition
THEN no programme is duplicated, omitted, or displaced by an hour
AND programme durations remain correct.

`AC-FR-EPG-05-1`
GIVEN an entitled channel with no EPG data
WHEN the viewer selects it
THEN playback authorization proceeds normally and playback starts
AND the guide area shows "Programme information unavailable".

`AC-FR-EPG-06-1`
GIVEN an EPG source file already ingested successfully
WHEN the identical file is ingested again
THEN no programme record is duplicated, created, or modified
AND the ingest reports success with zero changes.

`AC-FR-EPG-07-1`
GIVEN existing valid EPG data for a channel
WHEN an ingest fails partway through
THEN the previously stored data remains intact and servable
AND the failure raises an operations alert.

---

## A9. Player (PLY)

| ID | Requirement | Priority |
|---|---|---|
| FR-PLY-01 | The player supports adaptive playback with volume, mute, and fullscreen on all platforms. | **P0** |
| FR-PLY-02 | The player implements the defined playback states and their transitions. | **P0** |
| FR-PLY-03 | Every player error shows a plain-language message, a recovery action, and a stable short error code. | **P0** |
| FR-PLY-04 | Player errors never expose stack traces, URLs, host names, vendor errors, or tokens. | **P0** |
| FR-PLY-05 | The player distinguishes "you may not" (authorization) from "we could not" (technical) in its messaging. | **P0** |
| FR-PLY-06 | On mid-stream session expiry, the player attempts one silent re-authorization before showing an error. | **P0** |
| FR-PLY-07 | Buffering does not immediately tear down the playback session. | **P0** |
| FR-PLY-08 | The player reports QoE telemetry: startup time, rebuffer ratio, bitrate distribution, failure reason. | **P0** |
| FR-PLY-09 | Subtitle track selection where subtitles are supplied. | P1 |
| FR-PLY-10 | Audio track selection where multiple tracks are supplied. | P1 |
| FR-PLY-11 | Seeking within VOD, and within the programme for catch-up and restart. | P1 |
| FR-PLY-12 | Manual quality selection where the platform permits. | P2 |
| FR-PLY-13 | Pause behaviour for live playback. **[OPEN — PD-046]** | P2 |

**Acceptance criteria**

`AC-FR-PLY-03-1`
GIVEN any player error condition
WHEN it is surfaced to the viewer
THEN the display includes a localized plain-language message, at least one actionable
recovery option, and a short stable code quotable to support.

`AC-FR-PLY-04-1`
GIVEN a backend failure producing an internal error
WHEN the error reaches the player
THEN the displayed text contains no stack trace, no URL, no host name, no SQL, no vendor
error text, and no token.

`AC-FR-PLY-05-1`
GIVEN an entitled viewer whose playback fails because the CDN is unreachable
WHEN the error is displayed
THEN the message indicates a technical problem and offers retry
AND does **not** state or imply that the subscription or entitlement is invalid.

`AC-FR-PLY-06-1`
GIVEN playback in progress whose playback session expires
WHEN expiry is detected
THEN the client requests re-authorization once without user-visible interruption
AND only on failure of that attempt is a recoverable error shown.

---

## A10. Playback authorization (AUT) — the P0 core

| ID | Requirement | Priority |
|---|---|---|
| FR-AUT-01 | Every playback requires a backend authorization decision. Possessing a manifest URL never permits playback. | **P0** |
| FR-AUT-02 | Authorization evaluates all **eleven** checks in `PRODUCT_SPEC.md` §12.1 on every request, with no skip path. | **P0** |
| FR-AUT-03 | Client-reported location, device class, and time are never authoritative. | **P0** |
| FR-AUT-04 | Every denial returns a stable, machine-readable reason code; clients never parse human text. | **P0** |
| FR-AUT-05 | Playback sessions are short-lived, bound to one viewer, device, and asset, and are revocable. | **P0** |
| FR-AUT-06 | Concurrency limits are enforced server-side and cannot be exceeded, including under concurrent requests. | **P0** |
| FR-AUT-07 | Geo-enforcement, where a rights agreement requires it, is enforced server-side. | **P0** |
| FR-AUT-08 | Manifest and segment URLs are short-lived, signed, and not guessable. | **P0** |
| FR-AUT-09 | Playback tokens are validated at the edge, not only at issuance. | **P0** |
| FR-AUT-10 | Every authorization decision — allow and deny — is logged as compliance evidence, without credentials or tokens. | **P0** |
| FR-AUT-11 | An abandoned session releases its concurrency slot within a bounded interval (PD-041). | **P0** |
| FR-AUT-12 | The viewer is shown which sessions are active when a concurrency denial occurs. **[OPEN — PD-047]** | P1 |

**Acceptance criteria**

`AC-FR-AUT-01-1`
GIVEN a valid manifest URL captured from an authorized session
WHEN it is requested by a different device with no authorization
THEN delivery is refused
AND no media segment is served.

`AC-FR-AUT-02-1`
GIVEN a viewer failing exactly one of the eleven authorization checks
WHEN authorization is requested
THEN the request is denied
AND the reason code identifies the specific failed check.

`AC-FR-AUT-02-2`
GIVEN a viewer in a territory where the service is not available
AND an asset whose content rights would otherwise permit playback there
WHEN authorization is requested
THEN the request is denied with `SERVICE_NOT_AVAILABLE`, not `TERRITORY_RESTRICTED`
AND the two conditions remain independently representable.

`AC-FR-AUT-03-1`
GIVEN a client supplying a location header indicating a permitted territory
AND the server-side determination indicates a restricted territory
WHEN authorization is evaluated
THEN the server-side determination governs and the request is denied with
`TERRITORY_RESTRICTED`.

`AC-FR-AUT-06-1`
GIVEN an account at its concurrency limit of N
WHEN N+1 authorization requests are issued simultaneously from different devices
THEN exactly N are authorized
AND the remainder are denied with `CONCURRENCY_LIMIT_REACHED`
AND no race condition permits N+1 concurrent sessions.

`AC-FR-AUT-08-1`
GIVEN a signed playback URL
WHEN any element of its signature or parameters is modified
THEN delivery is refused.

`AC-FR-AUT-08-2`
GIVEN a signed playback URL whose expiry has passed
WHEN it is requested
THEN delivery is refused regardless of the requester's entitlement.

`AC-FR-AUT-09-1`
GIVEN a playback token revoked after issuance
WHEN a segment is requested at the edge using that token
THEN the edge refuses delivery within the documented revocation interval
AND does not rely solely on the token's original validity.

`AC-FR-AUT-10-1`
GIVEN any authorization decision
WHEN the compliance log entry is inspected
THEN it records viewer, asset, device class, territory determination, rights reference,
decision, reason code, and timestamp
AND contains no credential, token, or password.

`AC-FR-AUT-11-1`
GIVEN a session that stops sending heartbeats
WHEN the abandonment interval elapses
THEN its concurrency slot is released
AND a new session can be authorized in its place.

---

## A11. Packages (PKG)

| ID | Requirement | Priority |
|---|---|---|
| FR-PKG-01 | A package defines the content and feature allowances an entitled account receives. | **P0** |
| FR-PKG-02 | Package activation makes entitlement effective immediately. | **P0** |
| FR-PKG-03 | Package expiration makes entitlement ineffective immediately. | **P0** |
| FR-PKG-04 | Package contents are evaluated by the entitlement engine, never by a client. | **P0** |
| FR-PKG-05 | A viewer can upgrade their package, effective immediately. | P1 |
| FR-PKG-06 | A viewer can downgrade their package per the defined timing rule (PD-048). | P1 |

**Acceptance criteria**

`AC-FR-PKG-02-1`
GIVEN an account whose package activates
WHEN playback authorization is requested for newly included content immediately afterwards
THEN authorization succeeds
AND no stale cached entitlement denies it.

`AC-FR-PKG-03-1`
GIVEN an account whose package expires while content is being browsed
WHEN playback authorization is requested for content in the expired package
THEN authorization is denied
AND cached entitlement does not permit access.

---

## A12. Subscriptions (SUB)

| ID | Requirement | Priority |
|---|---|---|
| FR-SUB-01 | Subscriptions implement the confirmed state set: `pending`, `active`, `past_due`, `paused`, `cancelled`, `expired`. | **P0** |
| FR-SUB-02 | Every state transition is explicit and audited; invalid transitions are rejected. | **P0** |
| FR-SUB-03 | Any transition that changes entitlement invalidates cached entitlement immediately. | **P0** |
| FR-SUB-04 | Subscription history is append-only. | **P0** |
| FR-SUB-05 | Renewal is idempotent — a repeated provider callback never double-charges or double-extends. | **P0** |
| FR-SUB-06 | A viewer can cancel; access continues to period end per PD-051. | **P0** |
| FR-SUB-07 | Monetary values are never floating-point and always carry a currency. | **P0** |
| FR-SUB-08 | Payment failure moves the subscription to `past_due` and notifies the viewer. | P1 |
| FR-SUB-09 | Renewal reminders and expiry warnings are sent. | P1 |
| FR-SUB-10 | Trial subscriptions. **[OPEN — PD-011]** | P3 |

**Acceptance criteria**

`AC-FR-SUB-02-1`
GIVEN a subscription in state `expired`
WHEN a transition directly to `past_due` is attempted
THEN the transition is rejected as invalid
AND the state remains `expired`
AND the attempt is recorded.

`AC-FR-SUB-03-1`
GIVEN an active viewer with cached entitlement
WHEN the subscription transitions to `expired`
THEN the next playback authorization is denied
AND the cached entitlement was invalidated at transition time, not at cache expiry.

`AC-FR-SUB-05-1`
GIVEN a renewal callback already processed
WHEN the identical callback is received again
THEN no additional charge, no additional period extension, and no duplicate record occurs
AND the response acknowledges receipt.

`AC-FR-SUB-07-1`
GIVEN any stored monetary amount
WHEN it is inspected
THEN it is an exact representation (integer minor units or fixed-precision decimal)
AND a currency is stored alongside it.

---

## A13. Rights (RGT) — the compliance core

| ID | Requirement | Priority |
|---|---|---|
| FR-RGT-01 | Every distributable asset carries complete rights metadata or is not distributable. | **P0** |
| FR-RGT-02 | The system can answer, for any asset: rights holder, contract reference, territories, window, device classes, and distribution modes. | **P0** |
| FR-RGT-03 | Distribution modes (live, catch-up, restart, VOD, recording, download) are granted and enforced independently. | **P0** |
| FR-RGT-04 | Content becomes available automatically at rights window start. | **P0** |
| FR-RGT-05 | **Content becomes unavailable automatically at rights window end.** | **P0** |
| FR-RGT-06 | Expiry is enforced by two independent mechanisms: a scheduled job and a re-check at every authorization. | **P0** |
| FR-RGT-07 | Rights revocation takes effect immediately, ahead of the window end. | **P0** |
| FR-RGT-08 | Rights changes invalidate cached entitlement immediately. | **P0** |
| FR-RGT-09 | All rights records and changes are auditable with contract reference. | **P0** |
| FR-RGT-10 | Operators receive warnings for rights approaching expiry at a configured lead time (PD-055). | P1 |
| FR-RGT-11 | Content approaching window end may be surfaced to viewers as "leaving soon". | P1 |

**Acceptance criteria**

`AC-FR-RGT-03-1`
GIVEN a programme with live rights but **without** catch-up rights
WHEN a viewer requests catch-up playback of that programme
THEN authorization is denied with a rights reason code
AND the catch-up affordance was not offered for that programme.

`AC-FR-RGT-03-2`
GIVEN a channel with live rights but without restart rights
WHEN a viewer opens the live player
THEN no restart affordance is displayed
AND a direct restart request is denied.

`AC-FR-RGT-05-1`
GIVEN an asset whose rights window ends at time T
WHEN playback authorization is requested at T plus one second
THEN authorization is denied with `RIGHTS_EXPIRED`.

`AC-FR-RGT-06-1`
GIVEN the scheduled rights-expiry job is disabled
AND an asset whose rights window has ended
WHEN playback authorization is requested
THEN authorization is still denied by the per-request re-check.

`AC-FR-RGT-06-2`
GIVEN the per-request re-check is exercised in isolation
AND an asset whose rights window has ended
WHEN the scheduled job runs
THEN the asset is set to `disabled_by_rights` and removed from browse surfaces.

`AC-FR-RGT-07-1`
GIVEN an active rights agreement that is revoked
WHEN revocation is recorded
THEN the affected content is removed from browse within the cache-invalidation interval
AND subsequent authorization requests are denied
AND in-progress sessions are handled per the decided policy (PD-056).

`AC-FR-RGT-09-1`
GIVEN any modification to a rights agreement
WHEN the audit record is inspected
THEN it identifies the actor, the timestamp, the contract reference, and the before and
after state.

---

## A14. VOD (VOD)

| ID | Requirement | Priority |
|---|---|---|
| FR-VOD-01 | The catalogue supports movies, series, seasons, episodes, and documentaries. | **P0** |
| FR-VOD-02 | Catalogue browse is entitlement-filtered server-side. | **P0** |
| FR-VOD-03 | Title detail shows synopsis, duration, rating, artwork, and availability. | **P0** |
| FR-VOD-04 | Series navigation supports season selection and next episode. | **P0** |
| FR-VOD-05 | VOD availability is governed by the rights system, not by a catalogue flag. | **P0** |
| FR-VOD-06 | Resume position is stored per profile and synchronized across devices. | **P0** |
| FR-VOD-07 | Subtitles and audio tracks are selectable where supplied. | P1 |
| FR-VOD-08 | Categories and genres are navigable. | P1 |
| FR-VOD-09 | Catalogue filters by genre, language, year, and rating. | P1 |
| FR-VOD-10 | Trailers where supplied and rights-permitted. | P1 |
| FR-VOD-11 | Watchlist per profile. | P1 |
| FR-VOD-12 | Sorting by recency, popularity, and alphabetical. | P2 |

**Acceptance criteria**

`AC-FR-VOD-05-1`
GIVEN a VOD title whose rights window has ended
WHEN the catalogue is browsed
THEN the title is absent
AND a direct playback request for it is denied with `RIGHTS_EXPIRED`.

`AC-FR-VOD-06-1`
GIVEN a profile that watched 20 minutes of a title on device A
WHEN the same profile opens the title on device B
THEN playback offers to resume from approximately 20 minutes
AND the resume position was synchronized server-side.

`AC-FR-VOD-04-1`
GIVEN an episode reaching its end
WHEN playback completes
THEN the next episode in sequence is offered
AND the offer respects entitlement and maturity limits.

---

## A15. Catch-up TV (CUP)

All P1 — valuable, not launch-blocking, and dependent on rights availability.

| ID | Requirement | Priority |
|---|---|---|
| FR-CUP-01 | Past programmes are selectable from the guide where catch-up is available. | P1 |
| FR-CUP-02 | The catch-up window is configurable per channel and per rights agreement; no universal duration is assumed. | P1 |
| FR-CUP-03 | Catch-up requires catch-up distribution rights; live rights are insufficient. | P1 |
| FR-CUP-04 | Catch-up playback is bounded to the programme with configurable padding. | P1 |
| FR-CUP-05 | Seeking is permitted within the programme only. | P1 |
| FR-CUP-06 | On window expiry the programme becomes unavailable and its recording is removed. | P1 |

---

## A16. Restart TV (RST)

| ID | Requirement | Priority |
|---|---|---|
| FR-RST-01 | A restart affordance appears only when restart is genuinely available for the programme. | P1 |
| FR-RST-02 | Restart requires restart distribution rights. | P1 |
| FR-RST-03 | Restart transitions playback to the programme start with clear non-live indication. | P1 |
| FR-RST-04 | A "go live" action is always available during a restarted session. | P1 |
| FR-RST-05 | If restart fails, playback falls back to live rather than failing the session. | P1 |

---

## A17. Search (SCH)

| ID | Requirement | Priority |
|---|---|---|
| FR-SCH-01 | Search results are entitlement-filtered server-side. | **P0** |
| FR-SCH-02 | Global search covers channels, programmes, movies, series, and episodes. | P1 |
| FR-SCH-03 | Search handles Georgian, Cyrillic, and Latin scripts for input, indexing, and collation. | P1 |
| FR-SCH-04 | Autocomplete suggestions appear as the query is typed, debounced. | P1 |
| FR-SCH-05 | Typo tolerance across all supported scripts. | P1 |
| FR-SCH-06 | A no-result state offers alternatives rather than a dead end. | P1 |
| FR-SCH-07 | Results are filterable by type, genre, language, and availability. | P2 |
| FR-SCH-08 | TV search provides a remote-navigable on-screen keyboard supporting all scripts. | P2 |
| FR-SCH-09 | Cross-script transliteration matching. **[OPEN — PD-060]** | P3 |

**Acceptance criteria**

`AC-FR-SCH-01-1`
GIVEN content the profile is not entitled to
WHEN a search query that would otherwise match it is submitted
THEN the content does not appear in results
AND it is absent from the response payload.

---

## A18. Favorites (FAV)

| ID | Requirement | Priority |
|---|---|---|
| FR-FAV-01 | A profile can favorite channels. | P1 |
| FR-FAV-02 | A profile can add movies and series to a watchlist. | P1 |
| FR-FAV-03 | Favorites synchronize immediately across all devices for that profile. | P1 |
| FR-FAV-04 | A favorite that becomes unentitled remains saved and is shown as unavailable, never silently deleted. | P1 |
| FR-FAV-05 | Favorite channels are user-reorderable. | P2 |

---

## A19. Watch history (HIS)

| ID | Requirement | Priority |
|---|---|---|
| FR-HIS-01 | Watch history is recorded per profile. | **P0** |
| FR-HIS-02 | Watch history is deletable by the user, per item and in bulk, and deletion is effective. | **P0** |
| FR-HIS-03 | Watch history is treated as sensitive personal data in access control, analytics, and retention. | **P0** |
| FR-HIS-04 | Continue-watching is derived from history using defined thresholds (PD-062). | P1 |
| FR-HIS-05 | Resume positions synchronize across devices with a defined conflict-resolution rule. | P1 |
| FR-HIS-06 | History deletion propagates to analytics. | P1 |

**Acceptance criteria**

`AC-FR-HIS-02-1`
GIVEN a profile with watch history
WHEN the user deletes an item
THEN it no longer appears in history, continue-watching, or recommendations
AND the deletion propagates to analytics within the documented interval.

`AC-FR-HIS-03-1`
GIVEN any role other than those with an approved, recorded purpose
WHEN access to an identifiable viewer's watch history is attempted
THEN access is denied
AND the attempt is audited.

---

## A20. Recommendations (REC)

| ID | Requirement | Priority |
|---|---|---|
| FR-REC-01 | Recommendations never include content the profile is not entitled to or that exceeds its maturity limit. | **P0** |
| FR-REC-02 | Recommendations are deterministic: identical inputs produce identical output. | P1 |
| FR-REC-03 | Recommendations are computed per profile, never per account. | P1 |
| FR-REC-04 | Every recommendation carries a recorded reason. | P1 |
| FR-REC-05 | The ordered deterministic fallback in `PRODUCT_SPEC.md` §22.2 is implemented. | P2 |
| FR-REC-06 | Machine-learning recommendations. | P3 |

**Acceptance criteria**

`AC-FR-REC-01-1`
GIVEN a profile with a maturity limit and a partial entitlement
WHEN recommendations are generated
THEN every returned item is both within entitlement and within the maturity limit
AND no item requires an upgrade to play.

---

## A21. Notifications (NOT)

| ID | Requirement | Priority |
|---|---|---|
| FR-NOT-01 | Security notifications (new device login, password change, MFA change) are sent and cannot be disabled. | **P0** |
| FR-NOT-02 | No notification payload contains credentials, tokens, or sensitive personal data. | **P0** |
| FR-NOT-03 | All notification templates are localized into all supported languages. | **P0** |
| FR-NOT-04 | Payment and subscription transactional notifications are sent. | P1 |
| FR-NOT-05 | Viewers can manage preferences per category and channel. | P1 |
| FR-NOT-06 | Content and reminder notifications are opt-in or opt-out per the decided policy (PD-065). | P1 |
| FR-NOT-07 | Operator service announcements can be scheduled and targeted. | P2 |

**Acceptance criteria**

`AC-FR-NOT-01-1`
GIVEN a successful login from a previously unseen device
WHEN the login completes
THEN a security notification is delivered to the account identifier
AND the notification cannot be suppressed by user preference.

`AC-FR-NOT-02-1`
GIVEN any push notification payload
WHEN it is inspected
THEN it contains no token, no credential, no payment data, and no content that would
disclose sensitive viewing behaviour on a lock screen.

---

## A22. Admin Control Center (ADM)

| ID | Requirement | Priority |
|---|---|---|
| FR-ADM-01 | Every admin action is permission-gated server-side, never only in the UI. | **P0** |
| FR-ADM-02 | Every mutating admin action is audited with actor, timestamp, and before/after state. | **P0** |
| FR-ADM-03 | Audit logs are append-only and cannot be edited or deleted by any role. | **P0** |
| FR-ADM-04 | No admin view displays credentials, tokens, or payment card data. | **P0** |
| FR-ADM-05 | No role can grant itself privileges or escalate its own permissions. | **P0** |
| FR-ADM-06 | MFA is required for all administrative accounts. | **P0** |
| FR-ADM-07 | Operators can create, edit, and publish channels, with a mandatory rights reference. | **P0** |
| FR-ADM-08 | Operators can create and manage rights agreements and attach them to assets. | **P0** |
| FR-ADM-09 | Operators can view rights expiring within a configured horizon and act on them. | **P0** |
| FR-ADM-10 | Operators can search playback sessions and inspect authorization decisions and denial reasons. | **P0** |
| FR-ADM-11 | Operators can view, remove, and revoke subscriber devices and sessions. | **P0** |
| FR-ADM-12 | Operators can trigger and monitor EPG ingest and review gaps, overlaps, and failures. | **P0** |
| FR-ADM-13 | Operators can manage the VOD catalogue including series structure. | P1 |
| FR-ADM-14 | Operators can manage packages and their contents. | P1 |
| FR-ADM-15 | Operators can view subscriptions and payment transactions and process refunds where permitted. | P1 |
| FR-ADM-16 | Operators can monitor streaming health: sources, encoders, origins, CDN. | P1 |
| FR-ADM-17 | Bulk and destructive operations require preview and explicit confirmation. | P1 |
| FR-ADM-18 | The dashboard surfaces health, concurrency, playback success, denial reasons, rights expiry, and ingest status. | P1 |
| FR-ADM-19 | Operators can manage categories, assets, and artwork. | P2 |
| FR-ADM-20 | Operators can manage notification templates and announcements. | P2 |
| FR-ADM-21 | Operators can manage promotions. | P2 |

**Acceptance criteria**

`AC-FR-ADM-01-1`
GIVEN a staff member whose role lacks a given permission
WHEN they issue the corresponding request directly, bypassing the UI
THEN the request is denied server-side
AND the attempt is audited.

`AC-FR-ADM-03-1`
GIVEN any role including Super Administrator
WHEN an attempt is made to modify or delete an audit record
THEN the operation is rejected
AND the attempt is itself audited.

`AC-FR-ADM-05-1`
GIVEN an Administrator
WHEN they attempt to assign themselves the Super Administrator role or add a permission
to their own role
THEN the operation is denied
AND the attempt is audited.

`AC-FR-ADM-04-1`
GIVEN any admin screen displaying subscriber or payment information
WHEN the response payload is inspected
THEN it contains no password hash, no token, no PAN, no CVV, and no full card number.

`AC-FR-ADM-09-1`
GIVEN rights agreements expiring within the configured horizon
WHEN an operator opens the rights expiration view
THEN all such agreements are listed with their assets, windows, and contract references.

`AC-FR-ADM-10-1`
GIVEN a viewer whose playback was denied
WHEN an operator searches that viewer's playback sessions
THEN the denial is visible with its reason code and evaluation context
AND no credential or token is displayed.

---

## A23. Analytics (ANL)

| ID | Requirement | Priority |
|---|---|---|
| FR-ANL-01 | Product analytics, operational monitoring, and security auditing are separate systems with distinct access controls and retention. | **P0** |
| FR-ANL-02 | Product analytics uses pseudonymous identifiers by default. | **P0** |
| FR-ANL-03 | Every analytics event has a documented purpose; events without one are not collected. | **P0** |
| FR-ANL-04 | Playback failures are tracked by reason code. | P1 |
| FR-ANL-05 | Entitlement denials are tracked by reason. | P1 |
| FR-ANL-06 | Watch time, popular channels, and popular content are reportable. | P1 |
| FR-ANL-07 | Subscription conversion and churn are reportable. | P2 |

**Acceptance criteria**

`AC-FR-ANL-02-1`
GIVEN any product analytics event
WHEN it is inspected
THEN it carries a pseudonymous identifier
AND joining it to an identity requires a separately recorded, approved purpose.

`AC-FR-ANL-01-1`
GIVEN an Analyst role
WHEN access to the security audit log is attempted
THEN access is denied
AND the attempt is audited.

---

## A24. Payments (PAY)

| ID | Requirement | Priority |
|---|---|---|
| FR-PAY-01 | **Card data never touches KMS TV servers.** Provider-hosted or tokenized flows only. | **P0** |
| FR-PAY-02 | PAN, CVV, and track data are never stored, logged, or transmitted through KMS TV systems. | **P0** |
| FR-PAY-03 | Payment provider callbacks are signature-verified. | **P0** |
| FR-PAY-04 | Payment callbacks are idempotent — duplicates cause no additional effect. | **P0** |
| FR-PAY-05 | Delayed or out-of-order callbacks do not resurrect a superseded subscription state. | **P0** |
| FR-PAY-06 | Payment provider integration sits behind an abstraction with no vendor type in domain code. | **P0** |
| FR-PAY-07 | Refunds are permission-gated, audited, and have defined entitlement consequences. | P1 |
| FR-PAY-08 | Automated reconciliation against provider records. | P1 |
| FR-PAY-09 | Invoices/receipts available to the viewer. **[OPEN — PD-073]** | P2 |

**Acceptance criteria**

`AC-FR-PAY-01-1`
GIVEN a complete purchase flow
WHEN all KMS TV logs, databases, and request payloads are inspected
THEN no PAN, CVV, or track data is present anywhere.

`AC-FR-PAY-03-1`
GIVEN a payment callback with an invalid or absent signature
WHEN it is received
THEN it is rejected without effect
AND the rejection is logged.

`AC-FR-PAY-04-1`
GIVEN a payment callback already processed
WHEN the identical callback is received again
THEN no additional charge, entitlement change, or record is created.

`AC-FR-PAY-05-1`
GIVEN a subscription already advanced to a later state
WHEN a delayed callback for an earlier state arrives
THEN the earlier state is not restored
AND the callback is acknowledged and logged.

---

## A25. Internationalization (I18N)

| ID | Requirement | Priority |
|---|---|---|
| FR-I18N-01 | All user-visible strings are externalized; no hard-coded strings on any platform. | **P0** |
| FR-I18N-02 | Georgian, English, Russian, and Spanish are supported. | **P0** |
| FR-I18N-03 | Georgian (Mkhedruli), Cyrillic, and Latin scripts render, input, sort, and search correctly on every platform. | **P0** |
| FR-I18N-04 | Times are computed in UTC and rendered in the viewer's timezone with locale-appropriate formatting. | **P0** |
| FR-I18N-05 | Currency is always displayed with its currency indicator. | **P0** |
| FR-I18N-06 | Language is selectable per profile and persisted server-side. | P1 |
| FR-I18N-07 | Content metadata is localizable with per-item fallback. | P1 |
| FR-I18N-08 | A defined fallback chain applies when a translation is absent (PD-078). | P2 |
| FR-I18N-09 | RTL layout support. | P3 |

**Acceptance criteria**

`AC-FR-I18N-01-1`
GIVEN any client build
WHEN it is scanned for user-visible string literals
THEN all such strings resolve through the localization system.

`AC-FR-I18N-03-1`
GIVEN titles in Georgian, Russian, and English
WHEN they are displayed, sorted, and searched on each supported platform including TV
THEN all render without missing glyphs, sort per locale rules, and are findable by search.

---

## A26. Accessibility (A11Y)

| ID | Requirement | Priority |
|---|---|---|
| FR-A11Y-01 | Exactly one element holds visible focus at all times on TV platforms; focus is never lost or trapped. | **P0** |
| FR-A11Y-02 | All TV functionality is reachable by directional navigation alone. | **P0** |
| FR-A11Y-03 | Subtitles are displayed where supplied, on all platforms. | **P0** |
| FR-A11Y-04 | Web functionality is fully keyboard-navigable. | **P0** |
| FR-A11Y-05 | Contrast minimums are met, with TV readability at viewing distance. | P1 |
| FR-A11Y-06 | Screen reader support where the platform provides one. | P1 |
| FR-A11Y-07 | Subtitle appearance is user-configurable. | P1 |
| FR-A11Y-08 | Audio description support. **[OPEN — PD-080]** | P2 |

**Acceptance criteria**

`AC-FR-A11Y-01-1`
GIVEN any TV screen
WHEN every directional input is exercised exhaustively
THEN focus is always visible, never lost, and every focusable element is reachable and
escapable.

`AC-FR-A11Y-02-1`
GIVEN a TV client with no pointer input
WHEN every user journey is attempted using directional keys, OK, and Back only
THEN every journey completes.

---

## A27. Error handling (ERR)

| ID | Requirement | Priority |
|---|---|---|
| FR-ERR-01 | Every error state defines a user message, a recovery action, a logging requirement, and an analytics event. | **P0** |
| FR-ERR-02 | Error responses never leak internals — no stack traces, SQL, file paths, host names, or vendor errors. | **P0** |
| FR-ERR-03 | Territory and rights denials are neutral and offer no workaround, hint, or suggestion of one. | **P0** |
| FR-ERR-04 | Every error carries a stable, short, quotable code. | **P0** |
| FR-ERR-05 | Error messages are localized into all supported languages. | P1 |

**Acceptance criteria**

`AC-FR-ERR-02-1`
GIVEN any server-side exception
WHEN the response reaches any client
THEN it contains no stack trace, SQL fragment, file path, internal host name, or verbatim
upstream vendor error.

`AC-FR-ERR-03-1`
GIVEN a viewer denied for territory or expired rights
WHEN the error is displayed
THEN the message is neutral
AND it contains no reference to VPNs, proxies, location changes, or any other means of
obtaining access.

---

## A28. Territories (TER)

Arising from **PD-004 — APPROVED · FINAL** (2026-08-13): Georgia launch, multi-territory
architecture, future territories configurable. `PRODUCT_SPEC.md` §2.3 and §2.3.1 govern.

| ID | Requirement | Priority |
|---|---|---|
| FR-TER-01 | Territory is a first-class, configurable concept. **No component hard-codes Georgia, or any other territory, as the only possible territory.** | **P0** |
| FR-TER-02 | **App distribution, service availability, and content rights are three independent concepts.** No component derives one from another. | **P0** |
| FR-TER-03 | Installing or opening the application confers no access to content, in any territory. | **P0** |
| FR-TER-04 | Service availability is configurable per territory and enforced server-side. | **P0** |
| FR-TER-05 | Content availability is controlled independently per territory, through the rights system, not through a catalogue flag. | **P0** |
| FR-TER-06 | Playback authorization evaluates service availability as a check distinct from content rights, with a distinct reason code. | **P0** |
| FR-TER-07 | A new territory is addable through configuration and data, without changes to the core platform's structure. | **P0** |
| FR-TER-08 | Packages are territory-scoped: a package may be offered in one territory and not another. | P1 |
| FR-TER-09 | Pricing and currency are territory-scoped. | P1 |
| FR-TER-10 | Payment methods are territory-scoped. | P1 |
| FR-TER-11 | Tax configuration is territory-scoped. | P1 |
| FR-TER-12 | Localization defaults are territory-scoped, independently of the profile's language choice. | P1 |
| FR-TER-13 | A person in a territory where the service is unavailable receives a clear, non-error "not available yet" state, never a failure screen and never a workaround suggestion. | **P0** |
| FR-TER-14 | Operators can view and configure territory availability, and see which territories each rights agreement covers. | P1 |

**Acceptance criteria**

`AC-FR-TER-01-1`
GIVEN the complete codebase and configuration
WHEN searched for a hard-coded territory identifier used as the sole or default territory
THEN no occurrence exists outside configuration and seed data
AND the check is part of the CI gate.

`AC-FR-TER-02-1`
GIVEN a territory where the application is installable, the service is unavailable, and an
asset's rights would permit playback
WHEN each of the three concepts is evaluated
THEN each returns its own independent result
AND no result is inferred from another.

`AC-FR-TER-03-1`
GIVEN a person who has installed the application in a territory where the service is not
available
WHEN they open the application and attempt any playback
THEN no content is served
AND no registration or subscription is possible
AND the application shows the "not available yet" state rather than an error.

`AC-FR-TER-04-1`
GIVEN a territory whose service availability is switched off in configuration
WHEN a request originates from that territory
THEN registration, subscription, and playback are all refused server-side
AND the refusal does not depend on any client-side check.

`AC-FR-TER-05-1`
GIVEN an asset whose rights cover territory X but not territory Y
AND the service is available in both X and Y
WHEN a viewer in Y browses the catalogue
THEN the asset is absent from their catalogue
AND a direct playback request is denied with `TERRITORY_RESTRICTED`.

`AC-FR-TER-06-1`
GIVEN two viewers, one in an unserved territory and one in a served territory requesting an
asset unlicensed there
WHEN each requests playback
THEN the first receives `SERVICE_NOT_AVAILABLE` and the second `TERRITORY_RESTRICTED`
AND the codes are never used interchangeably.

`AC-FR-TER-07-1`
GIVEN a new territory to be added
WHEN it is introduced
THEN it is added through configuration, reference data, and rights agreements alone
AND no schema change, code change, or redeployment of core services is required to
represent it.

`AC-FR-TER-13-1`
GIVEN a person in a territory where the service is unavailable
WHEN the "not available yet" state is displayed
THEN it contains no reference to VPNs, proxies, location changes, or any other means of
obtaining access.

---

# PART B — NON-FUNCTIONAL REQUIREMENTS

## B1. Security (NFR-SEC)

| ID | Requirement | Priority |
|---|---|---|
| NFR-SEC-01 | Deny by default: every endpoint, queue consumer, and admin action requires an explicit grant. | **P0** |
| NFR-SEC-02 | All input validated at the boundary for type, range, length, format, and authorization context. | **P0** |
| NFR-SEC-03 | Parameterized queries only; no string-concatenated SQL. | **P0** |
| NFR-SEC-04 | Passwords hashed with a memory-hard algorithm. | **P0** |
| NFR-SEC-05 | No secrets in the repository; secrets rotatable without a code change. | **P0** |
| NFR-SEC-06 | All transport is TLS, including internal service-to-service traffic. | **P0** |
| NFR-SEC-07 | Rate limiting on authentication, registration, password reset, device registration, playback authorization, and payment endpoints. | **P0** |
| NFR-SEC-08 | Audit logging of every privileged action: who, what, when, from where, before/after. | **P0** |
| NFR-SEC-09 | Security headers on all web responses. | **P0** |
| NFR-SEC-10 | **No authentication bypass mode exists on any branch that can reach production.** | **P0** |
| NFR-SEC-11 | Token revocation takes effect for playback within a bounded, documented interval (PD-026). | **P0** |
| NFR-SEC-12 | Least privilege for database users, service accounts, and API keys; runtime DB user holds no DDL rights in production. | **P0** |
| NFR-SEC-13 | Dependency and container vulnerability scanning blocks the build on critical and high findings. | **P0** |
| NFR-SEC-14 | CSRF, session fixation, and replay protections are present and tested. | **P0** |
| NFR-SEC-15 | Breached-password checking at registration and password change. | P1 |
| NFR-SEC-16 | Suspicious-behaviour detection and alerting. | P1 |

**Acceptance criteria**

`AC-NFR-SEC-01-1`
GIVEN an endpoint with no explicit authorization policy
WHEN it is invoked
THEN the request is denied
AND the absence of a policy fails the build or the security test suite.

`AC-NFR-SEC-10-1`
GIVEN the complete codebase on any branch
WHEN it is searched for authentication-bypass mechanisms — environment flags, debug
routes, seeded superusers, or skip conditions
THEN none is found
AND the search is part of the CI security gate.

`AC-NFR-SEC-11-1`
GIVEN an active playback session
WHEN its token is revoked
THEN playback stops within the documented interval
AND the interval is measured and recorded, not asserted.

---

## B2. Privacy (NFR-PRV)

| ID | Requirement | Priority |
|---|---|---|
| NFR-PRV-01 | Every personal data field has a documented, stated purpose. | **P0** |
| NFR-PRV-02 | Retention periods are defined per data category and enforced automatically. | **P0** |
| NFR-PRV-03 | Account deletion is implemented, tested, and propagates to analytics and backups within the documented window. | **P0** |
| NFR-PRV-04 | Viewing history is classified and handled as sensitive personal data. | **P0** |
| NFR-PRV-05 | Viewing history is not exposed across profiles without explicit design. | **P0** |
| NFR-PRV-06 | No credentials, tokens, session identifiers, playback keys, payment data, or full personal records appear in any log. | **P0** |
| NFR-PRV-07 | Children's profiles receive stricter privacy defaults. | **P0** |
| NFR-PRV-08 | Third-party SDKs are reviewed for data collection before inclusion, and their collection is disclosed. | **P0** |
| NFR-PRV-09 | Users can obtain an export of their data. | P1 |
| NFR-PRV-10 | Data processing locations and cross-border transfers are documented before launch. | P1 |
| NFR-PRV-11 | Analytics preference controls. **[OPEN — PD-084]** | P2 |

**Acceptance criteria**

`AC-NFR-PRV-06-1`
GIVEN the complete log output of a full user journey including login, playback, and payment
WHEN it is scanned for sensitive patterns
THEN no credential, token, session identifier, playback key, or card datum is present.

`AC-NFR-PRV-03-1`
GIVEN a completed account deletion
WHEN analytics stores and backups are examined after the documented propagation window
THEN no personal data identifying the deleted account remains.

---

## B3. Performance (NFR-PER)

All targets are **PROPOSED — REQUIRES VALIDATION** (PD-087) and validated in Phase 32 on
the **minimum** supported device per platform.

| ID | Requirement | Target | Priority |
|---|---|---|---|
| NFR-PER-01 | Playback authorization p95 latency | ≤ 250 ms | **P0** |
| NFR-PER-02 | Playback authorization p99 latency | ≤ 600 ms | **P0** |
| NFR-PER-03 | API p95 latency, catalogue reads | ≤ 300 ms | **P0** |
| NFR-PER-04 | Time to first frame, live and VOD | ≤ 2.0 s | **P0** |
| NFR-PER-05 | Channel change time on minimum TV device | ≤ 2.0 s | **P0** |
| NFR-PER-06 | Rebuffer ratio | ≤ 0.5% of watch time | **P0** |
| NFR-PER-07 | EPG grid initial load | ≤ 1.5 s | **P0** |
| NFR-PER-08 | App cold start on minimum TV device | ≤ 5.0 s to interactive | **P0** |
| NFR-PER-09 | Performance is measured on the lowest supported device class, never a development machine. | — | **P0** |
| NFR-PER-10 | Catalogue browse load | ≤ 1.0 s | P1 |
| NFR-PER-11 | Search first results | ≤ 500 ms | P1 |
| NFR-PER-12 | Admin page load | ≤ 2.0 s | P1 |

**Acceptance criteria**

`AC-NFR-PER-01-1`
GIVEN production-representative load including a live-event concurrency spike
WHEN playback authorization latency is measured server-side
THEN p95 ≤ 250 ms and p99 ≤ 600 ms
AND the measurement is from real instrumentation, not estimation.

`AC-NFR-PER-09-1`
GIVEN each client platform
WHEN performance targets are validated
THEN validation is performed on the declared minimum supported device
AND results from higher-specification devices do not substitute.

---

## B4. Scalability (NFR-SCL)

| ID | Requirement | Priority |
|---|---|---|
| NFR-SCL-01 | The application tier is stateless; adding an instance is always possible. | **P0** |
| NFR-SCL-02 | Segment delivery is served from the edge; the application tier never proxies segments. | **P0** |
| NFR-SCL-03 | The origin is unreachable from the public internet. | **P0** |
| NFR-SCL-04 | Long-running work is queued with bounded concurrency and dead-letter handling. | **P0** |
| NFR-SCL-05 | Every external dependency has a defined, tested behaviour when slow or unavailable. | **P0** |
| NFR-SCL-06 | The platform survives the concurrency spike at the start of a popular live event. | **P0** |
| NFR-SCL-07 | Degradation under overload is graceful and defined, never collapse. | P1 |
| NFR-SCL-08 | Bulk admin operations do not degrade subscriber-facing performance. | P1 |

**Acceptance criteria**

`AC-NFR-SCL-03-1`
GIVEN the origin's address
WHEN it is requested from an external network position without CDN credentials
THEN the request is refused
AND no media is served.

`AC-NFR-SCL-06-1`
GIVEN a simulated live-event start with the specified concurrent authorization surge
WHEN the surge is applied
THEN authorization latency remains within NFR-PER-01/02
AND no authorization is incorrectly granted or denied as a result of load.

---

## B5. Observability (NFR-OBS)

| ID | Requirement | Priority |
|---|---|---|
| NFR-OBS-01 | Structured JSON logging with correlation ID, actor, module, and severity. | **P0** |
| NFR-OBS-02 | Every request carries a correlation ID that flows into logs and traces. | **P0** |
| NFR-OBS-03 | Health and readiness endpoints distinguish "alive" from "able to serve traffic". | **P0** |
| NFR-OBS-04 | Playback authorization decisions are recorded by outcome and reason. | **P0** |
| NFR-OBS-05 | Domain metrics exist: concurrent streams, authorization outcomes, denial reasons, transcode queue depth, EPG freshness, rights expiring within 24 hours. | **P0** |
| NFR-OBS-06 | Every alert has an owner and a runbook. | **P0** |
| NFR-OBS-07 | Clients report QoE: startup time, rebuffer ratio, bitrate distribution, failure reasons. | **P0** |
| NFR-OBS-08 | Distributed tracing across API, workers, and external calls. | P1 |
| NFR-OBS-09 | Dashboards exist for health, playback success, denial reasons, ingest status, and payment outcomes. | P1 |

---

## B6. Availability (NFR-AVL)

| ID | Requirement | Priority |
|---|---|---|
| NFR-AVL-01 | Availability target defined numerically before production. **[OPEN — PD-089]** | **P0** |
| NFR-AVL-02 | RPO and RTO defined numerically before production. **[OPEN — PD-090]** | **P0** |
| NFR-AVL-03 | EPG unavailability never prevents playback. | **P0** |
| NFR-AVL-04 | Defined degraded modes exist for CDN, origin, EPG, payment, and notification failures. | P1 |

---

# PART C — EDGE CASES

Each edge case names the required behaviour. All are mandatory test scenarios for the
phase that implements the affected domain.

| ID | Edge case | Required behaviour |
|---|---|---|
| **EC-01** | **Expired subscription** during browse | Browse continues; playback denied with `SUBSCRIPTION_EXPIRED`; renewal path offered directly from the error |
| **EC-02** | **Expired subscription** mid-playback | Current session behaviour per PD-051 grace policy; next authorization denied; viewer informed with a renewal path, not a bare failure |
| **EC-03** | **Simultaneous login** from two devices | Both permitted if within device and concurrency limits; each gets an independent session; neither invalidates the other |
| **EC-04** | **Simultaneous login** exceeding concurrency | The excess request is denied with `CONCURRENCY_LIMIT_REACHED`; existing sessions are unaffected |
| **EC-05** | **Revoked device** with playback in progress | Playback stops within the revocation interval; subsequent requests denied with `DEVICE_REVOKED`; viewer prompted to re-authenticate |
| **EC-06** | **Network interruption** during playback | Client retries with backoff; session and concurrency slot preserved for the heartbeat grace period; playback resumes without re-authorization if within the window |
| **EC-07** | **Network interruption** exceeding grace | Session expires; slot released; on reconnect the client re-authorizes silently once |
| **EC-08** | **EPG feed failure** | Last good data is served; no partial overwrite; operations alerted; playback unaffected |
| **EC-09** | **Duplicate EPG programme** in a feed | Deduplicated by the documented rule; the duplicate is not stored twice; the conflict is flagged for operator review |
| **EC-10** | **Stale EPG** beyond the freshness threshold | Existing data still served; staleness surfaced to operations; user-visible indication where confusion is likely; playback unaffected |
| **EC-11** | **Channel unavailable** (source down) | `source_error` state; retry offered; operations alerted; **the viewer is not told their subscription is invalid** |
| **EC-12** | **Rights expired** while browsing | Content disappears from browse at cache invalidation; direct playback denied with `RIGHTS_EXPIRED`; neutral message |
| **EC-13** | **Rights expired** mid-playback | Behaviour per PD-056 (terminate vs. allow completion) — **undecided, must not be defaulted silently** |
| **EC-14** | **Rights revoked** while content is cached at CDN | Cache purge is part of revocation; delivery refused after purge; revocation not considered complete until purge confirms |
| **EC-15** | **Payment callback duplicated** | Idempotent — no double charge, no double extension, no duplicate record; acknowledged |
| **EC-16** | **Payment callback delayed** and out of order | Later state is not overwritten by an earlier callback; the callback is acknowledged and logged |
| **EC-17** | **Payment callback never arrives** | Reconciliation detects the discrepancy against provider records; subscription state corrected; operations alerted |
| **EC-18** | **Playback token expired** mid-stream | One silent re-authorization attempt; on success playback continues seamlessly; on failure a recoverable error |
| **EC-19** | **Concurrent playback limit exceeded** by race | Server-side atomic enforcement; exactly the limit is granted; no race yields limit+1 |
| **EC-20** | **User deleted during active session** | Sessions invalidated; playback stops within the revocation interval; subsequent requests denied; deletion proceeds |
| **EC-21** | **Package changed during playback** | Entitlement re-evaluated at next authorization; upgrade takes effect immediately; downgrade per PD-048 timing; the current session is not silently broken mid-programme without explanation |
| **EC-22** | **Profile deleted during active session** on that profile | Session falls back to profile selection; playback stops cleanly; no orphaned resume writes |
| **EC-23** | **Clock skew** between client and server | Server time is authoritative for all window, expiry, and rights evaluation; client time is never trusted |
| **EC-24** | **DST transition** during a live programme | Programme boundaries remain correct in UTC; displayed local times shift correctly; catch-up boundaries unaffected |
| **EC-25** | **EPG correction** after a catch-up recording exists | Boundaries recomputed; existing recording remapped or marked; playback does not fail because of the correction |
| **EC-26** | **Two devices resume the same title** simultaneously | Defined conflict resolution (last-write-wins or furthest-position); never silent data loss without a rule |
| **EC-27** | **Device removed then immediately re-added** | Behaviour per PD-039 cooling-off decision — undecided, not defaulted |
| **EC-28** | **Rapid channel zapping** | Authorization requests debounced; the backend is not flooded; only committed changes are authorized |
| **EC-29** | **Account suspended mid-playback** | Playback stops at the next authorization; viewer directed to support with a clear, non-technical message |
| **EC-30** | **Maturity limit lowered while a title is playing** on that profile | Next authorization denied; current session handled per the same policy as EC-13 |

---

# PART D — ACCEPTANCE CRITERIA FOR REMAINING P0 REQUIREMENTS

The 91 P0 requirements not given criteria in Parts A and B. Equally binding; stated
compactly because each is directly observable and needs no design discussion.

## D1. Users, account, profiles

`AC-FR-USR-01-1` GIVEN a viewer identity and an operator identity WHEN each is inspected THEN they are distinct records, and no single record carries both a viewing entitlement and an administrative role.

`AC-FR-ACC-01-1` GIVEN a visitor submitting a valid identifier and password WHEN registration completes THEN an account exists in `pending_verification` AND no entitlement is granted by registration alone.

`AC-FR-ACC-03-1` GIVEN valid credentials and any required second factor WHEN login completes THEN a session is issued that is bound to a registered device record.

`AC-FR-ACC-07-1` GIVEN an account whose identifier is unverified WHEN a privileged account operation is attempted THEN it is refused until verification completes, per PD-028.

`AC-FR-PRF-01-1` GIVEN an account with multiple profiles WHEN each profile is loaded THEN each carries its own name, language, watch history, favorites, and resume points, with no shared state between them.

## D2. Devices

`AC-FR-DEV-01-1` GIVEN first authenticated use on a device WHEN the session is established THEN a device record exists recording class, identifier, display name, platform, version, and first-seen timestamp.

`AC-FR-DEV-03-1` GIVEN a playback authorization request WHEN device class is evaluated THEN the value used is read from the server-side device record, never from the request payload.

`AC-FR-DEV-05-1` GIVEN an account with registered devices WHEN the user opens device management THEN every registered device is listed with name, class, and last-active time, and each can be removed.

## D3. Live TV, channels, EPG

`AC-FR-LIV-02-1` GIVEN operator-defined channel groups and categories WHEN the channel list is requested THEN channels are returned within their assigned groups and categories in the operator-defined order.

`AC-FR-LIV-03-1` GIVEN an entitled channel WHEN the viewer selects it THEN playback authorization is requested and, on success, playback begins.

`AC-FR-LIV-04-1` GIVEN playback of a channel WHEN the viewer issues channel-up or channel-down THEN playback moves to the adjacent entitled channel in the current sort order.

`AC-FR-LIV-05-1` GIVEN playback in progress WHEN the viewer requests programme information THEN current and next programme are shown without interrupting playback.

`AC-FR-CHN-01-1` GIVEN any channel record WHEN it is inspected THEN every attribute marked required in `PRODUCT_SPEC.md` §9 is present and populated.

`AC-FR-CHN-04-1` GIVEN any channel WHEN its playback policy is inspected THEN restart, catch-up, and recording permissions are each explicitly stated rather than inferred or defaulted.

`AC-FR-EPG-01-1` GIVEN a channel with EPG data WHEN now/next is requested THEN the currently broadcasting programme and the immediately following programme are returned with their UTC time ranges.

`AC-FR-EPG-02-1` GIVEN entitled channels and a time range WHEN the schedule grid is requested THEN programmes are returned for each channel across that range, scrollable on both axes.

## D4. Player and authorization

`AC-FR-PLY-01-1` GIVEN playback on any supported platform WHEN the player is active THEN adaptive bitrate playback, volume, mute, and fullscreen are all available.

`AC-FR-PLY-02-1` GIVEN playback WHEN each defined state is entered THEN the player reports that state and only transitions permitted by `PRODUCT_SPEC.md` §11.2 occur.

`AC-FR-PLY-07-1` GIVEN playback that enters `buffering` WHEN buffering persists within the tolerance window THEN the playback session and its concurrency slot are retained and no error is shown.

`AC-FR-PLY-08-1` GIVEN a completed playback session WHEN its telemetry is inspected THEN startup time, rebuffer ratio, bitrate distribution, and any failure reason were reported.

`AC-FR-AUT-04-1` GIVEN any denial WHEN the response is inspected THEN it carries a reason code drawn from the published stable set AND the client's behaviour is driven by that code alone, never by the message text.

`AC-FR-AUT-05-1` GIVEN an issued playback session WHEN it is inspected THEN it has a bounded lifetime, is bound to exactly one viewer, one device, and one asset or session, and can be revoked server-side.

`AC-FR-AUT-07-1` GIVEN a rights agreement requiring territorial enforcement WHEN authorization is evaluated THEN territory is determined server-side AND no client-supplied location value influences the outcome.

## D5. Packages, subscriptions, rights

`AC-FR-PKG-01-1` GIVEN a package WHEN it is inspected THEN it states its content set and its feature allowances (quality ceiling, concurrency, device allowance) explicitly.

`AC-FR-PKG-04-1` GIVEN a playback authorization request WHEN package contents are evaluated THEN the evaluation occurs server-side in the entitlement engine AND no client-supplied package claim is consulted.

`AC-FR-SUB-01-1` GIVEN any subscription WHEN its state is inspected THEN it holds exactly one of `pending`, `active`, `past_due`, `paused`, `cancelled`, `expired`.

`AC-FR-SUB-04-1` GIVEN a subscription that has changed state several times WHEN its history is inspected THEN every prior state remains recorded AND no historical record was modified or removed.

`AC-FR-SUB-06-1` GIVEN an active subscription WHEN the viewer cancels THEN the state becomes `cancelled`, entitlement continues unchanged until period end, and the exact end date is communicated.

`AC-FR-RGT-01-1` GIVEN any asset marked distributable WHEN its rights metadata is inspected THEN rights holder, contract reference, territories, window, device classes, and distribution modes are all present.

`AC-FR-RGT-02-1` GIVEN any asset in the catalogue WHEN the compliance question is asked THEN the system returns rights holder, contract reference, permitted territories, window, permitted device classes, and permitted distribution modes without human research.

`AC-FR-RGT-04-1` GIVEN an asset whose rights window starts at time T WHEN time T passes THEN the asset becomes available automatically AND no manual publish action was required.

`AC-FR-RGT-08-1` GIVEN cached entitlement for an account WHEN any rights record affecting that account's accessible content changes THEN the cache is invalidated at change time, not at cache expiry.

## D6. VOD, notifications, analytics, errors, accessibility, i18n

`AC-FR-VOD-01-1` GIVEN the catalogue WHEN content types are inspected THEN movie, series, season, episode, and documentary are each representable with their defined relationships.

`AC-FR-VOD-02-1` GIVEN a profile with partial entitlement WHEN the catalogue is browsed THEN unentitled titles are absent from the response payload, not merely hidden by the client.

`AC-FR-VOD-03-1` GIVEN any published VOD title WHEN its detail is requested THEN synopsis, duration, rating, artwork, and availability are returned.

`AC-FR-NOT-03-1` GIVEN every notification template WHEN each supported language is rendered THEN the template renders completely with no missing keys and no truncation.

`AC-FR-ANL-03-1` GIVEN any analytics event type WHEN the event taxonomy is inspected THEN a documented purpose exists for it AND events without a documented purpose are not collected.

`AC-FR-ERR-01-1` GIVEN any defined error state WHEN it occurs THEN a user message, a recovery action, a log entry, and an analytics event are all produced.

`AC-FR-ERR-04-1` GIVEN any error surfaced to a viewer WHEN it is displayed THEN it includes a short stable code that remains constant across releases for the same condition.

`AC-FR-A11Y-03-1` GIVEN content with supplied subtitles WHEN it is played on any supported platform THEN subtitles can be enabled and are rendered.

`AC-FR-A11Y-04-1` GIVEN the web client WHEN every user journey is attempted using keyboard input only THEN every journey completes.

`AC-FR-I18N-02-1` GIVEN each of Georgian, English, Russian, and Spanish WHEN the interface is rendered in that language THEN all user-visible strings resolve, with no untranslated keys displayed.

`AC-FR-I18N-04-1` GIVEN any displayed time WHEN it is rendered THEN it was computed in UTC and converted to the viewer's timezone using locale-appropriate formatting.

`AC-FR-I18N-05-1` GIVEN any displayed monetary amount WHEN it is rendered THEN its currency is displayed alongside it.

`AC-FR-HIS-01-1` GIVEN playback by a profile WHEN watch history is inspected THEN the item is recorded against that profile alone.

## D7. Admin and payments

`AC-FR-ADM-02-1` GIVEN any mutating admin action WHEN its audit record is inspected THEN actor, timestamp, action, and both before and after state are present.

`AC-FR-ADM-06-1` GIVEN any administrative account WHEN authentication is attempted without a second factor THEN access is refused.

`AC-FR-ADM-07-1` GIVEN a Content Manager WHEN a channel is created, edited, ordered, or published THEN the operation succeeds only with a rights reference present and is audited.

`AC-FR-ADM-08-1` GIVEN an authorized operator WHEN a rights agreement is created and attached to assets THEN the attachment takes effect for authorization decisions and is audited with its contract reference.

`AC-FR-ADM-11-1` GIVEN an authorized operator WHEN a subscriber's devices and sessions are viewed THEN all are listed, each can be removed or terminated, and the effect on playback occurs within the revocation interval.

`AC-FR-ADM-12-1` GIVEN an EPG ingest run WHEN it completes or fails THEN the operator can view its outcome, counts, conflicts, unmatched channels, and warnings.

`AC-FR-PAY-02-1` GIVEN the full estate of KMS TV databases, logs, caches, and request payloads WHEN scanned for PAN, CVV, or track data patterns THEN no match is found.

`AC-FR-PAY-06-1` GIVEN the domain layer WHEN it is inspected for payment provider types THEN no vendor SDK type appears anywhere inside the abstraction boundary, and the check is enforced in CI.

## D8. Security

`AC-NFR-SEC-02-1` GIVEN any boundary input WHEN it is received THEN type, range, length, format, and authorization context are validated before use.

`AC-NFR-SEC-03-1` GIVEN the codebase WHEN scanned for string-concatenated SQL THEN no occurrence exists, and the scan is a blocking CI step.

`AC-NFR-SEC-04-1` GIVEN any stored password WHEN its hash is inspected THEN it was produced by a memory-hard algorithm, never MD5, SHA-1, or an unsalted digest.

`AC-NFR-SEC-05-1` GIVEN the repository and all build artifacts WHEN scanned for secrets THEN none is found AND rotating any secret requires no code change.

`AC-NFR-SEC-06-1` GIVEN any network path including internal service-to-service traffic WHEN inspected THEN it is TLS-protected and no credential traverses a plaintext connection.

`AC-NFR-SEC-07-1` GIVEN each of the authentication, registration, password-reset, device-registration, playback-authorization, and payment endpoints WHEN requests exceed the configured rate THEN further requests are throttled.

`AC-NFR-SEC-08-1` GIVEN any privileged action WHEN its audit entry is inspected THEN actor, action, timestamp, source, and before/after state are recorded.

`AC-NFR-SEC-09-1` GIVEN any web response WHEN its headers are inspected THEN HSTS, CSP, `X-Content-Type-Options`, `Referrer-Policy`, and frame protections are present.

`AC-NFR-SEC-12-1` GIVEN the production runtime database user WHEN its grants are inspected THEN it holds no DDL rights and does not own the schema.

`AC-NFR-SEC-13-1` GIVEN a dependency or container image with a known critical or high vulnerability WHEN CI runs THEN the build fails.

`AC-NFR-SEC-14-1` GIVEN CSRF, session-fixation, and replay attack attempts WHEN each is executed against the platform THEN each is rejected, and each has a test that fails if the protection is removed.

## D9. Privacy

`AC-NFR-PRV-01-1` GIVEN every personal-data field WHEN the data inventory is inspected THEN each has a documented purpose AND no field exists without one.

`AC-NFR-PRV-02-1` GIVEN a data category past its retention period WHEN the retention job runs THEN the data is removed or anonymized without manual intervention.

`AC-NFR-PRV-04-1` GIVEN watch history WHEN its classification is inspected THEN it is marked sensitive AND access control, analytics handling, and retention follow the sensitive-data rules.

`AC-NFR-PRV-05-1` GIVEN two profiles on one account WHEN either requests history, continue-watching, or recommendations THEN no item originating from the other profile appears.

`AC-NFR-PRV-07-1` GIVEN a child profile WHEN its defaults are inspected THEN maturity ceiling, data collection, and personalization are each set more restrictively than a standard profile.

`AC-NFR-PRV-08-1` GIVEN any third-party SDK proposed for a client WHEN it is reviewed THEN its data collection is documented and disclosed before inclusion, and inclusion without that record fails the gate.

## D10. Performance, scalability, observability, availability

`AC-NFR-PER-02-1` GIVEN production-representative load WHEN playback authorization latency is measured THEN p99 ≤ 600 ms.

`AC-NFR-PER-03-1` GIVEN production-representative load WHEN catalogue read latency is measured THEN p95 ≤ 300 ms.

`AC-NFR-PER-04-1` GIVEN the minimum supported device on a broadband connection WHEN playback is started for live and for VOD THEN time to first frame ≤ 2.0 s in both cases.

`AC-NFR-PER-05-1` GIVEN the minimum supported TV device WHEN a channel change is committed THEN the new channel renders within 2.0 s.

`AC-NFR-PER-06-1` GIVEN aggregate client telemetry over a measurement period WHEN rebuffering is computed THEN it is ≤ 0.5% of total watch time.

`AC-NFR-PER-07-1` GIVEN a realistic channel count on the minimum supported device WHEN the EPG grid is first loaded THEN it renders within 1.5 s.

`AC-NFR-PER-08-1` GIVEN the minimum supported TV device from a cold start WHEN the application launches THEN it is interactive within 5.0 s.

`AC-NFR-SCL-01-1` GIVEN a running application tier WHEN an instance is added or removed under load THEN no session, upload, or cache-of-record is lost and traffic is served throughout.

`AC-NFR-SCL-02-1` GIVEN media delivery in production WHEN request paths are inspected THEN no media segment traversed the application tier.

`AC-NFR-SCL-04-1` GIVEN queued work WHEN a job fails repeatedly THEN it moves to a dead-letter queue within the bounded retry policy AND queue depth is visible as a metric.

`AC-NFR-SCL-05-1` GIVEN each external dependency WHEN it is made slow and then unavailable in test THEN the defined degraded behaviour occurs within its timeout, and the test exists for each dependency.

`AC-NFR-OBS-01-1` GIVEN any log line WHEN it is inspected THEN it is structured JSON carrying correlation ID, actor, module, and severity.

`AC-NFR-OBS-02-1` GIVEN a single request traversing API and queue workers WHEN its logs and traces are collected by correlation ID THEN the full path is reconstructable end to end.

`AC-NFR-OBS-03-1` GIVEN an instance whose dependencies are unreachable WHEN health and readiness are polled THEN health reports alive and readiness reports not ready, and traffic is withheld.

`AC-NFR-OBS-04-1` GIVEN a period of playback authorization activity WHEN metrics are inspected THEN decisions are counted by outcome and by reason code.

`AC-NFR-OBS-05-1` GIVEN the metrics catalogue WHEN inspected THEN concurrent streams, authorization outcomes, entitlement denials by reason, transcode queue depth, EPG ingest freshness, and rights expiring within 24 hours are all collected.

`AC-NFR-OBS-06-1` GIVEN every configured alert WHEN the alert catalogue is reviewed THEN each has a named owner and a linked runbook, and an alert with neither fails the gate.

`AC-NFR-OBS-07-1` GIVEN a client playback session on any platform WHEN telemetry is received THEN startup time, rebuffer ratio, bitrate distribution, and failure reason are present.

`AC-NFR-AVL-01-1` GIVEN the production readiness review WHEN availability is inspected THEN a numeric availability target is recorded (PD-089) and measured against.

`AC-NFR-AVL-02-1` GIVEN the production readiness review WHEN recovery objectives are inspected THEN numeric RPO and RTO are recorded (PD-090) and have been met in a timed drill.

`AC-NFR-AVL-03-1` GIVEN the EPG service is fully unavailable WHEN a viewer selects an entitled channel THEN playback authorization succeeds and playback starts.

---

## Appendix — Requirements traceability to phases

| Domain | Specified | Designed | Implemented | Verified |
|---|---|---|---|---|
| USR, ACC | Phase 1 | Phase 3, 6 | Phase 8 | Phase 8, 33 |
| PRF, DEV | Phase 1 | Phase 3, 6 | Phase 9 | Phase 9, 33 |
| CHN, HOME | Phase 1 | Phase 3, 4 | Phase 10 | Phase 10 |
| EPG | Phase 1 | Phase 3, 4 | Phase 11 | Phase 11 |
| PKG, SUB | Phase 1 | Phase 4 | Phase 12 | Phase 12 |
| **RGT** | Phase 1 | Phase 4 | **Phase 13** | Phase 13, 33, 37 |
| Entitlement | Phase 1 | Phase 4, 6 | Phase 14 | Phase 14, 33 |
| **AUT** | Phase 1 | Phase 5, 6 | **Phase 15** | Phase 15, 32, 33 |
| PLY, LIV | Phase 1 | Phase 3 | Phase 16, 19–24 | Phase 19–24, 32 |
| VOD | Phase 1 | Phase 4 | Phase 25 | Phase 25 |
| CUP, RST | Phase 1 | Phase 4 | Phase 26 | Phase 26 |
| PAY | Phase 1 | Phase 3 | Phase 27 | Phase 27, 33 |
| NOT | Phase 1 | Phase 3 | Phase 28 | Phase 28 |
| ANL | Phase 1 | Phase 3 | Phase 29 | Phase 29 |
| ADM | Phase 1 | Phase 3, 6 | Phase 18 | Phase 18, 33 |
| SCH, FAV, HIS, REC | Phase 1 | Phase 3, 4 | Phase 10, 25 | Phase 25 |
| I18N, A11Y, ERR | Phase 1 | Phase 3 | Phase 19–24 | Phase 19–24 |
| NFR-SEC, NFR-PRV | Phase 1 | Phase 6 | All | Phase 33 |
| NFR-PER, NFR-SCL | Phase 1 | Phase 3 | All | Phase 32 |
| NFR-OBS | Phase 1 | Phase 3 | Phase 7, 31 | Phase 31 |
| NFR-AVL | Phase 1 | Phase 3 | Phase 34 | Phase 34 |
