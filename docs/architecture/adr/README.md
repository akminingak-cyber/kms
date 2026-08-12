# Architecture Decision Records

An ADR records **one decision**: the context that forced it, the options considered, what was
decided, and the consequences we accept. ADRs are immutable once accepted. A decision that changes
gets a new ADR that supersedes the old one; the old one stays, because the reasoning that was valid
at the time is what makes the change comprehensible later.

## Status values

| Status | Meaning |
|---|---|
| **Proposed** | Written, not yet agreed. **Must not be built on.** |
| **Accepted** | Agreed and in force |
| **Superseded by ADR-NNNN** | Replaced; retained for history |
| **Deprecated** | No longer applies, not replaced |

## Index

| ADR | Title | Status |
|---|---|---|
| [0001](ADR-0001-modular-monolith-first.md) | Modular monolith first, extraction by evidence | Accepted |
| [0002](ADR-0002-monorepo-layout-and-tooling.md) | Monorepo with per-workspace tooling | Accepted |
| [0003](ADR-0003-postgresql-system-of-record.md) | PostgreSQL as system of record, schema per context | Accepted |
| [0004](ADR-0004-api-versioning.md) | Versioned API surfaces with append-only evolution | Accepted |
| [0005](ADR-0005-cmaf-multi-drm.md) | CMAF with `cbcs` as the single-encode multi-DRM target | Proposed |
| [0006](ADR-0006-playback-authorization-tokens.md) | Two-token playback authorization | Accepted |
| [0007](ADR-0007-laravel-major-version.md) | Laravel major version for `core-api` | **Proposed — needs owner decision** |
| [0008](ADR-0008-cdn-and-origin-abstraction.md) | CDN and origin behind a delivery port | Accepted |
| [0009](ADR-0009-payments-boundary.md) | PSP-agnostic payments, no card data on our infrastructure | Accepted |
| [0010](ADR-0010-contract-first-openapi.md) | Contract-first OpenAPI as the source of truth | Accepted |
| [0011](ADR-0011-availability-projection-shape.md) | Factorised availability projection | Accepted |
| [0012](ADR-0012-manifest-generation-and-quality-classes.md) | Control-plane manifest generation, per quality class | Accepted |

## Template

```markdown
# ADR-NNNN: <title>

**Status:** Proposed | Accepted | Superseded by ADR-NNNN
**Date:** YYYY-MM-DD
**Deciders:** <roles>

## Context
What forces this decision? What is true that makes it necessary now?

## Options considered
Each with its real trade-offs, including the option of doing nothing.

## Decision
What we will do, stated unambiguously.

## Consequences
What this makes easy, what it makes hard, what we accept, and what would make us revisit it.
```
