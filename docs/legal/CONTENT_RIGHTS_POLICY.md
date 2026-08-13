# Content Rights Policy — KMS TV

**Status:** Established in Phase 0. Implemented as an enforceable system in Phase 13.
**Governing document:** `CLAUDE.md` §2.

---

## 1. Principle

KMS TV distributes **only** content for which the operator holds appropriate rights or
permission. The platform is designed for a licensed operator serving licensed content to
entitled subscribers. It is not designed for, and must not be adapted to, unauthorized
redistribution.

## 2. The controlling question

For every distributable asset, the system must be able to answer, at any time, without
human research:

> **Why are we allowed to serve this, to whom, where, in what form, and until when?**

An asset for which this cannot be answered is not distributable. This is enforced by the
data model (Phase 4) and by the rights system (Phase 13) — not by policy alone, because
policy alone is not enforcement.

## 3. Required rights metadata

Every distributable asset — channel, VOD title, series, season, episode, catch-up window,
restart window — carries:

| Field | Meaning |
|---|---|
| Rights holder | Who granted the right |
| Contract reference | The agreement the grant comes from |
| Territories | Where distribution is permitted |
| Window start / end | When distribution is permitted |
| Device classes | Which device categories are permitted |
| Distribution modes | Live / catch-up / restart / VOD / download |
| Concurrency limits | Any contractual simultaneous-stream cap |

**Distribution modes are independent.** Live rights do not imply catch-up rights.
Catch-up rights do not imply download rights. Each mode is granted or not granted
separately, and each is enforced separately. This is the most common source of
inadvertent breach in time-shifted television, and Phase 26 tests for it explicitly.

## 4. Enforcement

Rights are enforced at **playback authorization time** (Phase 15), server-side.

- Hiding a tile in a client is presentation. Denying a playback token is enforcement.
  Only the second one counts.
- Client-reported territory, device class, and time are never authoritative.
- Expiry is enforced by **two independent mechanisms**: a scheduled job that disables
  expired content, **and** a re-check at every authorization request. Either alone is
  insufficient, because either alone can fail silently.
- Rights changes invalidate cached entitlements immediately. A stale entitlement is a
  compliance failure, not a performance trade-off.

## 5. Content ingestion

- Every content source records its provenance: who supplied it, under which agreement,
  with which identifier.
- **A stream source cannot be created without a rights basis.** This is a validation
  rule, enforced by the system, not a checklist item (Phase 10).
- Ingesting a stream URL or playlist with no recorded rights basis is prohibited.

## 6. Prohibited feature classes

The platform must not contain features whose purpose or primary effect is to:

- bypass DRM or any content protection system
- bypass authentication or authorization
- bypass geo-restrictions or territorial licensing
- steal, harvest, or replay stream credentials
- extract, restream, or re-host protected streams from third parties
- evade content-provider controls, watermarking, or audit mechanisms
- redistribute unauthorized copyrighted content

This applies to admin tooling and internal diagnostics exactly as it applies to
subscriber features. **"Internal only" is not an exemption.**

## 7. Audit and evidence

- All rights records and changes are auditable: who changed what, when, against which
  contract.
- Playback authorization decisions are logged with enough detail to demonstrate
  compliance to a rights holder — and with no credentials or tokens.
- Compliance evidence for any sampled asset must be producible on demand (Phase 13,
  re-verified in Phase 37).

## 8. Separate from software licensing

Content rights, **software licensing** (`THIRD_PARTY_LICENSES.md`), and **codec/patent
licensing** are three distinct obligations. Satisfying one says nothing about the others.
All three must be resolved before commercial launch.
