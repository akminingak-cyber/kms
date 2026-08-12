# Packaging and Delivery

> Vendor behaviour is **`[UNVERIFIED]`** here — see [`README.md`](README.md#-verification-status).
> Decisions: [ADR-0005](../architecture/adr/ADR-0005-cmaf-multi-drm.md) ·
> [ADR-0008](../architecture/adr/ADR-0008-cdn-and-origin-abstraction.md)

## 1. Packaging

### Target

One CMAF fMP4 segment set, encrypted once, referenced by both an HLS and a DASH manifest.

```
              ┌─────────────────────────┐
              │  CMAF fMP4 segments     │
              │  encrypted with cbcs    │
              └───────┬─────────┬───────┘
                      │         │
             ┌────────▼──┐   ┌──▼────────┐
             │ HLS       │   │ DASH      │
             │ manifest  │   │ manifest  │
             └───────────┘   └───────────┘
```

Why this matters economically as well as technically: separate segment sets per format would divide
CDN cache efficiency between them, and cache offload is the dominant lever on origin egress cost
([`README.md`](README.md) §3). One segment set means one cache key space and one thing to invalidate.

### Encryption scheme

Common Encryption (CENC) defines `cenc` (AES-CTR) and `cbcs` (AES-CBC with pattern encryption).
The design targets **`cbcs` for all three DRM systems** so a single encrypted copy serves everything,
and retains the ability to emit a **`cenc` fallback set** selected per device class if verification
shows some target device cannot handle `cbcs`. `[UNVERIFIED — ADR-0005 blocking checklist]`

**There is a second, independent fallback axis: the container.** Legacy HLS implementations on older
televisions have historically expected MPEG-TS segments rather than fMP4. A device that cannot play
CMAF fMP4 under HLS is not helped by changing the encryption scheme — it needs a different segment
set entirely. Container and scheme are therefore selected independently per device class, and both
are verified on the **oldest** device in the matrix, not the newest.

The device-class → container/format/scheme/DRM mapping is **data, not code**, so it can be corrected
as device-lab results arrive.

### Segment duration

A trade-off with no universally right answer:

| Shorter segments | Longer segments |
|---|---|
| Lower latency | Better compression efficiency |
| Faster ABR adaptation | Fewer requests, less overhead |
| Faster channel change | Better cache behaviour |
| More requests, more overhead | Higher latency |

Chosen per content class and **measured**, not copied from a default. Live sport and news justify
different values from back-catalogue film. If low-latency delivery becomes a requirement, it changes
segment structure, packager configuration and CDN requirements together — so it is decided before
Phase 4 rather than added afterwards.

### Packager

Selection is a Phase 4/5 decision with licence clearance as a gate
([`../architecture/09-dependency-policy.md`](../architecture/09-dependency-policy.md)) — note in
particular that Bento4 is dual GPL/commercial, which likely requires a commercial licence for a
proprietary platform.

Requirements the packager must satisfy:
- CMAF fMP4 output with HLS and DASH manifests over the same segments
- `cbcs` (and ideally `cenc`) encryption with external key provisioning
- Live and VOD packaging
- Multi-period / discontinuity handling (needed for restart, and for SSAI if in scope)
- Operationally supportable: observable, restartable, deterministic output

## 2. Manifests

Manifests are a **client contract**, and they behave like one: a change that looks harmless can break
a player on a television that will never be updated.

Therefore:

- **Golden-file tests** on generated manifests. Manifest regressions are invisible in API tests and
  catastrophic in players ([`../architecture/07-testing-strategy.md`](../architecture/07-testing-strategy.md)).
- Validated with an independent tool as well as our own.
- Manifest changes are reviewed with the same seriousness as API changes.
- **Personalisation belongs in the URL and the token, not in the manifest body** wherever possible —
  a per-user manifest body is a cache entry with one consumer, which destroys offload.

### Who writes what (ADR-0012)

Manifest generation is split, and the split follows what each side actually knows:

| Artifact | Written by | Why |
|---|---|---|
| HLS multivariant playlist | **Control plane** | Derived entirely from the ladder, the packaging profile and the quality class |
| DASH MPD (live) | **Control plane** | `SegmentTemplate` with a fixed duration describes every segment by arithmetic |
| HLS media playlists | **Packager** | Contents change with every segment emitted; only the packager knows what it published |
| Segments, init segments | **Packager** | Media |

### Quality classes, not per-viewer manifests

A licensor's `max_resolution` rule and a plan's resolution cap have to reach the player, and the only
place they can be enforced is the manifest — a client that filters its own renditions is a
client-side security boundary, and the client is never one.

Generating a manifest per viewer would enforce the cap and destroy cache offload with it. So caps are
quantised into a **small, closed, append-only set of quality classes** (`h576`, `h720`, `h1080`,
`h2160`, `full`), the class appears in the origin path, and every viewer sharing a cap shares one
cached manifest. Quantisation rounds **down**; an unrecognised cap is treated as the strictest class.

Segments are not duplicated per class. The class decides which rungs a manifest *mentions*, never
where their segments live, so capped and uncapped viewers still hit the same cached segments.

## 3. Origin

- **The origin is the system of record for media.** The CDN is a cache; deleting it must lose nothing
  but performance.
- **Shielded**: a mid-tier absorbs cache misses so a CDN miss storm — or a CDN failover — cannot
  reach the packagers directly. Without a shield, a regional CDN failure becomes an origin overload
  and then a total outage.
- **Redundant**, with health checking. Origin failure is a full outage.
- Serves live (from the time-shift buffer) and VOD (from object storage) through one addressing
  scheme.
- **Content addressing is vendor-neutral and stable**, derived from asset and rendition identifiers,
  so a second CDN can point at the same origin without re-deriving URLs (ADR-0008).

## 4. CDN

All interaction goes through the `CdnProvider` port (P3). No other context knows a CDN vendor exists.

| Capability | Requirement | Verification |
|---|---|---|
| Token authentication | Per-session tokens scoped to one asset's paths, short-lived | P3 checklist |
| Origin shield | Mid-tier caching | P3 |
| Purge | Granular, with known propagation time | P3 |
| Logs | Real-time and raw, for QoE and licensor reporting | P3 |
| Geo controls | Available — but **defence in depth only**, never the territory control | P3 |
| Live support | Including low-latency if in scope | P3 |

### Multi-CDN

Deferred to Phase 10, prepared from Phase 4: the playback response carries an **ordered list** of
delivery targets, and clients implement fallback from day one even when the list always has one
entry. Clients that cannot handle a list will still be in the field years later, blocking adoption —
which is precisely why it is in the contract from the first release.

## 5. Cache strategy

| Object | Cacheability | Note |
|---|---|---|
| VOD segments | Very long, immutable | Content-addressed |
| VOD manifests | Long, with revalidation | Change only on repackaging |
| Live segments | Short, matching segment duration | High request rate |
| **Live manifests** | **Very short** | The hardest object to cache well; it changes constantly and every player requests it repeatedly |
| Images | Very long, immutable | Content-hashed URLs |
| **API responses** | **Never at the media CDN** | Different security model entirely |

Live manifest handling dominates live delivery cost and correctness. Too much caching and players see
stale manifests and stall; too little and every player request reaches the origin. Getting this right
is a tuning exercise against real traffic, not a configuration to set once.

## 6. Token authentication

- Delivery tokens are issued **only** by playback authorization
  ([ADR-0006](../architecture/adr/ADR-0006-playback-authorization-tokens.md)).
- Scoped to one asset's paths, short-lived (minutes), renewed on heartbeat.
- The **CDN validates the token; it does not make authorization decisions.** A CDN that decides who
  may watch is a CDN that cannot be replaced, and it is a control we cannot audit.
- Token validation failures are logged and monitored — a spike is a signal of either an attack or a
  client bug, and both need to be seen.

### One opaque parameter, not several

Everything that varies per viewer — the signature, the expiry and the session — is packed into a
**single** opaque query parameter. Carried as three parameters, an edge configuration has three
separate things to exclude from its cache key, and missing any one of them produces the failure
below. One parameter is one thing to get right.

### The token must not enter the cache key

A per-session token in the URL query string is, by default, part of the CDN's cache key — which means
**every viewer gets a private copy of every segment and cache offload collapses to zero**. Origin
egress then rises by roughly the offload ratio we were counting on, which is the single largest cost
line in the platform.

So, as an explicit configuration requirement on every CDN adapter (a P3 verification item):

- The token parameter (or cookie) is **excluded from the cache key** while still being **validated**
  at the edge on every request. Validate, then key the cache on the path alone.
- Segment paths stay **identical across viewers**. Anything that varies per viewer belongs in the
  token, never in the path.
- Cache offload ratio is monitored per CDN, and a drop is treated as a cost incident — it is the
  symptom this misconfiguration produces, and it is otherwise invisible until an invoice arrives.

## 7. Delivery observability

Measured **from the viewer's perspective**, not only from ours. Server-side metrics can look perfect
while viewers are suffering, and it is the viewer's experience that determines churn:

| Metric | Source | Why |
|---|---|---|
| Playback start time | Player telemetry | The number viewers actually feel |
| Rebuffer ratio | Player telemetry | The strongest QoE signal |
| Bitrate distribution | Player telemetry | Are viewers getting the quality they pay for? |
| Playback failure rate by error type | Player telemetry | Distinguishes DRM, network and authorization failures |
| Cache offload ratio | CDN logs | The dominant cost lever |
| Origin egress | Origin | Cost, and a leading indicator of cache problems |
| Segment publication latency | Packager | Live health |
| CDN error rate by region | CDN logs | Detects regional failure before viewers report it |

All correlated by `correlation_id` and `session_id` so a single complaint can be traced from the play
button through the decision, the CDN request, the licence request and the QoE events
([`../api/conventions.md`](../api/conventions.md) §8).
