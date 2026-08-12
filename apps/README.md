# apps/

Deployable end-user applications — one directory per client platform.

No application directory is created before the phase that builds it; empty scaffolding invites
premature dependencies and dead configuration.

## Built

| Directory | Stack | Phase |
|---|---|---|
| [`admin/`](admin/) | React, TypeScript, Vite — a static SPA over the admin API ([ADR-0013](../docs/architecture/adr/ADR-0013-admin-panel-stack.md)) | 4 |

## Planned

| Directory | Stack | Phase |
|---|---|---|
| `web/` | Next.js, React, TypeScript — **not** bound by ADR-0013; server rendering earns its keep for a viewer-facing app | 5 |
| `android/` | Kotlin, AndroidX Media3 | 8 |
| `androidtv/` | Kotlin, Media3, Leanback | 9 |
| `ios/` | Swift, AVFoundation — iOS + iPadOS (may move to its own repository, OQ-15) | 8 |
| `tizen/` | Samsung Tizen web application | 9 |
| `webos/` | LG webOS web application | 9 |

## Rules

- An app is a **delivery mechanism**. Business rules live in `services/`, shared logic in `packages/`.
- Every client calls the API through the **generated** client in `packages/ts-api-client` (or its
  Kotlin/Swift equivalent). No hand-written API calls, ever — they drift from the contract silently.
- Every client is a **tolerant reader** and passes the conformance suite in
  [`../docs/streaming/player-and-device-matrix.md`](../docs/streaming/player-and-device-matrix.md) §6.
- Playback logic belongs in the shared `*-player-core` package, not in the app.
- No client-side security decisions. Rights, entitlement and parental checks are server-side; the app
  renders the outcome.

See [`../docs/architecture/03-repository-structure.md`](../docs/architecture/03-repository-structure.md).
