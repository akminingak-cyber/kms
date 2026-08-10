# Playback Authorization

The most critical path in the platform. If it is wrong, viewers see content they should not, or
paying viewers cannot watch. If it is slow, every play button in the product feels broken. If it is
down, the platform is down in the only way viewers actually measure.

Decision record: [ADR-0006](../architecture/adr/ADR-0006-playback-authorization-tokens.md)

## 1. The decision

```
POST /api/client/v1/playback/sessions
Authorization: Bearer <access token — carries account, profile and device binding>
Idempotency-Key: <client-generated identifier>
{
  "content_ref":  { "type": "channel|title|programme", "id": "0199c…" },
  "mode":         "live|restart|catchup|vod",
  "capabilities": { "drm": ["widevine","playready","fairplay"],
                    "formats": ["hls","dash"],
                    "containers": ["cmaf","ts"],
                    "max_resolution": "1080p",
                    "hdcp": "2.2" }
}
```

> **The request body does not carry a device identifier, an account, or a profile.** All three come
> from the access token, which is device-bound
> ([`identity-and-access.md`](identity-and-access.md) §2). A client-asserted `device_id` would let a
> caller present a different device's identity and inherit its class — and **device class is an input
> to the rights and DRM decision**, so that would be a privilege escalation, not a cosmetic problem.
>
> `capabilities` **is** client-asserted, and is treated accordingly: it may only ever *narrow* what is
> offered. A client claiming a capability it lacks gets a stream it cannot play; a client claiming a
> capability that would *raise* its entitlement — a higher resolution, a weaker protection
> requirement — is ignored, because those bounds come from rights and from the server-side device
> class, never from the request.

Evaluated in this order. **First failure stops evaluation** and returns a specific reason code —
cheapest checks first, so an abusive client is rejected before expensive work happens:

| # | Check | Source | Failure code |
|---|---|---|---|
| 1 | Token valid and not revoked; device still registered | Token signature + revocation set; A3 | `AUTH_REQUIRED` / `DEVICE_NOT_REGISTERED` |
| 2 | Content exists and is playable | B1, B3 | `PLAYBACK_CONTENT_UNAVAILABLE` |
| 3 | Territory determined | P11 | `PLAYBACK_TERRITORY_UNDETERMINED` |
| 4 | **Rights allow it** — window, territory, platform, exploitation, blackout | D1 projection | `PLAYBACK_OUTSIDE_LICENCE_WINDOW`, `…NOT_AVAILABLE_IN_TERRITORY`, `…PLATFORM_NOT_PERMITTED`, `…BLACKED_OUT` |
| 5 | **Entitlement grants it** | C3 snapshot | `PLAYBACK_NOT_ENTITLED` |
| 6 | Parental rating permits it | A2 | `PLAYBACK_PARENTAL_BLOCKED` |
| 7 | Device capability meets required protection | D1 usage rules + client capabilities | `PLAYBACK_DEVICE_NOT_SUPPORTED` |
| 8 | Concurrency slot available | D2 | `PLAYBACK_CONCURRENCY_EXCEEDED` |

On success: a concurrency slot is claimed, a session and a decision record are written, and the two
tokens are issued.

```json
{
  "session_id":  "0199c1a4-7e3b-7c21-9f4d-2b6a8e05d913",
  "decision_id": "0199c1a4-7e3b-7c21-9f4d-2b6a8e05d914",
  "delivery": {
    "targets": [ { "format": "hls",
                   "container": "cmaf",
                   "url": "https://cdn…/manifest.m3u8?token=…",
                   "priority": 1 } ],
    "expires_at": "2026-08-10T14:35:00Z"
  },
  "protection": {
    "system": "widevine",
    "licence_url": "https://licence…/widevine",
    "licence_token": "…",
    "expires_at": "2026-08-10T14:35:00Z"
  },
  "restrictions": { "max_resolution": "1080p", "hdcp": "2.2" },
  "heartbeat_interval_seconds": 30
}
```

`delivery.targets` is an **array from day one**, even when it always has one entry — otherwise every
TV in the field blocks multi-CDN adoption later
([ADR-0008](../architecture/adr/ADR-0008-cdn-and-origin-abstraction.md)).

`container` is present alongside `format` because they are **independent** fallback axes: a device
may need HLS with MPEG-TS rather than HLS with CMAF fMP4, and that is a different problem from
needing a different encryption scheme
([ADR-0005](../architecture/adr/ADR-0005-cmaf-multi-drm.md)). Both are chosen server-side from the
device-class mapping, never from the client's request.

The URL carries the delivery token as a query parameter. **The CDN validates it but must exclude it
from the cache key** — otherwise every viewer gets a private copy of every segment
([`../streaming/packaging-and-delivery.md`](../streaming/packaging-and-delivery.md) §6).

## 2. Why the order matters

Rights (4) is evaluated **before** entitlement (5) deliberately. If content is not licensed for this
territory, that is true regardless of what the customer bought, and telling them "upgrade your
package" would be wrong and would generate a support contact and a refund request. Correct error
messages come from correct evaluation order.

## 3. Heartbeats

Sessions send a heartbeat at the server-specified interval. Each heartbeat:

1. Refreshes the concurrency slot's TTL
2. Re-checks the **cheap, volatile** conditions: session valid, subscription not cancelled mid-stream,
   **no blackout newly in force**
3. Extends the delivery and licence tokens if still authorized
4. Records QoE telemetry, asynchronously and non-blocking

**Blackouts starting mid-event are the reason heartbeat re-evaluation exists.** A sports blackout
that only applies at session start is not a blackout. Full re-evaluation on every heartbeat would be
too expensive; the volatile subset is re-checked, and a rights change publishes an invalidation that
affected sessions pick up on their next heartbeat.

A session with no heartbeat past a grace period is closed and its slot released, which is what stops
crashed clients from permanently consuming a viewer's concurrency allowance.

## 4. Concurrency

- Enforced with a Redis counter per account (or per plan-defined scope) with TTL, mirrored durably in
  `playback.concurrency_slots`.
- Limits come from two independent sources: the **plan** (commercial) and **rights usage rules**
  (contractual). **The lower always wins** — a licensor's cap is not negotiable by selling a bigger
  plan.
- **The two sources fail differently, and the decision must know which one it is enforcing.** A plan
  limit may fail open under infrastructure failure; a licensor cap may not (§6). The concurrency
  check therefore returns *which* limit bound the request, and that is recorded in the decision.
- Slot release: on explicit stop, on heartbeat expiry, on session end.
- **Playback session start is idempotent** by `Idempotency-Key` — otherwise a TV retrying over a flaky
  connection consumes several slots and the viewer sees "too many streams" while alone in their
  living room.

## 5. Territory determination

- Primary signal: IP geolocation via the `GeoLocation` port (P11).
- VPN/proxy detection is a **signal, not a verdict**. Blocking every flagged connection blocks
  legitimate corporate and mobile-carrier networks.
- Additional signals (billing address, SIM country, account registration territory) may be combined.
- **Which signal wins when they disagree is a commercial and contractual decision (OQ-20)**, not an
  engineering default. Different content agreements may even demand different answers.
- **The determination and its method are recorded with every decision.** When a licensor asks how we
  concluded a viewer was in a given country, the answer must be a record.

## 6. Degraded modes

Every dependency has a defined and tested behaviour. "Fail open" never appears in the rights or
entitlement column.

| Dependency unavailable | Behaviour | Rationale |
|---|---|---|
| Rights availability projection | **Deny**, `PLAYBACK_TEMPORARILY_UNAVAILABLE` | Unauthorized playback is a contract breach. Refusing is recoverable; breaching is not |
| Entitlement snapshot | **Deny** — unless a short, explicitly configured grace window using a recently cached snapshot applies | Bounded, deliberate, monitored. Never unbounded |
| Profile / parental data | **Deny** | Cannot verify a child's protection: do not play |
| Geolocation provider | Fall back to a cached determination for the session; if none, **deny** for territory-restricted content, allow for content with no territory restriction | Proportionate to the actual constraint |
| Concurrency store — content with **no licensor concurrency cap** | Allow, **log prominently, alert** | Exceeding a *plan* limit is a revenue nuisance. This is the one deliberate soft-fail, and it is monitored |
| Concurrency store — content **with** a licensor concurrency cap | **Deny** | A licensor-imposed cap is a contract term. Failing open on it is a breach, exactly like failing open on a territory. See §4 |
| Token signing keys unavailable | **Deny** | Cannot issue a token we can later validate |
| DRM licence proxy | Deny at authorization rather than issue a token that cannot be redeemed | A clear error beats a spinning player |
| CDN adapter | Try the next delivery target; if none, deny | Why targets is a list |
| Decision audit write | **Deny** | If we cannot record the decision, we cannot defend it. Non-negotiable for licensed content |

That last row is worth stating plainly: **if the audit record cannot be written, playback does not
start.** It looks harsh, and it is the difference between an auditable platform and an unauditable
one.

## 7. Performance

- Target: **p99 ≤ 150 ms** server-side ([`../architecture/01-system-context.md`](../architecture/01-system-context.md)).
- Achieved by reading **materialised** projections rather than evaluating rules per request; caching
  hot data with event-driven invalidation; no synchronous third-party call in the path; deployment on
  its own profile with its own replicas and database role.

### Token validation must not touch the database

Access token validation is **stateless**: an asymmetric signature check against a published key set,
plus a lookup in a small in-memory revocation set replicated to every authorizer instance. The
authorizer therefore holds **no grant on the `identity` schema at all**
([`../database/README.md`](../database/README.md) §2).

This is a boundary decision, not an optimisation. If validating a token required reading `identity`,
then the identity store would be a hard dependency of the playback hot path, its load would scale
with play requests rather than with sign-ins, and a compromise of the authorizer would reach
credential data. Revocation accepts a bounded propagation delay — measured in seconds and monitored —
in exchange for that separation.

### The decision write is on the hot path, deliberately

§6 requires denying playback if the decision record cannot be written, which puts a durable write
inside the latency budget. That is affordable only if it stays a **single append**:

- One `INSERT` into `playback.decisions`, time-partitioned, **with no indexes beyond the partition
  key and `decision_id`**. Query-driven indexes go on an offline copy, not on the write path.
- No synchronous wait for replica acknowledgement.
- **Heartbeats do not write a decision record unless the decision changed.** Writing one per
  heartbeat multiplies the platform's highest-volume control-plane write by the heartbeat rate for
  no evidential gain — the record that matters is the one where the answer changed.
- Session state is written on start, on significant change, and on end. Heartbeat liveness lives in
  Redis ([`../database/scaling-and-retention.md`](../database/scaling-and-retention.md) §4).
- Load tested before each phase that increases audience, and with headroom for predictable live-event
  peaks — which, unlike most traffic, arrive all at once at a known minute.

## 8. Audit record

Every decision, allowed or denied, records:

```
decision_id · occurred_at · account_id · profile_id · device_id · device_class
client + version · content_ref · mode · outcome · reason_code
availability_version · rights_rule_ids[] · entitlement_grant_ids[]
territory + determination_method · applied_restrictions · correlation_id
```

Requirements this satisfies
([`../architecture/06-rights-management.md`](../architecture/06-rights-management.md) §5): decisions
are explainable, reproducible against recorded versions, and retained for the contractually required
period (**OQ-19**).
