# Legal and Licensing Decision Log — KMS TV

Every legal, licensing, or content-rights decision is recorded here with a date and a
rationale. Entries are **append-only**: a superseded decision is marked superseded and
kept, never deleted. Traceability is the point.

Related: `CONTENT_RIGHTS_POLICY.md`, `THIRD_PARTY_LICENSES.md`, `CLAUDE.md` §2–§3, and
the *Decisions* section of `PROJECT_STATE.md`.

---

## Entry format

```
### L-NNN — <short title>
- **Date:**
- **Phase:**
- **Decision:**
- **Rationale:**
- **Alternatives considered:**
- **Legal review required:** yes / no
- **Legal review status:** not required / pending / completed on <date> by <who>
- **Recorded by:**
- **Status:** Accepted / Rejected / Superseded by L-NNN / Open
```

---

## Log

### L-001 — Rights metadata is a mandatory attribute of every distributable asset
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** No asset may be distributable without complete rights metadata: holder,
  contract reference, territories, window, device classes, distribution modes, and
  concurrency limits. Enforced by the data model (Phase 4) and the rights system
  (Phase 13), not by process alone.
- **Rationale:** Rights cannot be retrofitted onto a catalogue built without them without
  effectively rewriting the entitlement and playback paths. Modelling it from the first
  schema is the only affordable time to do it.
- **Alternatives considered:** Rights as an optional annotation added later — rejected;
  it makes unauthorized distribution the default behaviour of the system.
- **Legal review required:** no (engineering design decision)
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-002 — Distribution modes are granted and enforced independently
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** Live, catch-up, restart, VOD, and download rights are separate grants.
  Holding one never implies another. Each is enforced separately at playback
  authorization.
- **Rationale:** Assuming that live rights extend to catch-up is the most common
  inadvertent breach in time-shifted television. Phase 26 tests this explicitly and
  negatively.
- **Legal review required:** no (restates standard rights practice; specific contracts
  govern specific assets)
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-003 — Rights expiry enforced by two independent mechanisms
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** Expired rights disable content via a scheduled job **and** via a re-check
  at each playback authorization request. Both are required; each is tested with the
  other disabled.
- **Rationale:** A single mechanism can fail silently, and silent failure here means
  serving content we have no right to serve. Two independent mechanisms make silent
  failure require two simultaneous faults.
- **Legal review required:** no
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-004 — No third-party component adopted in Phase 0
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** No dependency, library, image, or asset was installed or incorporated.
  `THIRD_PARTY_LICENSES.md` is intentionally empty.
- **Rationale:** Phase 0 is inspection and governance only. The intake procedure exists
  before the first component arrives, rather than being written retroactively around
  choices already made.
- **Legal review required:** no
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-005 — FFmpeg license review precedes FFmpeg installation
- **Date:** 2026-08-13
- **Phase:** 0 (applies at Phase 16)
- **Decision:** The specific FFmpeg build's license (LGPL vs. GPL) and enabled components
  are reviewed and recorded in `ffmpeg-license-review.md` **before** installation for
  production use. Nonfree builds must not be distributed.
- **Rationale:** FFmpeg's license depends on build configuration, and the distinction
  materially affects a proprietary product. Reviewing after adoption converts a choice
  into a problem.
- **Legal review required:** yes
- **Legal review status:** pending — due before Phase 16
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-006 — Codec and patent licensing treated as a separate obligation
- **Date:** 2026-08-13
- **Phase:** 0 (applies at Phases 16 and 37)
- **Decision:** Patent licensing for H.264/AVC, H.265/HEVC, AAC and similar technologies
  is tracked separately from software licensing and must be resolved before commercial
  launch.
- **Rationale:** Software freedom does not imply patent freedom. This obligation carries
  real commercial cost and is routinely discovered at launch rather than planned for.
- **Legal review required:** yes
- **Legal review status:** pending — due before commercial launch
- **Recorded by:** Engineering agent
- **Status:** Accepted

### L-007 — Pre-existing repository scaffold: legal status unassessed
- **Date:** 2026-08-13
- **Phase:** 0
- **Decision:** The repository contains a pre-existing, non-buildable Vite/React/Supabase
  starter of external origin (see `PROJECT_STATE.md` §2.1). Its provenance and license
  have **not** been assessed. It must not be treated as a foundation, and no code may be
  copied from it, until its origin and license are established.
- **Rationale:** Code of unknown provenance is code of unknown license. `CLAUDE.md` §3
  requires the license be known before incorporation, and "it was already in the
  repository" is not a determination of provenance.
- **Legal review required:** yes, **if** any part of it is retained or reused. Not
  required if it is removed.
- **Legal review status:** pending — resolve alongside decision D-006 in Phase 1
- **Recorded by:** Engineering agent
- **Status:** Open
