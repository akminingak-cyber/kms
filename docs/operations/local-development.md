# Local Development

> **Nothing here is implemented yet.** This describes what Phase 1 builds and the constraints it must
> work within. The commands below do not exist until Phase 1 delivers them.

## 1. What this repository contains today

Documentation only, plus a pre-existing Vite/React starter unrelated to KMS TV
([`../architecture/00-inspection-report.md`](../architecture/00-inspection-report.md), findings R1–R3;
its fate is **OQ-17**). There is no application to run.

## 2. Constraints observed in the session container

Verified 2026-08-10 in the environment where this repository was initialised. These are recorded
because they determine where work can and cannot be validated:

| Constraint | Consequence |
|---|---|
| **No Docker daemon** (`/var/run/docker.sock` absent) | The compose stack cannot run here. It must be validated on a developer machine or a CI runner with a daemon |
| **PostgreSQL and Redis servers not preinstalled** | Installable from the distribution repositories, and that is how the integration suite is run here — against real PostgreSQL 16 and real Redis, never SQLite |
| **No FFmpeg** | No media processing or inspection here |
| **Nginx not preinstalled** | Installable from the distribution repositories, and that is how the origin configuration was verified — see the note below |
| **PHP `bcmath` not loaded** | The production PHP image must enable it explicitly |
| **Egress restricted to an allow-list** | Package registries reachable; **vendor documentation blocked**. Dependency metadata and licences are verifiable here; vendor capabilities are not |
| **No mobile or TV toolchains** | Client application work is impossible here. iOS additionally needs macOS |

**This environment is suitable for backend, web, schema, contract and documentation work.** Anything
requiring a container runtime, media processing, or a device toolchain must be validated elsewhere.
Plans that assume otherwise produce work nobody has actually run.

The distinction that matters is between *not preinstalled* and *not possible*. PostgreSQL, Redis and
Nginx are all installable here, and the work that depends on them is genuinely exercised rather than
asserted: the integration suite runs against real servers, and the origin configuration in
[`../../infrastructure/nginx/origin.conf`](../../infrastructure/nginx/origin.conf) has been run
against a live `core-api` — including the property the delivery cost model rests on, that two
viewers with different tokens hit the same cached segment.

FFmpeg and a container runtime are a different case. FFmpeg's licence follows its build flags, so
"install FFmpeg" is not a neutral act here (see
[`../architecture/09-dependency-policy.md`](../architecture/09-dependency-policy.md)); and there is
no Docker daemon to run a pinned image with. So the media pipeline is developed against a
specification and an argument vector, and confirmed where a cleared build exists.

## 3. Target developer experience (Phase 1)

One command, from a fresh clone, on a clean machine:

```bash
make up          # start the full local stack
make test        # run everything CI runs
make lint        # formatting and static analysis
make spec        # lint, diff and generate from the OpenAPI contract
make seed        # load the seed dataset
make down        # stop, preserving data
make reset       # stop and destroy all local data
```

`make up` must produce a working system with no manual steps. Every manual step in a setup guide is a
step someone will get wrong on their first day, and a step that silently rots as the system changes.

### Local stack

| Service | Purpose |
|---|---|
| PostgreSQL | Pinned to the **same major version as production** — planner and collation differences between majors cause bugs that reproduce nowhere else |
| Redis | Cache, queues, locks |
| PHP-FPM + Nginx | `core-api` |
| Queue worker + scheduler | Real async processing, not synchronous fakes |
| Mailpit (or equivalent) | Local mail capture, so verification and reset flows are exercised end to end |
| Object storage (S3-compatible, local) | From Phase 5 |

### Prerequisites

Docker with Compose, Make, Git. Everything else runs in containers, so a new contributor does not
need a matching PHP or Node installation on the host. Node and PHP on the host are convenient for
editor tooling and are not required to run the stack.

## 4. Seed data

`make seed` loads a fictional dataset maintained **as code** and treated as a first-class deliverable
([`../architecture/07-testing-strategy.md`](../architecture/07-testing-strategy.md) §5):

- A small catalog: films, a multi-season series, collections
- Channels with a lineup and a schedule spanning past, present and future
- **Rights configurations covering the interesting cases** — overlapping windows, territory
  exclusions, platform restrictions, a mid-event blackout, a relative catch-up window
- Products, plans and prices
- Accounts in every subscription state: trial, active, past-due, cancelled, expired
- Profiles including a child profile with a rating limit
- Devices of several classes

The rights configurations matter most. Most rights bugs are only visible with overlapping and
contradictory rules, and a seed dataset with one clean window per title will never surface them.

**Production data is never used**, in any form, for any reason
([`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md) §2).

## 5. Working in this repository

1. Branch from `main`.
2. Make the change, with tests.
3. `make lint && make test` locally before pushing.
4. Open a PR; CI runs the full gate set ([`ci-cd.md`](ci-cd.md)).
5. Review by a `CODEOWNERS` owner; mandatory security review for auth, rights, entitlement, payment
   or key changes.
6. Squash-merge to `main`.

Conventions, coding rules and the definition of done: [`../../CLAUDE.md`](../../CLAUDE.md).

## 6. Common problems (to be filled in as they occur)

This section is populated from real friction rather than anticipated friction. When a contributor
loses time to something, it goes here — a troubleshooting guide written in advance documents
problems nobody has, and misses the ones they will.
