# ADR-0002: Monorepo with per-workspace tooling

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture

## Context

KMS TV will have six client applications, several backend services, and one API contract they all
depend on. The clients update at very different speeds — a web deploy is minutes, a Samsung TV app
update reaches users over months.

The dominant integration risk is **contract drift**: a client assuming API behaviour that changed.
The second risk is duplicated playback, DRM, and telemetry logic diverging between platforms.

## Options considered

**A. Polyrepo.** Natural per-platform ownership and independent tooling, but the API contract lives
in one repo and its consumers in others, so drift is detected late — often only in QA or production.
Shared player logic must be published as versioned packages, which adds latency to every fix.

**B. Monorepo, single toolchain.** Attractive on paper, impossible in practice: Gradle, Xcode,
Composer and pnpm cannot be unified.

**C. Monorepo with per-workspace native tooling and a thin repo-level orchestrator.** Each workspace
uses its native tools; the root provides one entry point per task and CI targets only what changed.

## Decision

**Option C.**

- Top level: `apps/`, `services/`, `packages/`, `infrastructure/`, `docs/`, `tools/`.
- pnpm workspaces for TypeScript; Composer per PHP service; Gradle for Android; SwiftPM/Xcode for
  Apple platforms.
- A root `Makefile` as the single entry point (`make up`, `make test`, `make lint`, `make spec`),
  delegating to each workspace.
- CI computes the affected workspace set from the diff and runs only those pipelines, plus a full
  nightly run.
- **No directory is created before the phase that needs it.**
- Turborepo is a candidate for task orchestration but is **not adopted in Phase 1** — plain pnpm
  scripts until build times justify the added tooling.

The iOS application may move to its own repository if code signing and macOS build constraints make
co-location impractical. That is deferred to Phase 8 (**OQ-15**) and does not change this decision
for everything else.

## Consequences

**Accepted costs**
- Large checkout; developers use sparse checkout.
- CI needs change-detection logic from the start, or every PR runs everything.
- Requires strict `CODEOWNERS` discipline to keep review load sane.

**Made easier**
- An API change and every client's adaptation land in one reviewable commit.
- Shared player logic is refactored across platforms atomically.
- One CI configuration, one dependency policy, one security baseline.

**Revisit when**
- CI wall-clock time on a typical PR exceeds ~15 minutes despite targeting, or
- Platform teams are consistently blocked by unrelated workspaces.
