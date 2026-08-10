# ADR-0005: CMAF with `cbcs` as the single-encode multi-DRM target

**Status:** **Proposed** — depends on verification that cannot be performed in this environment
**Date:** 2026-08-10
**Deciders:** Architecture, Streaming, Security

## Context

KMS TV must reach six platform families, which between them require both HLS and DASH and all three
major DRM systems (Widevine, PlayReady, FairPlay).

The naive approach — encode and store a separate copy per delivery format and per DRM — multiplies
encoding cost, storage, and CDN cache footprint by three to six. Cache fragmentation is the worst of
these: it lowers offload and raises origin egress, the dominant cost in OTT delivery.

CMAF (Common Media Application Format) exists to make one set of fragmented-MP4 segments serve both
HLS and DASH. Common Encryption (CENC) defines two protection schemes: `cenc` (AES-CTR) and `cbcs`
(AES-CBC with pattern encryption). Which schemes a given DRM system and a given device support is
the crux of this decision.

**Constraint on this ADR:** vendor documentation is unreachable from the environment where it was
written (see [`../00-inspection-report.md`](../00-inspection-report.md), E6). The technical premise
below is therefore recorded as an **assumption to be verified**, not as fact.

## Options considered

**A. Separate encode and package per format and DRM.** Maximum compatibility, worst economics, and
n copies to keep consistent.

**B. Single CMAF encode, single encryption scheme (`cbcs`), HLS and DASH manifests over the same
segments.** Best economics and one cache footprint — *if* all target devices support it.

**C. Single CMAF encode, dual encryption (`cbcs` primary, `cenc` fallback for legacy devices).**
Two segment sets instead of six, with device-based selection at authorization time.

## Decision

**Target option B; design for option C.**

1. Encode once to a CMAF fMP4 ladder.
2. Encrypt with `cbcs` as the primary scheme.
3. Generate HLS and DASH manifests over the **same** segments.
4. Keep the packaging pipeline and the manifest/delivery layer capable of emitting a **second,
   `cenc`-encrypted** segment set, selected per device class at authorization time — without a
   redesign.
5. Device class → format + scheme + DRM system mapping is **data, not code**, so it can be corrected
   as device-lab results arrive (see
   [`../../streaming/player-and-device-matrix.md`](../../streaming/player-and-device-matrix.md)).

**This ADR cannot move to Accepted until:**
- [ ] Widevine, PlayReady and FairPlay `cbcs` support is confirmed from each vendor's current
      documentation, for the specific security levels we require
- [ ] The device matrix (OQ-3: which platforms and model years) is confirmed, and `cbcs` support is
      verified on the **oldest** device in it
- [ ] A packager that produces the required output is selected and licence-cleared
      ([`../09-dependency-policy.md`](../09-dependency-policy.md))
- [ ] An end-to-end test plays `cbcs` content on at least one real device per DRM system

## Consequences

**Accepted costs**
- Dependent on a technical premise that must hold across every target device; a single important
  legacy device that cannot do `cbcs` forces the fallback path and its extra storage and cache cost.
- The packaging pipeline must be scheme-parameterised from the start, which is slightly more work
  than hardcoding one.

**Made easier**
- One encode, one storage footprint, one cache key space for the common case.
- Adding a platform generally becomes a manifest and player question, not an encoding question.

**Revisit when**
- Device-lab results contradict the premise, or
- A new codec or delivery format (for example a low-latency or 4K/HDR requirement, OQ-14) changes the
  ladder economics.
