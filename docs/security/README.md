# Security Architecture

## Contents

| Document | Covers |
|---|---|
| This document | Security model, trust zones, principles, controls by layer |
| [`threat-model.md`](threat-model.md) | Assets, adversaries, threats, and what we do about them |
| [`identity-and-access.md`](identity-and-access.md) | Authentication, tokens, devices, staff access |
| [`playback-authorization.md`](playback-authorization.md) | The critical path: the decision, the tokens, degradation |
| [`secrets-and-key-management.md`](secrets-and-key-management.md) | Secrets, content keys, rotation, separation of duties |
| [`privacy-and-compliance.md`](privacy-and-compliance.md) | Personal data, retention, erasure, PCI, age ratings |

DRM specifics live in [`../streaming/drm.md`](../streaming/drm.md); rights (the contractual layer DRM
partially enforces) in [`../architecture/06-rights-management.md`](../architecture/06-rights-management.md).

---

## 1. What we are protecting, and from whom

| Asset | Primary threat | Consequence of loss |
|---|---|---|
| **Content keys** | Extraction → mass decryption of the library | Catastrophic. Loss of content deals, contractual penalties |
| **Premium content streams** | Restreaming, ripping | Revenue loss, licensor sanction |
| **Subscriber personal data** | Breach, insider access | Regulatory penalty, reputational damage, legal exposure |
| **Payment credentials** | Interception | Kept out of scope by design — we never hold PAN ([ADR-0009](../architecture/adr/ADR-0009-payments-boundary.md)) |
| **Accounts** | Credential stuffing, sharing | Revenue leakage, support cost |
| **Entitlement integrity** | Manipulation → free access | Direct revenue loss |
| **Rights compliance** | Misconfiguration → unauthorized territory/window | Contract breach; commercially the most serious non-key risk |
| **Platform availability** | DoS, resource exhaustion | Every viewer affected simultaneously |
| **Admin access** | Takeover | Total compromise: rights, pricing, subscriber data |

The two at the top are what make OTT security different from ordinary SaaS security. A typical SaaS
breach exposes customer data. An OTT key compromise additionally destroys commercial relationships
with content owners, and those relationships are the business.

## 2. Trust zones

```
┌────────────────────────────────────────────────────────────────────┐
│ UNTRUSTED — client devices, browsers, TVs, the public internet     │
│ Assume every client is hostile and every device is rooted.         │
│ Nothing on a client is a security control. Client-side checks are  │
│ UX, never enforcement.                                             │
└──────────────────────────┬─────────────────────────────────────────┘
                           │  TLS 1.3, WAF, rate limits, bot controls
┌──────────────────────────▼─────────────────────────────────────────┐
│ EDGE — CDN, load balancers, API gateway                            │
│ Terminates TLS, enforces coarse controls. Trusted for delivery,    │
│ never for authorization.                                           │
└──────────────────────────┬─────────────────────────────────────────┘
                           │  authenticated, least-privilege
┌──────────────────────────▼─────────────────────────────────────────┐
│ APPLICATION — core-api, playback-authorizer, workers               │
│ Enforces all authentication, authorization, rights and entitlement │
│ decisions. Schema-scoped database roles.                           │
└──────────────────────────┬─────────────────────────────────────────┘
                           │  narrow, explicitly allowed paths only
┌──────────────────────────▼─────────────────────────────────────────┐
│ PROTECTED — drm-license-proxy, key vault, database primaries       │
│ No inbound internet. The licence proxy is the ONLY workload that   │
│ can read content keys. Separate credentials, separate network      │
│ zone, separate audit stream.                                       │
└────────────────────────────────────────────────────────────────────┘
```

The zone boundary that matters most is the last one. If compromising `core-api` yields content keys,
every other control is decoration — which is why `drm-license-proxy` is a separate service from
Phase 6 rather than a module ([ADR-0001](../architecture/adr/ADR-0001-modular-monolith-first.md)).

## 3. Principles

1. **Assume breach.** Design so that compromising one component does not yield the next. Segment
   credentials, segment networks, segment data access.
2. **The client is not a security boundary.** Every check that matters happens server-side.
   Client-side rating checks, hidden UI, and disabled buttons are UX.
3. **Default deny.** Absence of a right, an entitlement, or a permission is a prohibition.
   ([`../architecture/06-rights-management.md`](../architecture/06-rights-management.md))
4. **Least privilege, everywhere** — database roles, service credentials, staff permissions, vendor
   API scopes.
5. **Defence in depth for anything revenue-bearing.** Geo is enforced at authorization *and* at the
   edge. Concurrency is enforced at authorization *and* in the licence policy.
6. **Everything security-relevant is logged, and the logs are tamper-evident.** An attack you cannot
   reconstruct is an attack you cannot bound.
7. **Fail closed on the decision path.** If rights cannot be evaluated, deny with a specific error.
   Never "allow because the check was unavailable".
8. **No secret in the repository. Ever.** Enforced by CI secret scanning and by pre-commit hooks.

## 4. Controls by layer

| Layer | Controls |
|---|---|
| **Transport** | TLS 1.3 everywhere including internal calls; HSTS; modern cipher suites only; certificate lifecycle automated and monitored |
| **Edge** | WAF; per-account/device/IP rate limits; bot detection on auth endpoints; DDoS protection; geo controls as defence in depth |
| **Authentication** | Argon2id password hashing; MFA for staff (mandatory) and optional for viewers; rotating refresh tokens with reuse detection; device binding |
| **Authorization** | Server-side always; RBAC for staff; policy evaluation for playback; 4-eyes on rights and pricing |
| **Application** | Input validation at the boundary; parameterised queries only; output encoding; CSRF protection on cookie-authenticated flows; strict CSP on web apps |
| **Data** | Encryption at rest; schema-scoped database roles; field-level protection for sensitive attributes; **no production data outside production** |
| **Keys** | Vault/HSM-backed; the licence proxy is the sole reader; rotation; access audited |
| **Content** | DRM with rights-derived policy; short-lived, scoped tokens; concurrency enforcement; watermarking under consideration (Phase 10) |
| **Supply chain** | Lockfiles committed; dependency audit in CI; SBOM per release; reviewed updates on payment, playback and key paths |
| **Operations** | No standing production access; audited break-glass; immutable infrastructure; signed artifacts |
| **Monitoring** | Security events to a separate, append-only stream; alerting on anomalies described in [`threat-model.md`](threat-model.md) |

## 5. Security in the development process

- **Threat modelling** for every new context or externally exposed surface, not once at the start.
- **Security review is mandatory** for changes to authentication, authorization, rights, entitlement,
  payment, or key handling — enforced via `CODEOWNERS`.
- **Automated in CI:** SAST, dependency vulnerability audit, secret scanning, IaC scanning.
- **Before launch:** external penetration test; findings resolved or formally accepted with a named
  owner and a date.
- **Continuously:** dependency alerts triaged within one business day for anything on the playback,
  payment or key paths.

## 6. What we deliberately do not rely on

Stating these explicitly, because each is a common and false assumption:

- **CDN geo-blocking as the territory control.** It is defence in depth. Territory is decided at
  authorization and recorded with the decision.
- **DRM as rights compliance.** DRM has no concept of a licence window or a territory.
- **Obscurity of internal endpoints.** Internal APIs are network-isolated, not merely undocumented.
- **Client-side parental controls.** Enforced server-side; the client only renders the outcome.
- **A single vendor's availability.** Multi-CDN readiness ([ADR-0008](../architecture/adr/ADR-0008-cdn-and-origin-abstraction.md)) is partly a security-of-availability decision.
