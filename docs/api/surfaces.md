# API Surfaces

Four surfaces, separated because their audiences have different change rates, threat models and
support obligations. See [ADR-0004](../architecture/adr/ADR-0004-api-versioning.md).

| Surface | Base path | Callers | Exposed at edge | Support window |
|---|---|---|---|---|
| **Client** | `/api/client/v1` | Viewer applications: web, Android, Android TV, iOS/iPadOS, Tizen, webOS | Yes | ≥ 24 months after successor |
| **Admin** | `/api/admin/v1` | Admin Control Center | Yes, restricted | 3 months |
| **Partner** | `/api/partner/v1` | B2B integrations, operator partners | Yes, restricted | Contractual, ≥ 12 months |
| **Internal** | not routed publicly | Service-to-service | **No** | One deploy cycle |

---

## Client surface

The largest and most constrained surface.

### Shape

```
/api/client/v1
  /auth                 register, sign-in, refresh, sign-out, password reset
  /auth/device          device authorization (TV pairing) — RFC 8628 style
  /account              account details, plan, devices
  /profiles             CRUD, preferences, parental settings
  /devices              list, rename, remove
  /pages/{page}         server-driven layout: rails and their items
  /search               query, suggestions
  /titles/{id}          title detail, seasons, episodes, availability summary
  /channels             lineup, now/next
  /epg                  schedule by channel and time range
  /watchlist            add, remove, list
  /progress             resume points (bulk read, incremental write)
  /playback/sessions    ★ start a session — the authorization decision
  /playback/sessions/{id}/heartbeat
  /playback/sessions/{id}/stop
  /subscriptions        current plan, change, cancel
  /payments/methods     managed via provider-hosted flows only
  /config               client configuration, feature flags, minimum version
```

### Rules specific to this surface

- **Every request carries `X-KMS-Client` and `X-KMS-Client-Version`.** Without them, installed-version
  distribution cannot be measured and no version can ever be retired safely.
- **`/config` is fetched at app start.** It carries feature flags, minimum supported version, and
  endpoint capability hints. It is the mechanism by which a three-year-old TV app can be told to
  stop trying something, or to prompt for an upgrade.
- **Rights and entitlement filtering is server-side.** A client never receives an item it may not
  see and is never trusted to hide it.
- **`/pages/{page}` is server-driven layout.** Rails, ordering, titles and badges come from the
  server so merchandising changes ship without a client release.
- **Playback endpoints are served by the `playback-authorizer` profile**, not the general web tier —
  different scaling, different degradation behaviour ([`../architecture/04-service-boundaries.md`](../architecture/04-service-boundaries.md)).

## Admin surface

Serves the Control Center. Higher privilege, smaller audience, faster evolution.

```
/api/admin/v1
  /auth                 staff authentication, MFA (mandatory)
  /catalog              titles, series, images, translations, credits
  /schedule             channels, lineups, programmes, ingest status
  /media                assets, jobs, renditions, QC results
  /rights               agreements, rights, windows, blackouts, availability preview
  /products             packages, plans, prices, promotions, vouchers
  /customers            account lookup, subscription state, devices, payment history
  /support              act-on-behalf-of (fully audited), device reset, entitlement inspection
  /operations           channel health, ingest, pipeline, delivery status
  /reports              usage, licensor reporting, revenue
  /audit                the audit trail itself
```

Rules:

- **MFA mandatory.** No exceptions, including service accounts (which use different credentials
  entirely).
- **Every mutation is audited** with actor, before, after, and reason.
- **Sensitive mutations require 4-eyes approval** — rights changes and price changes at minimum.
- **Never routed on the same hostname or edge configuration as the client surface.** Separate
  hostname, separate network policy, separate rate limits, ideally IP-restricted.
- **Customer data access is minimised and logged.** Support sees what it needs; a support agent
  should not be able to enumerate the subscriber base.
- **`/rights/availability/preview`** lets a rights manager see the computed effect of a change
  *before* committing it. This is a correctness feature, not a convenience: rights mistakes are
  contract breaches.

## Partner surface

Not built until a partner exists. Reserved so it is not retrofitted onto the client surface — which
is the usual mistake, and which then binds partner obligations to the client surface's change rate.

Anticipated: content availability feeds, entitlement checks for operator bundles, usage reporting,
subscriber provisioning for wholesale deals.

## Internal surface

Service-to-service calls. **Never routed at the edge**, authenticated per
[`../architecture/04-service-boundaries.md`](../architecture/04-service-boundaries.md) §6.

Distinct from the others in that it carries no backwards-compatibility promise beyond a single
deploy cycle — internal callers are deployed together. The important rule is that internal endpoints
must never become a shortcut into the platform from outside: they are not exposed, not documented
publicly, and are network-isolated, not merely undocumented.

## Webhooks (inbound)

Not an API surface we define, but a contract we accept. Common rules for all providers:

- Dedicated path per provider, signature-verified before parsing
- Stored raw and immutable **before** interpretation
- Acknowledged immediately (2xx), processed asynchronously
- Idempotent by provider event id
- Replay-tolerant: providers redeliver, especially during incidents
- Never trusted as the sole source of state — reconciliation polling backs every critical webhook
