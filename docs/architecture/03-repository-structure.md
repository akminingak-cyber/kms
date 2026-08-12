# Repository Structure

## 1. Why a monorepo

KMS TV has one product spread across six client platforms, one API contract, and one rights model.
The dominant risk is **contract drift** — the Tizen app expecting a field the API stopped sending,
the mobile app disagreeing with the web app about what "available" means. A monorepo makes the
contract and its consumers change in the same commit and be verified by the same CI run.

The cost is tooling complexity and a large checkout. That cost is accepted and mitigated with sparse
checkout and per-workspace CI targeting (see [`docs/operations/ci-cd.md`](../operations/ci-cd.md)).

The iOS application is the one likely exception: App Store tooling, code signing, and macOS build
agents may make a separate repository pragmatic. That is deferred to Phase 8 and recorded as
**OQ-15**, not decided now.

## 2. Top-level layout

```
kms/
├── apps/                     # Deployable end-user applications (one per client platform)
├── services/                 # Deployable backend services and workers
├── packages/                 # Shared libraries — never deployed on their own
├── infrastructure/           # Everything needed to run it: IaC, containers, edge config
├── docs/                     # Architecture, security, streaming, database, api, operations
├── tools/                    # Repo tooling: generators, checks, scripts (added when needed)
├── ARCHITECTURE.md
├── CLAUDE.md                 # Engineering rules for all contributors, human and AI
└── README.md
```

Only the five directories named in the brief plus `tools/` exist. **No application directory is
created before the phase that builds it.** Empty scaffolding invites premature dependencies.

## 3. Target layout (end state, built incrementally)

Entries are annotated with the phase that creates them. Nothing beyond Phase 1 exists yet.

```
apps/
├── web/                      # P5  Next.js — viewer web app (browse, play, account)
├── admin/                    # P4  React + Vite, static SPA — the operator panel.
│                             #     Deliberately not Next.js: no SSR value for a
│                             #     token-authenticated internal tool (ADR-0013)
├── android/                  # P8  Kotlin, AndroidX Media3 — phone + tablet
├── androidtv/                # P9  Kotlin, Media3, Leanback — Android TV / Google TV
├── ios/                      # P8  Swift, AVFoundation — iOS + iPadOS  (see OQ-15)
├── tizen/                    # P9  Samsung Tizen web app
└── webos/                    # P9  LG webOS web app

services/
├── core-api/                 # P1  Laravel modular monolith — the control plane.
│   └── modules/              #     One module per bounded context, boundaries enforced in CI
│       ├── Identity/
│       ├── Profile/
│       ├── Device/
│       ├── Catalog/
│       ├── Schedule/
│       ├── MediaAsset/
│       ├── Discovery/
│       ├── Product/
│       ├── Billing/
│       ├── Entitlement/
│       ├── Rights/
│       ├── Playback/
│       ├── Protection/
│       ├── Delivery/
│       ├── Administration/
│       └── Notification/
├── playback-authorizer/      # P4  Same codebase as core-api, separate deployment profile.
│                             #     Extracted to its own binary only if measurement demands it
├── drm-license-proxy/        # P6  Isolated: sole resolver of content keys by id
├── media-pipeline/           # P5  Encode/package orchestration workers (FFmpeg + packager)
├── epg-ingest/               # P2  Schedule ingestion, normalisation, reconciliation workers
└── telemetry-collector/      # P10 High-write playback/QoE event intake

packages/
├── api-contracts/            # P1  OpenAPI specs — the source of truth for every client API
├── ts-api-client/            # P4  Generated TypeScript client (generated, never hand-edited)
├── ts-player-core/           # P5  Shared player logic: ABR policy, DRM setup, error taxonomy,
│                             #     telemetry beacons. Used by web, tizen, webos
├── ts-ui-tv/                 # P9  Shared TV UI primitives: focus/spatial navigation, remote keys
├── ts-config/                # P1  Shared eslint / tsconfig / prettier bases
├── php-shared-kernel/        # P1  Cross-context value objects: Money, Territory, TimeWindow,
│                             #     ContentId, DeviceClass. No business logic
├── kotlin-player-core/       # P8  Shared Media3 playback + DRM + telemetry for android/androidtv
└── swift-player-core/        # P8  Shared AVFoundation playback + FairPlay + telemetry

infrastructure/
├── docker/                   # P1  Dockerfiles + local development compose stack
├── terraform/                # P3  Cloud infrastructure as code (provider pending OQ-16)
├── kubernetes/               # P4  Manifests/Helm, if k8s is chosen (OQ-16)
├── nginx/                    # P4  Origin and edge configuration
├── database/                 # P1  Cluster config, roles, backup and PITR policy
└── observability/            # P1  Dashboards, alert rules, SLO definitions as code

docs/
├── architecture/  security/  streaming/  database/  api/  operations/
```

## 4. Module structure inside `core-api`

Each bounded context is a module with an enforced internal shape. The point is that a module's
public surface is small and explicit, so extraction to a separate service later is a build-file
change rather than an archaeology project.

```
services/core-api/modules/Rights/
├── Domain/            # Entities, value objects, domain events, invariants. No framework imports
├── Application/       # Use cases, commands, queries, handlers, transaction boundaries
├── Infrastructure/    # Eloquent models, repositories, external adapters, migrations
├── Http/              # Controllers, requests, resources, routes  (delivery mechanism only)
├── Contracts/         # ★ THE ONLY THING OTHER MODULES MAY IMPORT
│   ├── RightsQuery.php          # inbound port other contexts call
│   └── Events/                  # published domain events
└── Tests/
```

### The boundary rule

> A module may import from another module **only** through that module's `Contracts/` namespace.
> Nothing else. Not an Eloquent model, not a helper, not a constant.

This is enforced mechanically in CI, not by review discipline — a static analysis rule that fails
the build on violation (candidate tooling in [`09-dependency-policy.md`](09-dependency-policy.md);
selection is a Phase 1 task). Without mechanical enforcement, module boundaries decay within weeks.

Cross-module data access follows one of three patterns, in order of preference:

1. **Call the inbound port** (`RightsQuery::isAvailable(...)`) — synchronous, in-process, cheap now
   and replaceable with an HTTP/gRPC call later without touching the caller.
2. **Subscribe to a domain event** — for reactions and read-model updates.
3. **Read a purpose-built read model** — for expensive cross-context queries, maintained by
   projection from events (Discovery does this).

Never: reach into another module's tables.

## 5. Tooling

| Concern | Choice | Rationale |
|---|---|---|
| JS/TS workspaces | **pnpm workspaces** | Already installed (10.33.0); strict node_modules layout catches undeclared dependencies, which matters most in a monorepo |
| JS/TS task graph | **Turborepo** (candidate, MIT, v2.10.9 verified) | Adopt only when build times justify it — plain pnpm scripts until then. Do not install in Phase 1 without a measured need |
| PHP dependencies | **Composer 2** | Single `composer.json` per deployable service |
| Formatting | Prettier (TS), Laravel Pint (PHP), ktlint (Kotlin), swift-format (Swift) | |
| Static analysis | TypeScript strict; PHPStan/Larastan at the highest level the codebase can hold | Level is ratcheted upward, never downward |
| Repo scripts | `Makefile` at root delegating to workspace scripts | Make is present in every environment we target; one entry point per task |

**Rule:** every tool added must be justified in a PR description and recorded in
[`09-dependency-policy.md`](09-dependency-policy.md). No tool is added "because it is standard".

## 6. Ownership

`CODEOWNERS` will be introduced in Phase 1, mapping directories to teams. Until team composition is
known (**OQ-18**), ownership is not fabricated.

Directories that will require mandatory specialist review from the first commit:

- `services/core-api/modules/Rights/` and `.../Entitlement/` — rights or commercial owner
- `services/drm-license-proxy/` and `.../Protection/` — security owner
- `infrastructure/` — platform owner
- `packages/api-contracts/` — API owner; breaking-change detection in CI
