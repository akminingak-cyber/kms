# KMS TV Documentation

Entry point: [`ARCHITECTURE.md`](../ARCHITECTURE.md) at the repository root.
Engineering rules for every contributor: [`CLAUDE.md`](../CLAUDE.md).

## Map

| Area | Start here | Covers |
|---|---|---|
| **Architecture** | [`architecture/`](architecture/) | System context, bounded contexts, repository and service boundaries, integrations, rights, testing, phases, dependencies, ADRs |
| **Database** | [`database/`](database/) | Storage topology, schema-per-context, conceptual model, migrations, scaling and retention |
| **API** | [`api/`](api/) | Surfaces, versioning, conventions, the OpenAPI workflow |
| **Security** | [`security/`](security/) | Trust zones, threat model, identity, playback authorization, secrets and keys, privacy |
| **Streaming** | [`streaming/`](streaming/) | Ingest, transcoding, packaging, delivery, catch-up/nDVR, DRM, players and devices |
| **Operations** | [`operations/`](operations/) | Local development, environments, CI/CD, observability, releases and incidents |

## If you are looking for…

| Question | Document |
|---|---|
| What was actually in this repository and environment at the start? | [`architecture/00-inspection-report.md`](architecture/00-inspection-report.md) |
| What are the parts of the system and who owns what? | [`architecture/02-bounded-contexts.md`](architecture/02-bounded-contexts.md) |
| Where does my code go? | [`architecture/03-repository-structure.md`](architecture/03-repository-structure.md) |
| Why is it a monolith? | [`architecture/adr/ADR-0001-modular-monolith-first.md`](architecture/adr/ADR-0001-modular-monolith-first.md) |
| How do rights differ from DRM? | [`architecture/06-rights-management.md`](architecture/06-rights-management.md) |
| What happens when someone presses play? | [`security/playback-authorization.md`](security/playback-authorization.md) |
| Why can't I just change this API field? | [`api/versioning.md`](api/versioning.md) |
| How do I add a dependency? | [`architecture/09-dependency-policy.md`](architecture/09-dependency-policy.md) |
| What are we building next? | [`architecture/08-development-phases.md`](architecture/08-development-phases.md) |
| What is still undecided? | [`../ARCHITECTURE.md`](../ARCHITECTURE.md) § Open questions |

## Conventions

- **`[UNVERIFIED]`** — a claim about third-party behaviour that could not be checked from the
  initialisation environment, because vendor documentation is blocked by the network egress policy
  ([`architecture/00-inspection-report.md`](architecture/00-inspection-report.md), E6). Confirm against
  vendor documentation before building on it.
- **`OQ-n`** — an open question, listed in [`../ARCHITECTURE.md`](../ARCHITECTURE.md).
- **`P-n`** — an integration port, defined in [`architecture/05-integration-boundaries.md`](architecture/05-integration-boundaries.md).
- **`P1`…`P10` in a phase context** — development phases, in [`architecture/08-development-phases.md`](architecture/08-development-phases.md).

## Status

**Phase 0 — foundation.** These documents describe a system that has not been built yet. They are
decisions and constraints, not descriptions of running code. Where something is undecided, it says so
rather than guessing.
