# services/

Deployable backend services and workers.

**Nothing here yet.** `core-api` is created in Phase 1.

## Planned

| Directory | Stack | Why it is (or is not) separate | Phase |
|---|---|---|---|
| `core-api/` | Laravel, PHP 8.3+ | The modular monolith. All control-plane contexts as modules with CI-enforced boundaries | 1 |
| `playback-authorizer/` | Same codebase, separate deployment profile | Latency-critical, high-RPS, must be scaled and isolated separately. Extracted to its own codebase only if measurement demands it | 4 |
| `drm-license-proxy/` | Separate service | **Security isolation.** The only component that can resolve a content key by identifier | 6 |
| `media-pipeline/` | FFmpeg + packager workers | Different runtime: large disk, long jobs, possibly GPU | 5 |
| `epg-ingest/` | Workers | Failure isolation — a malformed provider feed must not exhaust web-tier workers | 2 |
| `telemetry-collector/` | High-write intake | Write volume orders of magnitude above everything else; must be droppable under load. Also the platform's largest client-supplied write surface — see threat T13 | 10 |

## Rules

- A **bounded context** is a modelling boundary; a **deployment unit** is an operational one. They are
  chosen by different criteria and are not the same thing.
- Extracting a module into its own service requires meeting a criterion in
  [`../docs/architecture/04-service-boundaries.md`](../docs/architecture/04-service-boundaries.md) §5,
  **with evidence**. "Two teams" is not evidence.
- Inside `core-api`, a module may import from another module **only** through its `Contracts/`
  namespace. Enforced by CI — a violation fails the build.
- Every service defines its degraded behaviour for every dependency. "Fail open" is never the default
  on a rights or entitlement check.

See [`../docs/architecture/04-service-boundaries.md`](../docs/architecture/04-service-boundaries.md)
and [ADR-0001](../docs/architecture/adr/ADR-0001-modular-monolith-first.md).
