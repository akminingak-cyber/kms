# ADR-0001: Modular monolith first, extraction by evidence

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture

## Context

KMS TV has 16 bounded contexts ([`../02-bounded-contexts.md`](../02-bounded-contexts.md)). Their
boundaries are, at this point, **hypotheses derived from domain analysis, not from operating
experience with this product**. Several of them — Entitlements vs Subscriptions, Discovery vs
Catalog, Playback vs Protection — have plausible alternative divisions.

Team size is unknown (OQ-18). There is no platform engineering capability in place: no cluster, no
service mesh, no distributed tracing, no on-call rotation.

At the same time, the contexts genuinely differ in operational profile: playback authorization is
latency-critical and high-RPS, the media pipeline needs FFmpeg and large disks, DRM key handling
needs credential isolation, telemetry is write-dominated.

## Options considered

**A. Microservices from the start (one service per context).**
Matches the eventual target for some contexts. But it fixes boundaries before they are validated,
and every boundary correction becomes a cross-repository migration with data movement. It forces
distributed transactions across billing and entitlements while that model is still changing weekly.
It requires platform maturity that does not exist. The cost is paid immediately; the benefit arrives
much later, if the boundaries were right.

**B. Single monolith with no internal boundaries.**
Fastest initially, and reliably fatal. Without enforced boundaries, contexts grow into each other
within weeks; rights checks end up inline in controllers; the playback path acquires a dependency on
the admin schema. Extraction later becomes archaeology.

**C. Modular monolith with mechanically enforced boundaries, deployed as multiple runtime profiles,
plus separate services only where the reason is technical and specific.**
Keeps change cheap while the model is unstable, gives operational isolation immediately through
deployment profiles rather than codebases, and makes later extraction a build-configuration change
because the boundaries were real all along.

## Decision

**Option C.**

1. One Laravel codebase, `services/core-api`, holding all control-plane contexts as modules.
2. Module boundaries enforced **by CI**, not by convention: a module may import only from another
   module's `Contracts/` namespace. A violation fails the build.
3. Deployed as **several runtime profiles of the same artifact** — web, admin, workers, and (from
   Phase 4) playback-authorizer — with separate scaling, separate database credentials, separate
   rate limits.
4. Genuinely separate services only where the reason is technical: `drm-license-proxy` (credential
   isolation), `media-pipeline` (FFmpeg runtime), `epg-ingest` (failure isolation from bad feeds),
   `telemetry-collector` (write volume).
5. Extraction of any further module requires meeting one of the criteria in
   [`../04-service-boundaries.md`](../04-service-boundaries.md) §5, **with evidence**.
6. Inter-module communication uses in-process ports and a transactional outbox for events, so the
   call sites do not change when a module later moves behind the network.

## Consequences

**Accepted costs**
- One deployment artifact means a bad change can affect several contexts at once. Mitigated by
  runtime profiles, canary deploys, and feature flags.
- Everything is PHP, so a context needing a different runtime must be extracted (as media-pipeline
  and telemetry-collector already are).
- Boundary enforcement tooling must be built in Phase 1 — a real cost, paid once. **If it is
  skipped, this ADR is void**, because option C without enforcement is option B.

**Made easier**
- Cross-context refactoring stays a single atomic commit while the model is still moving.
- Strong consistency where it matters (billing ↔ entitlements) without distributed transactions.
- Local development is one stack, which keeps the feedback loop short.

**Revisit when**
- A module's scaling profile provably wastes capacity when co-located, or
- Release cadence conflict is measurably blocking delivery, or
- A context needs credentials or a network zone the monolith must not have.
