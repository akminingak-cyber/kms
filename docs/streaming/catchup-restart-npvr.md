# Catch-up, Restart and nDVR

> Vendor behaviour is **`[UNVERIFIED]`** here — see [`README.md`](README.md#-verification-status).

## 1. Three features, one buffer

| Feature | What the viewer does | Time range needed |
|---|---|---|
| **Restart** | Jumps to the start of the programme currently airing | Now back to the current programme's start |
| **Catch-up** | Watches a programme that finished hours or days ago | Now back to the retention limit |
| **Pause / time-shift** | Pauses live and resumes behind live | Minutes |

All three are served from the same **rolling time-shift buffer** written continuously per channel.
Building them as three separate mechanisms is a common and expensive mistake — it triples the storage
and produces three sets of edge cases at programme boundaries.

```
    ─────────────────────────────────────────────────────────────►  time
    │◄──────── retention window (OQ-27) ────────────────►│
    │                                                     │
    ├─ catch-up ──────────┬─ restart ──────┬─ live edge ──┤
    │  (finished          │  (current       │  (now)
    │   programmes)       │   programme)    │
    └── same segments, different manifest windows ────────┘
```

Restart and catch-up are **manifest operations over the same segments**, not separate recordings. A
restart manifest is a window from the programme's start to the live edge; a catch-up manifest is a
window bounded by the programme's start and end.

## 2. Rights come first

**Catch-up and restart are separately licensed exploitations**
([`../architecture/06-rights-management.md`](../architecture/06-rights-management.md) §4). This has
consequences that must be built in, not bolted on:

1. A programme may be live-viewable but **not** restartable, or restartable but not catch-up-able.
2. Catch-up windows are usually **relative to transmission** ("available for 7 days after
   broadcast"), so availability is computed per broadcast event, not per title.
3. **Some content must never be written to the buffer at all.** If the right to record does not
   exist, retaining the bytes is itself a breach — hiding the feature in the UI is not sufficient.
4. Rights can differ **within** a programme: an event may be catch-up-able except for a segment
   containing separately licensed material.

### Recording gate

Before any segment is written to the buffer, the pipeline checks whether recording rights exist for
that channel at that moment:

```
segment ready ──► recording right for (channel, time)?
                        │                    │
                       yes                   no
                        │                    │
                  write to buffer      discard immediately
                                       (never persisted, not even briefly)
```

This gate sits in the **media plane** but is driven by the **rights projection**. It is one of the
few places where a control-plane decision has to be enforced inside the media path, and it is
non-negotiable: an audit of "what did you record?" must be answerable with certainty.

Point 4 above means the gate operates on a time-based rights view, not a per-programme flag —
otherwise a mid-programme rights boundary cannot be honoured.

## 3. Programme boundaries

Schedules are approximate; broadcasts are not. Live events overrun, programmes start late, and EPG
data is corrected after the fact.

Consequences:

- **Restart must use actual transmission boundaries where available**, not scheduled ones. Starting a
  restart at the scheduled time when the previous programme overran by four minutes puts the viewer
  in the wrong programme.
- Where the source provides no as-run signalling, boundaries are scheduled times **plus a configured
  padding**, and the imprecision is acknowledged rather than hidden.
- **`schedule.revisions` retains provider corrections**
  ([`../database/conceptual-model.md`](../database/conceptual-model.md)), so a catch-up asset whose
  boundaries were derived from a since-corrected schedule can be re-derived rather than lost.
- Whether as-run data is available from the content source is **OQ-4**, and it materially affects
  restart quality.

## 4. Storage

The nDVR buffer is usually the **largest single storage line in the platform**:

```
storage ≈ channels × retention_hours × Σ(ladder bitrates) × 3600 / 8
```

Every term is a decision with a permanent cost:

- **Channels** — how many are catch-up enabled? Not every channel needs to be.
- **Retention hours** — the largest lever. 7 days versus 30 days is a four-fold difference in the
  biggest storage line the platform has. **OQ-27**, a commercial decision.
- **Ladder** — retaining every rung is the simple option; retaining a reduced ladder for older
  content is a legitimate trade (older catch-up content is watched less, and often on fewer device
  classes).
- **Per-channel policy** — a news channel and a premium film channel do not need the same retention.

Design consequences:

- Retention policy is **data, per channel**, not a global constant.
- **Tiering by age**: recent segments on fast storage, older segments on cheaper storage. Access
  patterns are heavily skewed toward the last 24 hours.
- Expiry is **automatic and monitored**. An unbounded buffer is a cost incident that grows quietly
  until it becomes a very large invoice.
- Expiry must also honour **rights expiry**, which can be shorter than the retention policy: a
  catch-up right ending is a mandatory deletion, not an optional cleanup.

## 5. Catch-up as VOD

For content that is watched well beyond the buffer window — a popular series, a major event — a
scheduled job can promote a catch-up recording into a proper VOD asset:

```
buffer segments ──► clip at programme boundaries ──► repackage as VOD ──► catalog
```

This is worth doing when it applies, because VOD assets cache far better than time-windowed manifests
over a live buffer, and they can be retained independently of the rolling buffer.

It requires: VOD rights (a **different** exploitation again), an editorial or automated decision about
what to promote, and its own storage lifecycle.

## 6. Playback authorization

Restart and catch-up sessions go through the same authorization path with `mode` set accordingly
([`../security/playback-authorization.md`](../security/playback-authorization.md)). The rights check
evaluates the **`restart` or `catchup` exploitation**, not `live` — which is exactly why `mode` is
part of the request rather than inferred from the URL.

Additional considerations:

- Concurrency counts against the same limits as live.
- Some agreements forbid seeking or fast-forwarding within catch-up content (typically to preserve
  advertising). If that applies, it is a **usage rule** carried from rights through to the client, not
  a hardcoded player behaviour.
- Blackouts apply to catch-up as well as live, and a blackout can be applied retrospectively to
  already-recorded content — so the availability projection, not the recording, is the authority at
  playback time.

## 7. Operational realities

- **The buffer writer must never block the live path.** A storage problem must degrade catch-up, not
  live viewing. This is a hard isolation requirement.
- **Gaps happen** — ingest interruptions, storage failures, restarts. Gaps must be detected,
  recorded, and reflected in manifests rather than producing a stalling player.
- **Buffer health is monitored per channel**: write continuity, storage headroom, expiry job success,
  and the actual retention achieved versus the policy.
- **Restart from the live edge is a burst load.** When a popular programme starts, many viewers press
  restart within the same minute — a different traffic shape from steady live viewing, and one that
  must be load-tested rather than assumed.
