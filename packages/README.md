# packages/

Shared libraries. Never deployed on their own — always consumed by something in `apps/` or
`services/`.

**Nothing here yet.** `api-contracts`, `ts-api-client`, `ts-config` and `php-shared-kernel` are
created in Phase 1.

## Planned

| Directory | Purpose | Phase |
|---|---|---|
| `api-contracts/` | **OpenAPI specs — the source of truth for every client API** | 1 |
| `ts-api-client/` | Generated TypeScript client and types (generated, never hand-edited) | 1 |
| `ts-config/` | Shared ESLint / TypeScript / Prettier bases | 1 |
| `php-shared-kernel/` | Cross-context value objects: `Money`, `Territory`, `TimeWindow`, `ContentId`, `DeviceClass`. **No business logic** | 1 |
| `ts-player-core/` | Session lifecycle, delivery-target fallback, DRM setup, error taxonomy, QoE telemetry — web, Tizen, webOS | 5 |
| `ts-ui-tv/` | TV UI primitives: focus and spatial navigation, remote key handling | 9 |
| `kotlin-player-core/` | The same player specification for Android and Android TV | 8 |
| `swift-player-core/` | The same player specification for iOS/iPadOS | 8 |

## Rules

- A package exists because **two or more** consumers need it. One consumer means it belongs in that
  consumer.
- **`api-contracts` is the contract**, not documentation of the implementation
  ([ADR-0010](../docs/architecture/adr/ADR-0010-contract-first-openapi.md)). Change the spec first.
- Generated code is committed so diffs are reviewable, and is **never hand-edited** — CI regenerates
  and fails on any difference.
- The three `*-player-core` packages implement **one specification in three languages**. They must
  agree on session lifecycle, error taxonomy and telemetry, or QoE data is not comparable across
  platforms and every playback investigation becomes six investigations.
- `php-shared-kernel` holds value objects only. Business logic in a shared kernel becomes a hidden
  dependency between every context that uses it.

See [`../docs/architecture/03-repository-structure.md`](../docs/architecture/03-repository-structure.md).
