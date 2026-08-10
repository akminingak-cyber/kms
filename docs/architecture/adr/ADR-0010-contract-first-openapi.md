# ADR-0010: Contract-first OpenAPI as the source of truth

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture, API owner

## Context

Six client applications, built by different people at different times on different platforms, all
depend on one API. Smart TV clients cannot be updated quickly (ADR-0004), so an accidental breaking
change is not a bug to fix forward — it is a population of devices that stops working.

The question is whether the API contract is **written and reviewed deliberately**, or **derived from
whatever the implementation currently does**.

## Options considered

**A. Code-first generation** (e.g. `dedoc/scramble`, `zircote/swagger-php`). Low friction; the spec
always matches the code. But the spec is then a *description*, not a *contract*: a developer removing
a field regenerates a spec that documents the break. Nothing detects it, because there is no
independent statement of intent. Client work also cannot start until the server is written.

**B. Contract-first**: the OpenAPI document is authored and reviewed; server and clients are both
verified against it.

**C. Hand-written documentation.** Drifts within weeks. Not considered.

## Decision

**Option B.**

1. The OpenAPI 3.1 documents in `packages/api-contracts` are **the source of truth**. Changing the
   API means changing the spec first, in a reviewed PR.
2. **Breaking-change detection runs in CI** by diffing the spec against the previous release. A
   breaking change on an existing major version fails the build. This is the single most valuable
   check in the pipeline for a platform with slow-updating clients.
3. **The server is verified against the spec**, both directions: no undocumented endpoint or field,
   no documented behaviour missing.
4. **Client types and API clients are generated** from the spec (`openapi-typescript` / `orval` for
   TypeScript; equivalents for Kotlin and Swift). Generated code is never hand-edited.
5. **Spec style is linted** (Spectral) so naming, errors, pagination and versioning conventions are
   enforced mechanically rather than debated per PR.
6. The spec carries examples that are used as test fixtures, so examples cannot rot.
7. Internal service APIs may use lighter-weight contracts; this ADR governs `client`, `admin` and
   `partner` surfaces.

## Consequences

**Accepted costs**
- Higher friction per API change — deliberately. That friction is what protects the field.
- The spec can drift from the implementation if conformance testing is weak, so conformance testing
  is mandatory, not optional.
- Contributors must be comfortable authoring OpenAPI. Spec review is part of code review.

**Made easier**
- Client teams start against the spec before the server exists, with `msw`-based fakes generated from
  it.
- Breaking changes are caught in CI rather than by a viewer on a three-year-old television.
- The spec is a reviewable artifact for partners and for the platform's own API design discussions.

**Revisit when**
- Nothing foreseeable. If conformance testing proves impractical, the response is to fix conformance
  testing, not to invert the source of truth.
