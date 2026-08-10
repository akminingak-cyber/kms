# Players and the Device Matrix

> ## ⚠ This document contains **no** claims about what any platform supports
>
> Per-platform capability (which DRM systems, which streaming formats, which codecs, which encryption
> schemes, which model years) **cannot be verified from this environment**
> ([`../architecture/00-inspection-report.md`](../architecture/00-inspection-report.md), E6) and must
> not be assumed from memory. What follows is the **method** for establishing the matrix and the
> structure that consumes it — the matrix itself is filled in by testing on real hardware.

## 1. The matrix is data, not code

The mapping from device class to delivery configuration lives in the database, owned by
**D4 Delivery Control**, and is consumed at playback authorization:

```
device_class + client + client_version + capabilities
        │
        ▼
   ┌──────────────────────────────────────────────┐
   │ streaming format   hls | dash                │
   │ encryption scheme  cbcs | cenc               │
   │ drm system         widevine | playready |    │
   │                    fairplay                  │
   │ max resolution     (also bounded by rights)  │
   │ codec profile                                │
   │ known quirks/workarounds                     │
   └──────────────────────────────────────────────┘
```

Why data rather than code: device-lab findings arrive continuously and unpredictably, often for
devices already in the field. A firmware update on one TV model breaks one code path; the fix must be
a configuration change deployable in minutes, not a client release that reaches that model in months
— by which time the viewer has cancelled.

The mapping also carries `client_version`, so a workaround can be scoped to the exact client builds
that need it.

## 2. Establishing the matrix

For each platform in scope (**OQ-3** defines which, and which model years):

1. **Read current vendor documentation** — capabilities change between OS versions and model years.
2. **Procure real devices**, including the **oldest** model year in scope. The newest device proves
   the least; the oldest is where the platform actually fails.
3. **Test**, per device: streaming format, **container (fMP4 vs TS)**, encryption scheme, DRM system
   and security level, codec and profile, maximum resolution, HDCP behaviour, **subtitle format and
   rendering**, audio track switching, ABR behaviour on a constrained network, error handling,
   **TLS version and cipher support**, and **whether the device trusts our certificate chain's root**.
4. **Record results** with device model, firmware/OS version, and test date. A result without a
   firmware version is not reproducible.
5. **Re-test on platform updates.** A TV firmware update can change playback behaviour without any
   change on our side, and the first signal is usually a support spike.

Emulators are useful for UI and navigation work and are **not sufficient** for DRM or playback
verification: they do not exercise the device's security implementation, which is the part that fails.

## 3. Player per platform

Direction from the brief, with the shared logic that keeps them consistent:

| Platform | Player | Shared package |
|---|---|---|
| Web | Browser media stack via a JS player (candidates verified in [`../architecture/09-dependency-policy.md`](../architecture/09-dependency-policy.md): `shaka-player`, `hls.js`, `dashjs`) | `packages/ts-player-core` |
| Android / Android TV | AndroidX **Media3** | `packages/kotlin-player-core` |
| iOS / iPadOS | **AVFoundation** | `packages/swift-player-core` |
| Samsung Tizen | Platform player | `packages/ts-player-core` |
| LG webOS | Platform player | `packages/ts-player-core` |

Player selection per platform is confirmed during Phases 5, 8 and 9 against the device matrix, not
decided now.

## 4. What `*-player-core` owns

Three implementations (TypeScript, Kotlin, Swift), one specification. Each owns:

- **Session lifecycle** — authorize, start, heartbeat, stop, renew tokens
- **Delivery target fallback** — try targets in order; this is what makes multi-CDN possible later
  ([ADR-0008](../architecture/adr/ADR-0008-cdn-and-origin-abstraction.md))
- **DRM setup** — licence request with the licence token, per-system handling
- **A shared error taxonomy** — mapping platform-specific player errors onto KMS TV error codes
- **QoE telemetry** — start time, rebuffering, bitrate, errors, all carrying `correlation_id`
- **Resume point reporting** — throttled, resilient to network loss
- **ABR policy hints** where the platform allows them

The error taxonomy is the highest-value item. Without a shared mapping, each platform reports failures
in its own vocabulary, and "playback is broken on TVs" becomes six unrelated investigations instead of
one. With it, the same failure produces the same code everywhere, and QoE dashboards are comparable
across platforms.

## 5. TV-specific constraints

TV applications differ from web and mobile in ways that shape the client architecture:

| Constraint | Consequence |
|---|---|
| **No pointer, no keyboard** | Spatial (D-pad) navigation, and device activation instead of typed sign-in ([`../security/identity-and-access.md`](../security/identity-and-access.md) §2) |
| **Limited CPU and memory** | Performance is tested on the **lowest** target device, not a development machine. A UI that is smooth on the newest model can be unusable on a five-year-old one |
| **Slow update propagation** | The server contract is effectively append-only ([`../api/versioning.md`](../api/versioning.md)) |
| **Platform certification** | Store review adds real calendar time to every release; plan release trains around it |
| **Long device lifetimes** | Devices from many years back remain in use and in support scope |
| **Varying media stacks** | Behaviour differs by manufacturer, model year, and firmware — hence the matrix |
| **Old JavaScript engines** | Tizen and webOS applications are web applications running in the TV's browser engine, which may be years behind current. **Build target and polyfill set are a device-matrix output**, not a developer preference — see below |
| **Old TLS stacks and root stores** | A device that cannot negotiate our TLS version, or does not trust our chain's root, simply never connects. There is no server-side error to alert on ([`../security/README.md`](../security/README.md) §4) |
| **Subtitle format support varies** | Which of WebVTT / TTML-IMSC a device renders, and how well, differs by platform and model year. Verified per device, not assumed |

`packages/ts-ui-tv` provides the shared focus/spatial navigation and remote-key handling, so Tizen and
webOS applications differ in platform integration rather than in interaction model.

### Build targets are a compatibility decision

The TypeScript in `ts-player-core`, `ts-ui-tv` and the Tizen/webOS applications compiles to whatever
the **oldest engine in the matrix** supports — not to a modern default. Consequences that are cheap
to accept up front and expensive to retrofit:

- The build target, polyfill set and bundler configuration are derived from OQ-3 and recorded
  alongside the matrix.
- Dependencies that ship only modern syntax, or that assume current browser APIs, are a compatibility
  risk and are checked before adoption
  ([`../architecture/09-dependency-policy.md`](../architecture/09-dependency-policy.md)).
- A dependency that cannot be transpiled to the target is rejected regardless of its other merits.
- **Bundle size and parse time matter far more here than on desktop**, because the device is slow and
  the app is launched cold from a remote control.

### Subtitle delivery

Subtitle and caption tracks are part of the packaging output (`media.tracks`), and the format is
selected per device class from the same mapping table as container and scheme. Two things follow:

- The packaging pipeline must be able to emit **more than one subtitle format** from one source
  track, rather than assuming a single format across the estate.
- Rights are checked for subtitle and audio tracks independently — dubs and subtitle tracks are
  frequently licensed separately, sometimes for different territories
  ([`ingest-and-transcoding.md`](ingest-and-transcoding.md) §4).

## 6. Client conformance tests

Every client implementation runs a conformance suite proving it is a **tolerant reader**
([`../api/versioning.md`](../api/versioning.md) §3):

- [ ] Unknown response fields are ignored
- [ ] Unknown enum values fall back as documented
- [ ] Unknown item types in a rail are skipped, not fatal
- [ ] `delivery.targets` with more than one entry is handled, with fallback exercised
- [ ] `429` is honoured with backoff **and jitter** — a fleet of TVs retrying in lockstep is a
      self-inflicted denial of service
- [ ] Token renewal on heartbeat works, including after a network interruption
- [ ] Every playback error code maps to a localised message
- [ ] `upgrade_required` renders correctly
- [ ] Clock skew does not break token validation

These run in CI per client package, against fixtures generated from the OpenAPI spec
([`../api/openapi-workflow.md`](../api/openapi-workflow.md)). They test the properties that determine
whether a client shipped today still works in three years.

## 7. Device lab

Required from Phase 8. It is infrastructure, not a nice-to-have:

- At least one real device per platform, **including the oldest supported model year**
- Automated where possible; manual verification for certification and release candidates
- Network shaping to reproduce constrained connections — most playback complaints originate in
  network conditions no office connection reproduces
- Test accounts spanning every entitlement and rights configuration in the golden fixture set
- Results recorded against device model **and firmware version**, and treated as an asset with a
  history rather than a one-off report
