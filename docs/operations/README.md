# Operations

## Contents

| Document | Covers |
|---|---|
| This document | Operating model, SLOs, on-call, capacity |
| [`local-development.md`](local-development.md) | Getting a working environment, and what this container cannot do |
| [`environments.md`](environments.md) | Environment definitions and promotion |
| [`ci-cd.md`](ci-cd.md) | Pipelines, gates, deployment, release trains |
| [`observability.md`](observability.md) | Logs, metrics, traces, alerts |
| [`release-and-incident-management.md`](release-and-incident-management.md) | Releasing, rolling back, incidents |

---

## 1. Operating model

The four planes ([`../architecture/01-system-context.md`](../architecture/01-system-context.md)) have
different operational characteristics, and treating them uniformly guarantees the wrong response at
the wrong time:

| Plane | Failure means | Response |
|---|---|---|
| Control | Sign-ups and admin work stop; existing playback continues | Urgent, business hours acceptable outside peak |
| **Decision** | **Nobody can start a stream** | **Page immediately, any hour** |
| **Media** | Playback fails or degrades for everyone | **Page immediately, any hour** |
| Insight | No visibility; reporting at risk | Next business day, unless licensor reporting is due |

## 2. Service level objectives

Placeholders until audience scale (**OQ-2**) and the launch commitment are known. They are stated so
they can be argued with — an SLO nobody has challenged is a number, not an objective.

| Service | SLI | Target |
|---|---|---|
| Playback authorization | Availability | 99.95% |
| Playback authorization | Latency p99 | ≤ 150 ms |
| **Playback start success** | Authorized requests that reach playing state | **≥ 99.5%** |
| Rebuffer ratio | Time rebuffering ÷ time watching | ≤ 0.5% |
| Client API (non-playback) | Availability | 99.9% |
| Client API | Latency p95 | ≤ 300 ms |
| Admin API | Availability | 99.5% |
| Live channel continuity | Uptime per channel | 99.9% |
| EPG freshness | Time since successful ingest | ≤ 2 h |

**Playback start success is the one that matters most**, because it is the only SLI that measures what
a viewer experiences. Every server-side metric can be green while viewers cannot watch — a CDN
problem, a DRM licence failure, or a client bug all produce exactly that shape. It is measured from
**player telemetry**, not from server responses.

Error budgets are derived from these targets and are what decides whether the next change ships or
the next week is spent on reliability. That decision should be mechanical, not political.

## 3. On-call

- Required from **Phase 4** — once a stream can be started, someone must be reachable when it cannot.
- **Every alert has a runbook.** An alert without one is deleted or given one; waking someone with no
  guidance produces slow, error-prone responses and burns out the rota.
- Alerts are **symptom-based, not cause-based**: "playback start success is below target" rather than
  "CPU is high". Viewers do not experience CPU.
- Escalation paths are defined and, more importantly, **tested** before they are needed.
- **Predictable peaks are staffed deliberately.** Major live events arrive at a known minute with a
  step-change in load — unlike organic traffic, they can and should be planned for.

## 4. Capacity

| Resource | Driver | Planning |
|---|---|---|
| Playback authorization | Concurrent streams **and start rate** | Load test per phase; headroom for live-event peaks |
| CDN egress | Concurrent viewers × bitrate | Committed volume with burst allowance |
| Origin egress | CDN misses | Watch cache offload as the leading indicator |
| Transcoding | Channels + VOD backlog | Live is fixed; VOD is queue-elastic |
| Storage | Library + **nDVR retention** | nDVR usually dominates ([`../streaming/catchup-restart-npvr.md`](../streaming/catchup-restart-npvr.md)) |
| Database | Write volume, connections | Partition runway, replica lag, connection headroom |

The distinction between concurrent streams and **start rate** matters: a live event's opening minute
produces a start-rate spike far above steady-state concurrency, and it is the start path — the
decision plane — that saturates first.

## 5. Cost

Delivery and storage dominate, and both are consequences of architectural decisions rather than
operational tuning ([`../streaming/README.md`](../streaming/README.md) §3). Cost per viewing hour is
tracked as a first-class metric from Phase 4, broken down by CDN egress, origin egress, storage,
transcoding and compute — because by the time an invoice is surprising, the decisions that caused it
are months old.

## 6. Documentation that must exist before launch

- [ ] A runbook per alert
- [ ] Channel-down procedure
- [ ] Origin and CDN failover procedure
- [ ] Database failover and restore procedure, **rehearsed**
- [ ] Key compromise procedure ([`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md))
- [ ] Payment provider outage procedure
- [ ] Escalation matrix, with names and numbers
- [ ] Communication templates for viewer-facing incidents

Every one of these is walked through at least once before launch. A procedure that has only been
written has not been tested, and an incident is the wrong time to discover that a step is wrong.
