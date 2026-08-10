# Architecture Documentation

Start with [`ARCHITECTURE.md`](../../ARCHITECTURE.md) at the repository root — it is the overview
and the entry point. The documents here are the detail behind it.

## Reading order

| # | Document | What it answers |
|---|---|---|
| 00 | [Inspection report](00-inspection-report.md) | What was actually in the repository and the environment on 2026-08-10, and what that constrains |
| 01 | [System context and scope](01-system-context.md) | What KMS TV is, who uses it, the four planes, quality attributes |
| 02 | [Bounded contexts](02-bounded-contexts.md) | The 16 contexts, what each owns, and the context map |
| 03 | [Repository structure](03-repository-structure.md) | Monorepo layout, module structure, boundary enforcement, tooling |
| 04 | [Service boundaries](04-service-boundaries.md) | Deployment units, sync vs async, the playback hot path, extraction criteria |
| 05 | [Integration boundaries](05-integration-boundaries.md) | The 13 third-party ports and their verification checklists |
| 06 | [Rights management](06-rights-management.md) | Rights vs DRM vs entitlements; the rights model; auditability |
| 07 | [Testing strategy](07-testing-strategy.md) | Layers, what "no mocks" means here, high-risk areas, definition of done |
| 08 | [Development phases](08-development-phases.md) | Phases 0–10 with exit criteria |
| 09 | [Dependency policy](09-dependency-policy.md) | Policy, and the versions/licences verified on 2026-08-10 |
| — | [Decision records](adr/) | The decisions themselves, with their reasoning |

## Domain documents

| Area | Location |
|---|---|
| Database | [`../database/`](../database/) |
| API | [`../api/`](../api/) |
| Security | [`../security/`](../security/) |
| Streaming | [`../streaming/`](../streaming/) |
| Operations | [`../operations/`](../operations/) |

## Conventions used in these documents

- **`[UNVERIFIED]`** marks a claim about third-party behaviour that could not be checked from this
  environment (vendor documentation is blocked — see finding E6 in the inspection report). Such
  claims must be confirmed by a human against vendor documentation before anything is built on them.
- **`OQ-n`** is an open question listed in [`ARCHITECTURE.md`](../../ARCHITECTURE.md) §Open questions.
- **`P-n`** is an integration port defined in [`05-integration-boundaries.md`](05-integration-boundaries.md).
- **Phase references** (`P1`, `P4`, …) point at [`08-development-phases.md`](08-development-phases.md).

## Keeping these current

These documents describe decisions, not aspirations. When a decision changes:

1. Write a new ADR that supersedes the old one — never edit an accepted ADR's decision.
2. Update the documents that referenced it.
3. Note the change in the PR that implements it.

A document that no longer matches the system is worse than no document, because it will be believed.
