# Ingest and Transcoding

> Vendor and hardware behaviour is **`[UNVERIFIED]`** here — see
> [`README.md`](README.md#-verification-status). Contribution format and handover are **OQ-4**.

## 1. Live contribution

The handover from the content source is the least controllable part of the platform and the most
common source of live incidents. It is also unknown for KMS TV (**OQ-4**): whether channels arrive
from a playout facility, satellite receivers, an existing headend, or a partner's contribution feed
changes this design substantially.

What must be true regardless of the answer:

| Requirement | Why |
|---|---|
| **Redundant contribution paths** | A single feed means a single point of failure for a channel, and channel outages are the most visible failure a viewer experiences |
| **Automatic failover between paths, with hysteresis** | Flapping between A and B is worse than staying on a degraded A |
| **Signal monitoring at ingest** | Black frames, silence, freeze, and loss must alert **before** viewers report them |
| **Timing reference discipline** | Drift between paths and between channels causes artefacts that are hard to diagnose after the fact |
| **Buffering at ingest** | Absorbs brief upstream interruptions before they reach viewers |
| **A defined slate behaviour** | When a feed is genuinely lost, viewers see a deliberate slate, not a broken player |

The last point matters more than it looks. "The stream stopped" and "the channel is showing an
apology slate" produce very different support volumes and very different perceptions of reliability.

### Redundancy model

```
        ┌─ path A ──┐
source ─┤           ├─► ingest (active/standby, automatic failover) ─► transcode
        └─ path B ──┘
```

Both paths are monitored continuously, not only on failover, so a silently dead standby is detected
before it is needed. A standby that has never been tested is not a standby.

## 2. VOD ingest

Mezzanine files arrive by watch folder, API upload, or partner delivery (**OQ-4**).

Every ingest, without exception:

1. **Checksum on arrival**, recorded. Storage and transfer corrupt files, and a corrupted mezzanine
   discovered after encoding wastes the whole pipeline run.
2. **Technical QC before encoding** — container, codec, resolution, frame rate, audio configuration,
   loudness, duration against the expected value.
3. **Rejected with a specific reason** if QC fails. A file that silently produces a broken encode is
   worse than one that is refused with a clear message.
4. **Recorded in `media.mezzanines`** with its storage location and checksum, so it can be
   re-processed after a pipeline bug is fixed — which will be needed.
5. **Retained** while re-encoding remains plausible, then moved to cold storage
   ([`../database/scaling-and-retention.md`](../database/scaling-and-retention.md)).

Never delete the mezzanine because the encode succeeded. Ladders change, codecs change, bugs are
found.

## 3. Transcoding

### Ladder design

The ABR ladder is a **cost decision as much as a quality decision**: every rung multiplies encoding
time and storage, and the top rung largely determines bandwidth cost.

Principles:

- **The ladder is data, not code**, defined per content class (live news, live sport, film, series)
  so it can be tuned without a deployment.
- **Rungs are chosen from measured device and network reality**, not copied from a reference table.
  The lowest rung must be genuinely usable on the worst connection in the target markets; the top
  rung is bounded by what the target devices can actually decode (**OQ-3**).
- **Sport needs a different ladder from film** — high motion needs more bits at the same perceived
  quality, so a single ladder across all content either wastes money or looks bad.
- **Adding a rung requires justification**: measured benefit against permanent cost.
- 4K/HDR (**OQ-14**) changes the ladder, the storage model, the DRM security requirements, and the
  device matrix simultaneously. It is not a later toggle.

### Codecs

Codec selection is a genuine trade-off and must be **measured, not assumed**:

- Wider device compatibility versus better compression efficiency.
- Newer codecs reduce bandwidth cost but narrow the compatible device set and may require separate
  encodes — reintroducing the multiplication that CMAF was chosen to avoid.
- **Patent licensing obligations differ substantially between codecs** and must be reviewed by
  counsel before adoption, not after.

Decision deferred to Phase 4/5 with the device matrix (**OQ-3**) and recorded as an ADR at that time.

### Implementation

- **FFmpeg-based**, with the **exact build flags recorded** — the build configuration determines
  FFmpeg's licence ([`../architecture/09-dependency-policy.md`](../architecture/09-dependency-policy.md)).
- Pinned container image; the FFmpeg version is part of the artifact, never "whatever is installed".
  A silent FFmpeg upgrade changing output characteristics is a genuinely difficult bug to trace.
- **Live transcoding** runs continuously per channel, sized for peak, with health monitoring and
  automatic restart. Restart must not desynchronise output.
- **VOD transcoding** is queued work: parallelisable, resumable, idempotent, priority-aware (a title
  publishing tomorrow outranks a back-catalogue re-encode).
- **CPU versus GPU** is decided by measurement in Phase 5 — the trade-off between density, quality at
  a given bitrate, and cost is workload-specific and changes with hardware generation.

## 4. Audio and subtitles

Routinely underestimated, and a frequent cause of late rework:

- **Multiple audio languages** per asset, each with a role (main, description, commentary).
- **Loudness normalisation to a consistent target.** Volume jumps between programmes and between
  channels are among the most common viewer complaints, and they are trivially preventable at ingest.
- **Subtitles and closed captions are distinct** — captions include non-speech information and are
  an accessibility requirement, sometimes a legal one
  ([`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md) §5).
- Forced-narrative subtitles (for foreign-language passages) are separate from full subtitle tracks
  and must be modelled as such.
- **Subtitle and audio-track rights can differ from the video's rights** — a dub or a subtitle track
  may be licensed separately, for different territories. `media.tracks` and `rights` must both be
  able to express that.
- Live captioning, if required, is an additional real-time pipeline with its own vendor and latency
  characteristics (**OQ-28**).

## 5. Quality control

| Stage | Check | On failure |
|---|---|---|
| Ingest | Checksum, container, codec, duration | Reject with a specific reason |
| Post-encode | All rungs produced; durations match; no frozen or black rungs | Fail the job, alert, retain the mezzanine |
| Post-package | Manifests valid; segments present and aligned; encryption applied | Fail before publication |
| Pre-publication | Playback verified on a reference player | Block publication |
| Live, continuous | Frame health, audio presence, bitrate, segment continuity | Alert; fail over if the path is at fault |

**Automated QC gates publication.** A human eyeballing a title before it goes live does not scale
past a few titles a week, and it is precisely at scale that a bad encode reaches viewers.

## 6. Observability

- Per-channel: input health, transcode health, output continuity, segment publication latency.
- Per-VOD-job: state, duration, attempt count, failure reason, queue wait.
- Alerts on: input loss, encoder restart, segment publication gaps, QC failure rate above baseline,
  queue depth growth.
- **Every media artifact is traceable to its source and its processing run** — asset → job → run →
  mezzanine → checksum. Without that chain, "this episode looks wrong" is unanswerable.
