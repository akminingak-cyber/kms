# KMS TV

A production OTT/IPTV platform: Live TV, EPG, VOD, catch-up and restart, delivered to web, mobile and
Smart TV, with subscriptions, entitlements, rights management and DRM-ready streaming.

> ## Status: **Phase 0 — foundation**
>
> **There is no application code yet.** This repository currently contains the engineering
> foundation: the architecture, the boundaries, the strategies, and the rules that the build will
> follow. Phase 1 is blocked on the decisions listed in
> [`ARCHITECTURE.md` § Open questions](ARCHITECTURE.md#13-open-questions).

---

## Start here

| If you are… | Read |
|---|---|
| New to the project | [`ARCHITECTURE.md`](ARCHITECTURE.md) — the whole system in one document |
| About to write code | [`CLAUDE.md`](CLAUDE.md) — the engineering rules, and the definition of done |
| Looking for a specific area | [`docs/README.md`](docs/README.md) — the documentation map |
| Wondering what was here before | [`docs/architecture/00-inspection-report.md`](docs/architecture/00-inspection-report.md) |
| Wondering what the architecture got wrong | [`docs/architecture/10-architecture-review.md`](docs/architecture/10-architecture-review.md) |
| Deciding what to build next | [`docs/architecture/08-development-phases.md`](docs/architecture/08-development-phases.md) |

## Repository layout

```
kms/
├── apps/             # client applications          — empty; created per phase
├── services/         # backend services and workers — empty; core-api in Phase 1
├── packages/         # shared libraries             — empty; contracts in Phase 1
├── infrastructure/   # containers, IaC, edge config, observability-as-code
├── docs/
│   ├── architecture/ # context, bounded contexts, boundaries, rights, testing, phases, ADRs
│   ├── database/     # storage strategy, conceptual model, migrations, scaling
│   ├── api/          # surfaces, versioning, conventions, OpenAPI workflow
│   ├── security/     # threat model, identity, playback authorization, keys, privacy
│   ├── streaming/    # ingest, packaging, delivery, catch-up/nDVR, DRM, devices
│   └── operations/   # local dev, environments, CI/CD, observability, incidents
├── ARCHITECTURE.md
├── CLAUDE.md
└── README.md
```

The four top-level directories are intentionally empty apart from a README explaining what belongs
there. **No application is created before the phase that builds it.**

## Technology direction

| Layer | Direction |
|---|---|
| Backend | Laravel (major version pending [ADR-0007](docs/architecture/adr/ADR-0007-laravel-major-version.md)), PHP 8.3+, PostgreSQL, Redis, REST, OpenAPI 3.1 |
| Web | Next.js, React, TypeScript |
| Mobile | Android: Kotlin + AndroidX Media3 · iOS/iPadOS: Swift + AVFoundation |
| Smart TV | Android TV, Samsung Tizen, LG webOS |
| Streaming | FFmpeg, CMAF, HLS + DASH, own origin, CDN behind an abstraction |
| Infrastructure | Docker, Nginx, CI/CD; hosting pending **OQ-16** |

Versions and licences verified against package registries on 2026-08-10:
[`docs/architecture/09-dependency-policy.md`](docs/architecture/09-dependency-policy.md).

## Architecture in five points

1. **Four planes** — control, decision, media, insight — kept separable, because they differ in
   traffic, failure impact and cost by orders of magnitude.
2. **Seventeen bounded contexts** (16 of them modules in one modular monolith with **CI-enforced
   boundaries**, deployed as several runtime profiles). Services are extracted on evidence, not on
   principle.
3. **Rights, entitlements and DRM are three different things.** Rights are contractual; entitlements
   are commercial; DRM enforces only a subset of either.
4. **The client API is append-only within a major version.** Smart TVs reach their installed base over
   months to years; the contract must outlive the client.
5. **Playback authorization is the critical path.** It fails closed, records every decision, and
   depends on nothing that can be slow.

Detail: [`ARCHITECTURE.md`](ARCHITECTURE.md)

## Development environment

Verified in the container used to initialise this repository (2026-08-10):

**Available:** PHP 8.4.19 · Composer 2.8.12 · Node 22.22.2 · pnpm 10.33.0 · Git · Make · Docker CLI ·
psql and redis-cli clients

**Not available:** Docker **daemon** · PostgreSQL server · Redis server · FFmpeg · Nginx · PHP
`bcmath` · any mobile or TV toolchain. Network egress is restricted to package registries; vendor
documentation is blocked.

Consequences, in full:
[`docs/operations/local-development.md`](docs/operations/local-development.md).
The practical version: this environment suits backend, web, schema, contract and documentation work.
Containers, databases, media processing and device builds must be validated elsewhere.

## Verification policy

Two rules govern factual claims in this repository, and both are load-bearing:

- **Dependency versions and licences are read from registries, never from memory.** The verified
  inventory is in
  [`docs/architecture/09-dependency-policy.md`](docs/architecture/09-dependency-policy.md).
- **No third-party capability is assumed.** Vendor documentation is unreachable from this
  environment, so every claim about DRM systems, devices, CDNs or payment providers is marked
  **`[UNVERIFIED]`** and carries a checklist to be completed by a human before anything is built on
  it. See
  [`docs/architecture/05-integration-boundaries.md`](docs/architecture/05-integration-boundaries.md).

## Note on existing repository contents

The root of this repository also contains a Vite + React + TypeScript starter (`index.html`,
`package.json`, `vite.config.ts`, `tsconfig*.json`, `tailwind.config.js`, `postcss.config.js`,
`eslint.config.js`). It predates KMS TV, **does not build** (it references `/src/main.tsx`, which is
not in the repository), and declares a Supabase dependency that conflicts with the stated backend
direction.

It has been left untouched. What to do with it is **OQ-17**, and it should be resolved before Phase 1
begins — it determines whether `/` or `/apps/web` is the home of the web application.

## Contributing

Read [`CLAUDE.md`](CLAUDE.md) first. It applies to every contributor, human or AI, and covers the
non-negotiable rules: no mocks where a real implementation is required, no invented APIs or vendor
capabilities, no secrets in the repository, and the definition of done.
