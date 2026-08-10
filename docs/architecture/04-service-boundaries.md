# Service Boundaries

## 1. The decision: modular monolith first, extraction by evidence

KMS TV starts as **one Laravel codebase (`core-api`) containing all control-plane contexts as
enforced modules**, deployed as **several independently scaled runtime profiles**, with a small
number of genuinely separate services where the reason is technical and specific.

Full rationale: [`adr/ADR-0001-modular-monolith-first.md`](adr/ADR-0001-modular-monolith-first.md).

The short version: sixteen contexts extracted into sixteen services on day one would multiply the
cost of every cross-cutting change, force distributed transactions across billing and entitlements
before the model is stable, and demand platform maturity the project does not yet have. The
monolith is not a compromise — it is the correct starting shape for a domain whose boundaries are
still hypotheses. What matters is that the boundaries are **enforced from the first commit** so that
extraction is cheap when evidence arrives.

## 2. Deployment units

The distinction that matters: **a bounded context is a modelling boundary; a deployment unit is an
operational boundary.** They are chosen by different criteria. Contexts are chosen by language and
ownership; deployment units are chosen by traffic profile, failure blast radius, security isolation,
and scaling economics.

| Deployment unit | Contexts served | Codebase | Why separate | Phase |
|---|---|---|---|---|
| **core-api (web tier)** | All control-plane contexts | `services/core-api` | The default. Transactional, low RPS | P1 |
| **core-api (admin tier)** | Same code, admin routes only | `services/core-api` | An admin bulk import must never contend with subscriber traffic — separate pods, separate DB pool, separate rate limits | P2 |
| **core-api (workers)** | All — queue consumers | `services/core-api` | Different failure and scaling behaviour to request handling | P1 |
| **playback-authorizer** | D2 Playback Authorization (+ read-only use of C3, D1, A2, A3) | `services/core-api` initially | **Highest-criticality, highest-RPS, latency-critical path.** Runs the same code with a restricted route set, its own replica count, its own caches and its own database credentials (read-mostly). Extracted to its own codebase only if profiling shows the shared runtime is the constraint | P4 |
| **drm-license-proxy** | D3 Content Protection | `services/drm-license-proxy` | **Separate from day one, for security not scale.** It is the only workload holding key-vault credentials and vendor DRM secrets. Compromise of core-api must not yield content keys. Different codebase, different network zone, minimal dependencies | P6 |
| **media-pipeline** | B3 Media Assets | `services/media-pipeline` | Entirely different runtime (FFmpeg, large local disk, long-running jobs, possibly GPU). Cannot share a PHP web image sensibly | P5 |
| **epg-ingest** | B2 Channels & Schedule | `services/epg-ingest` | Scheduled, bursty, provider-shaped. Isolating it means a malformed provider feed cannot exhaust web-tier workers | P2 |
| **telemetry-collector** | E2 Analytics | `services/telemetry-collector` | Write volume orders of magnitude above everything else; must be droppable under load without affecting playback | P10 |

Four of these (`core-api` web/admin/workers/authorizer) are the **same artifact with different
configuration**. This gives operational isolation at almost zero engineering cost — the most
underrated move available at this stage.

## 3. Synchronous vs asynchronous

### Synchronous (request/response) is used when

- A client is waiting and the answer determines what happens next — playback authorization,
  sign-in, page load.
- The caller cannot proceed without the answer and cannot compensate later.

### Asynchronous (events/queues) is used when

- The work can fail and be retried without the user noticing — encoding, notifications, projections,
  analytics, search indexing.
- The consumer must not be able to slow the producer.
- Multiple consumers care about the same fact.

### Events

Domain events are published within `core-api` on a transactional outbox: the event row is written in
the **same database transaction** as the state change, then relayed to the queue by a dispatcher.
Nothing publishes directly from application code to a broker, because the failure mode — state
committed, event lost, or event published then state rolled back — produces exactly the kind of
silent inconsistency that is unfindable months later in billing and entitlement data.

Transport: **Redis-backed queues (Laravel queues + Horizon) initially.** A dedicated event broker
(Kafka/NATS/Pulsar) is deferred until there is a real consumer outside the monolith and a real
retention requirement. See
[`adr/ADR-0001-modular-monolith-first.md`](adr/ADR-0001-modular-monolith-first.md).

Event naming: `context.aggregate.event_past_tense`, e.g. `billing.subscription.renewed`,
`rights.availability.window_opened`, `playback.session.started`. Every event carries
`event_id`, `occurred_at`, `version`, `correlation_id`, and `causation_id`.

Event payload rule: **events carry identifiers and the facts that changed, not whole aggregates.**
A fat event becomes a de facto shared schema that every consumer couples to.

## 4. The playback hot path

This is the path that must not fail, so its dependencies are constrained deliberately.

```
client ──POST /playback/sessions──► playback-authorizer
                                          │
                    ┌─────────────────────┼─────────────────────┐
                    ▼                     ▼                     ▼
            entitlement snapshot   rights evaluation     device + profile
            (materialised, cached)  (cached rule set)    (cached)
                    │                     │                     │
                    └─────────────────────┼─────────────────────┘
                                          ▼
                                    DECISION + audit record
                                          │
                    ┌─────────────────────┴─────────────────────┐
                    ▼                                           ▼
            manifest URL + CDN token                    DRM licence token
            (D4 Delivery Control)                       (signed, short-lived,
                                                         single content + session)
                                          │
      player ──licence request + token──► drm-license-proxy ──► DRM vendor
```

Constraints on this path, enforced in code review and load tests:

1. **No synchronous call to Subscriptions & Billing.** It reads the materialised entitlement
   snapshot. Billing being down must not stop existing subscribers from watching.
2. **No synchronous call to any third party** except the DRM licence exchange, which happens
   *after* authorization and is a separate request from the player.
3. **No write to a table that admin traffic writes to** except the append-only session/audit store.
4. **Every dependency has a defined degraded behaviour** — documented in
   [`docs/security/playback-authorization.md`](../security/playback-authorization.md). "Fail open" is
   never the default for a rights check; "fail closed with a specific error code" is.

## 5. Extraction criteria

A module is extracted into its own service only when at least one is true, evidenced not asserted:

1. Its scaling profile diverges enough that co-location wastes significant capacity (measured).
2. Its failure must be isolated from the rest for availability reasons (argued and accepted).
3. It requires credentials or a network zone the monolith must not have (security).
4. It needs a runtime the monolith cannot host (FFmpeg, GPU, a non-PHP runtime).
5. Independent release cadence is genuinely blocking delivery (measured in blocked PRs, not felt).

Team-size arguments alone ("two teams so two services") are not sufficient. Module ownership inside
a monorepo with enforced boundaries already gives most of that benefit without the distributed
systems tax.

## 6. Service-to-service communication

- Internal calls are **authenticated**; no implicit trust from network position. Mechanism (mTLS vs
  signed service tokens) is decided in Phase 4 with the hosting decision (**OQ-16**).
- Every internal call has an explicit timeout, a retry policy with jitter for idempotent operations
  only, and a circuit breaker.
- Every call carries `correlation_id` end to end, including into CDN and player telemetry, so a
  single viewer complaint can be traced across all four planes.
- Internal APIs are **not** the public API. They are versioned independently and never exposed at
  the edge (see [`docs/api/surfaces.md`](../api/surfaces.md)).
