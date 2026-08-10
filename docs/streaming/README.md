# Streaming Architecture

## Contents

| Document | Covers |
|---|---|
| This document | The media plane end to end, and the decisions that shape it |
| [`ingest-and-transcoding.md`](ingest-and-transcoding.md) | Contribution, redundancy, encoding ladders |
| [`packaging-and-delivery.md`](packaging-and-delivery.md) | CMAF, manifests, origin, CDN, cost |
| [`catchup-restart-npvr.md`](catchup-restart-npvr.md) | Time-shift buffer, restart, catch-up, storage |
| [`drm.md`](drm.md) | Multi-DRM, encryption schemes, licence policy |
| [`player-and-device-matrix.md`](player-and-device-matrix.md) | Per-platform players and the verification matrix |

Decision records:
[ADR-0005 CMAF multi-DRM](../architecture/adr/ADR-0005-cmaf-multi-drm.md) ·
[ADR-0008 CDN and origin abstraction](../architecture/adr/ADR-0008-cdn-and-origin-abstraction.md)

---

## ⚠ Verification status

Vendor documentation is **unreachable** from the environment in which these documents were written
([`../architecture/00-inspection-report.md`](../architecture/00-inspection-report.md), E6). Statements
about how a specific DRM system, device, packager or CDN behaves are marked **`[UNVERIFIED]`** and
must be confirmed by a human against current vendor documentation and real hardware before anything
is built on them.

What follows is a design, expressed in terms of what KMS TV needs, with verification points marked.
It is not a report of vendor capabilities.

## 1. The pipeline

```
CONTRIBUTION            PROCESSING              PACKAGING           DELIVERY         PLAYBACK
──────────────          ──────────              ─────────           ────────         ────────
playout / IRD  ──┐
satellite      ──┼──►  transcode          ──►  CMAF fMP4      ──►  origin      ──►  CDN  ──►  player
contribution   ──┘     · ABR ladder            · encrypt (cbcs)     · shield         edge
                       · audio/subtitles       · HLS manifest       · time-shift
mezzanine file ──►     · normalisation         · DASH manifest        buffer
(VOD)                  · QC                    (same segments)      · VOD store

                                        ▲                              ▲              ▲
                              content key from vault         delivery token    licence token
                              (D3 Content Protection)        (D4 Delivery)     (D3 → licence proxy)
```

The two dashed inputs at the bottom are the point of
[ADR-0006](../architecture/adr/ADR-0006-playback-authorization-tokens.md): the media plane carries
bytes, and it is the decision plane that says who may have them. **The media plane makes no
authorization decisions.** An origin or CDN that decides who may watch is an origin or CDN that
cannot be replaced.

## 2. Decisions that shape everything downstream

| Decision | Position | Consequence |
|---|---|---|
| **CMAF, single encode** | ADR-0005 | One segment set serves HLS and DASH. Cuts encoding, storage and — most importantly — CDN cache fragmentation |
| **`cbcs` encryption, single-encode multi-DRM** | ADR-0005, **proposed** | One encrypted copy for all three DRM systems, **if every target device supports it** `[UNVERIFIED]` |
| **Origin is ours; CDN is a cache** | ADR-0008 | Deleting the CDN loses performance, never content |
| **Delivery targets are a list** | ADR-0008 | Multi-CDN later without a client update |
| **Rights drive protection policy** | [`../architecture/06-rights-management.md`](../architecture/06-rights-management.md) | Resolution caps and HDCP come from contracts, not from code |
| **Recording rights gate the buffer** | [`catchup-restart-npvr.md`](catchup-restart-npvr.md) | Content without recording rights is never written to disk |

## 3. Cost model

Delivery is usually the largest running cost of an OTT platform, and its drivers are structural
rather than incidental:

```
CDN egress        ≈  concurrent viewers × average bitrate × hours
Origin egress     ≈  CDN egress × (1 − cache offload)
Storage           ≈  VOD library × ladder multiplier
                   + channels × nDVR retention hours × bitrate   ← usually the largest single line
Transcoding       ≈  channels × 24h  +  VOD hours × ladder rungs
```

Three implications that must be designed in rather than optimised later:

1. **Cache offload is the biggest lever on origin cost.** A single segment set (CMAF) rather than
   several per-format copies is what makes high offload achievable — which is why ADR-0005 is an
   economic decision as much as a technical one.
2. **The encoding ladder is a direct multiplier on both bandwidth and storage.** Adding a rung, or
   raising a bitrate, costs money every hour of every day thereafter.
3. **nDVR retention is the storage decision with the largest financial consequence.** Retaining 7
   days across 50 channels is a very different business than retaining 30. It is a **product and
   commercial decision** (**OQ-27**), not an engineering default.

The model is instantiated with real numbers once **OQ-1** (markets), **OQ-2** (audience scale) and
**OQ-6** (channel count) are answered. Until then, the shape is what matters: these are the terms
that will dominate the bill.

## 4. What must be verified before building

Consolidated from the checklists in
[`../architecture/05-integration-boundaries.md`](../architecture/05-integration-boundaries.md):

- [ ] **DRM agreements** — Widevine, PlayReady, FairPlay. Separate agreements, separate timelines.
      **The longest lead item in the programme; start immediately**
- [ ] `cbcs` support across every target device, especially the **oldest** one in the matrix
- [ ] Packager selection, with licence cleared
      ([`../architecture/09-dependency-policy.md`](../architecture/09-dependency-policy.md) — note
      Bento4's dual GPL/commercial licensing)
- [ ] CDN token scheme, purge semantics, log delivery, live support
- [ ] Contribution format and handover from the content source (**OQ-4**)
- [ ] Device matrix: platforms and model years (**OQ-3**)
- [ ] Whether SSAI/advertising is in scope (**OQ-11**) — **answer before Phase 4**; retrofitting it
      into packaging and manifests is expensive
- [ ] Whether offline download is in scope (**OQ-12**) — changes DRM licence requirements
- [ ] Whether 4K/HDR is in scope (**OQ-14**) — changes ladder, DRM security level, and cost model
