# Environments

## 1. Definitions

| Environment | Purpose | Data | External systems | Who deploys |
|---|---|---|---|---|
| **local** | Development | Seed | Fakes; sandboxes on demand | Developer |
| **ci** | Automated verification | Seed, ephemeral | **Fakes only** (vendor sandboxes nightly) | Pipeline |
| **preview** | Per-PR review — **from Phase 2**, when there is a UI to review | Seed, ephemeral | Fakes | Pipeline, per PR |
| **staging** | Pre-production verification | Seed + synthetic scale | **Real sandboxes**, real CDN, real DRM test servers | Pipeline, on merge |
| **production** | Live | Real | Real | Pipeline, gated |

## 2. Rules

1. **No production data outside production.** Not anonymised, not partial, not "just for one
   debugging session" ([`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md)).
2. **Staging uses real vendor sandboxes**, not our fakes. Fakes verify our code; sandboxes verify our
   understanding of the vendor. Both are needed, and they catch different things.
3. **Same artifact everywhere.** One build, promoted through environments. Configuration differs;
   the binary does not. Rebuilding per environment means the thing tested is not the thing deployed.
4. **Configuration is injected, never baked in.** No environment-conditional code paths — an
   `if (env === 'production')` branch is a code path that is never tested before it matters.
5. **Staging mirrors production topology**, at reduced scale: same deployment profiles, same network
   segmentation, same database roles. A staging environment shaped differently from production
   verifies a system that does not exist.
6. **Preview environments are ephemeral** and destroyed with the PR. A long-lived preview becomes an
   unmanaged environment holding real credentials.

## 3. Production topology

From Phase 4, when the decision plane separates:

```
                    ┌─────────── edge ───────────┐
                    │  WAF · rate limits · TLS   │
                    └──┬──────────┬──────────┬───┘
                       │          │          │
          ┌────────────▼──┐  ┌────▼──────┐  ┌▼──────────────┐
          │ core-api      │  │ core-api  │  │ playback-     │
          │ (web tier)    │  │ (admin)   │  │ authorizer    │
          └───────┬───────┘  └─────┬─────┘  └───────┬───────┘
                  │                │                │
                  └────────┬───────┴────────────────┘
                           │  schema-scoped roles
                  ┌────────▼────────┐    ┌──────────────┐
                  │ PostgreSQL      │    │ Redis        │
                  │ primary+replicas│    │              │
                  └─────────────────┘    └──────────────┘

    ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐
    │ core-api     │  │ epg-ingest   │  │ media-pipeline       │
    │ (workers)    │  │              │  │ (FFmpeg, large disk) │
    └──────────────┘  └──────────────┘  └──────────────────────┘

    ══════════ protected zone, no inbound internet ══════════
    ┌──────────────────────┐   ┌──────────────┐
    │ drm-license-proxy    │──►│ key vault    │
    │ (sole key reader)    │   │ / HSM        │
    └──────────────────────┘   └──────────────┘

    ┌──────────┐   ┌──────────┐   ┌──────────────┐
    │ origin   │──►│ CDN(s)   │──►│ viewers      │
    │ + shield │   │          │   │              │
    └──────────┘   └──────────┘   └──────────────┘
```

Admin is on a **separate hostname with its own edge configuration and network policy**, ideally
IP-restricted ([`../api/surfaces.md`](../api/surfaces.md)). The protected zone has no inbound internet
path at all.

## 4. Configuration and secrets

- Configuration by environment variable, validated **at startup**. A service with missing or invalid
  configuration fails immediately and loudly rather than failing later in a confusing way — usually
  under load, usually at night.
- Secrets from the secret manager, never in images or repositories
  ([`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md)).
- `.env.example` documents every required variable with placeholder values and is committed. Real
  `.env` files are git-ignored.
- **Configuration drift is detectable**: environments are described as code, and differences between
  described and actual state are reported.

## 5. Hosting

**Undecided — OQ-16.** Cloud provider, region strategy, and whether orchestration is Kubernetes or
something simpler are Phase 3/4 decisions, taken with the data-residency constraints that follow from
launch markets (**OQ-1**) and vendor data-location requirements
([`../security/privacy-and-compliance.md`](../security/privacy-and-compliance.md) §7).

Recorded as an ADR when taken. Until then, `infrastructure/` holds only the local Docker stack, and
nothing in the application depends on a specific provider — every cloud service sits behind a port
([`../architecture/05-integration-boundaries.md`](../architecture/05-integration-boundaries.md)).

## 6. Data residency

Deferred until markets are known, but flagged now because it can constrain hosting, CDN, analytics
and payment vendor choices simultaneously — and it is discovered late far more often than it is
planned for. It is part of every vendor verification checklist.
