# infrastructure/

Everything needed to run KMS TV: containers, infrastructure as code, edge configuration,
observability definitions.

**Nothing here yet.** `docker/`, `database/` and `observability/` are created in Phase 1.

## Planned

| Directory | Purpose | Phase |
|---|---|---|
| `docker/` | Dockerfiles and the local development compose stack | 1 |
| `database/` | Cluster configuration, roles and grants, backup and PITR policy | 1 |
| `observability/` | Dashboards, alert rules and SLO definitions **as code** | 1 |
| `terraform/` | Cloud infrastructure as code (provider pending **OQ-16**) | 3 |
| `kubernetes/` | Manifests or Helm charts, if Kubernetes is chosen (**OQ-16**) | 4 |
| `nginx/` | Origin and edge configuration | 4 |

## Rules

- **Infrastructure is code.** No manual console changes; drift between described and actual state is
  detected and reported.
- **Secrets never live here.** Configuration references secrets; the secret manager holds them
  ([`../docs/security/secrets-and-key-management.md`](../docs/security/secrets-and-key-management.md)).
- **Environments differ in configuration, never in the artifact.** One build, promoted
  ([`../docs/operations/environments.md`](../docs/operations/environments.md)).
- **Database roles are scoped per deployment profile.** `playback-authorizer` cannot write to
  `billing` and cannot read `protection` at all
  ([ADR-0003](../docs/architecture/adr/ADR-0003-postgresql-system-of-record.md)).
- **The protected zone has no inbound internet path.** The DRM licence proxy and the key vault sit
  behind it ([`../docs/security/README.md`](../docs/security/README.md) §2).
- Alert rules and dashboards live here so they are reviewed and versioned, not clicked together in a
  console and lost.

## Environment note

The session container that initialised this repository has **no Docker daemon, no PostgreSQL server,
no Redis server, no FFmpeg and no Nginx**
([`../docs/architecture/00-inspection-report.md`](../docs/architecture/00-inspection-report.md), E1–E4).
The compose stack must therefore be validated on a developer machine or a CI runner that provides a
container runtime.
