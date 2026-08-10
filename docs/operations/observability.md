# Observability

## 1. The question observability must answer

> A viewer says: *"It doesn't play on my TV."*
> With a session identifier, we must be able to reconstruct exactly what happened — the play request,
> the authorization decision and why it went that way, the manifest requests, the licence request, and
> the player's own experience.

If that chain is broken anywhere, playback problems become guesswork, and playback problems are the
ones that lose subscribers. Everything below exists to keep that chain intact.

## 2. Correlation

One identifier links the whole journey:

```
client action → API request → authorization decision → CDN request
              → licence request → QoE telemetry
```

- `correlation_id` accepted from the client or generated at the edge, echoed in responses, present in
  **every** log line and propagated to every downstream call.
- `session_id` and `decision_id` are returned to the client and included in player telemetry.
- Where the CDN supports it, the correlation identifier is carried into delivery logs — a P3
  verification item ([`../architecture/05-integration-boundaries.md`](../architecture/05-integration-boundaries.md)).

## 3. Logs

- **Structured JSON**, one event per line. Never free-text logs that require a regex to parse under
  incident pressure.
- Every line carries: timestamp, level, service, deployment profile, version, `correlation_id`, and
  the actor where applicable.
- **Redaction by field name and by value pattern**, applied in the logging layer so it cannot be
  forgotten at a call site. No secret, token, key, or PAN ever reaches a log
  ([`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md)).
- Levels used consistently: `error` means someone must act; `warn` means a degraded but handled
  condition. A log at `error` that nobody acts on trains everyone to ignore errors.
- **Security events go to a separate, append-only stream** with independent retention and access
  control ([`../security/threat-model.md`](../security/threat-model.md) §4).

## 4. Metrics

### Golden signals per service
Rate, errors, duration, saturation — the baseline for everything.

### Business and correctness metrics

These are the ones that distinguish a monitored platform from an observed one. Each is a **correctness**
signal, not a performance one:

| Metric | Why it matters |
|---|---|
| **Playback start success rate** | The only metric that measures what viewers experience |
| Playback denials **by reason code** | A spike in `NOT_ENTITLED` is a billing bug; a spike in `OUTSIDE_LICENCE_WINDOW` is a rights bug. Indistinguishable without specific codes ([`../api/conventions.md`](../api/conventions.md)) |
| Rebuffer ratio, bitrate distribution | Quality as experienced |
| **Entitlement snapshot staleness (p99)** | Stale snapshots mean unentitled viewing or wrongly blocked customers |
| **Availability projection recompute lag** | Rights changes not taking effect is a contract exposure |
| Concurrency rejections | Distinguishes genuine limits from leaked sessions |
| Licence issuance rate and failure rate | DRM health, and an anomaly signal |
| Subscription state transitions | Billing health |
| Payment success and decline rates | Revenue health, and fraud signal |
| EPG ingest freshness and revision counts | Content correctness |
| Cache offload ratio, origin egress | Cost, and a leading indicator of delivery problems |
| Cost per viewing hour | Sustainability |

### Client-side telemetry

Server metrics can be entirely green while viewers cannot watch. **Player telemetry is not optional**:

- Playback start time, rebuffering events and duration, bitrate switches, errors with our shared
  taxonomy, device and app version, network type.
- Emitted by `*-player-core` on every platform, so the data is comparable across clients
  ([`../streaming/player-and-device-matrix.md`](../streaming/player-and-device-matrix.md)).
- Pseudonymised at collection
  ([`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md)).
- **Never a synchronous dependency of playback.** Telemetry that can block playback is a liability,
  not an asset.

## 5. Tracing

- Distributed tracing across services from Phase 4, when the decision plane separates.
- **The playback authorization path is always traced** — it is the latency-critical path and the one
  where a slow dependency is otherwise invisible.
- Sampling: high for errors and slow requests, low for the steady state, with the ability to force a
  trace by correlation identifier when investigating a specific complaint.

## 6. Alerting

**Symptom-based, not cause-based.** Alert on what viewers experience:

| Alert | Severity |
|---|---|
| Playback start success below target | **Page** |
| Playback authorization latency p99 above target | **Page** |
| Live channel output discontinuity | **Page** |
| Origin or CDN error rate elevated | **Page** |
| **Content key read outside the licence proxy** | **Page — security** |
| Playback authorized against an invalid entitlement | **Page — correctness** |
| Rights or price change without an approval record | **Page — security** |
| Entitlement staleness or projection lag above threshold | Alert |
| Payment failure rate elevated | Alert |
| EPG ingest stale | Alert |
| Partition runway low | Alert |
| Queue depth growing | Alert |
| Certificate expiring | Alert, well ahead |
| Storage or cost trending above forecast | Ticket |

Rules:

1. **Every alert has a runbook.** No exceptions — an alert without one is deleted or given one.
2. **Every page is actionable by the person receiving it.** If the only response is to escalate, the
   alert should page the right person directly.
3. **Alert fatigue is treated as an incident in itself.** A noisy alert is fixed or removed, promptly;
   a rota that has learned to ignore alerts is worse than no rota.
4. Alerts are tested — including the path to a human.

## 7. Dashboards

| Dashboard | Audience | Content |
|---|---|---|
| Viewer experience | Everyone | Start success, rebuffering, errors by platform |
| Decision plane | Engineering, on-call | Authorization rate, latency, denials by reason |
| Media plane | Broadcast ops | Channel health, ingest, packaging, origin, CDN |
| Control plane | Engineering | API health, queues, database |
| Commercial | Product, finance | Subscriptions, payments, conversion, churn |
| Cost | Platform, finance | Egress, storage, compute, cost per viewing hour |
| Security | Security | Auth failures, admin actions, key access, anomalies |

Dashboards are **defined as code** in `infrastructure/observability/` alongside alert rules and SLO
definitions, so they are reviewed, versioned and reproducible rather than clicked together and lost.

## 8. Retention

| Data | Retention | Driver |
|---|---|---|
| Application logs | ~30 days hot, archived beyond | Cost versus investigation need |
| Security events | Long | Compliance; separate access control |
| Metrics | Long at reduced resolution | Trend analysis and capacity planning |
| Traces | Short, sampled | Volume |
| **Playback decisions** | **Contractual** | Licensor audit — **OQ-19** |
| Player telemetry | Raw archived; aggregates kept longer | Analytics value |
