# ADR-0004: Versioned API surfaces with append-only evolution

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture, API owner

## Context

Four categories of consumer will call KMS TV APIs, and their upgrade characteristics differ by
orders of magnitude:

| Consumer | Time for ~all installs to reach a new version |
|---|---|
| Web application | Minutes (we control the deploy) |
| Admin Control Center | Minutes |
| Mobile applications | Weeks to months; some users never update |
| **Smart TV applications** | **Months to years.** Platform update policies vary and some devices effectively stop receiving updates |

A single global API version forces the slowest consumer's constraints onto the fastest. Internal
service calls have different needs again.

## Options considered

**A. One version for everything (`/api/v1`).** Simple, and it means the admin API cannot evolve
faster than the oldest TV app in the field.

**B. Per-endpoint versioning / header negotiation.** Maximum flexibility; in practice, a combinatorial
support matrix nobody can reason about, and cache keys that depend on headers.

**C. Versioned surfaces**: separate base paths per audience, each with its own major version and its
own support policy.

## Decision

**Option C.**

```
/api/client/v1/…     viewer applications  (web, mobile, TV)
/api/admin/v1/…      Admin Control Center
/api/partner/v1/…    partners and B2B integrations   (when needed)
internal (not exposed at the edge)  service-to-service
```

Rules:

1. **Within a major version, changes are additive only.** New optional fields, new endpoints, new
   enum values in fields documented as extensible. Never: removing a field, narrowing a type,
   changing a default, changing semantics, adding a required request field.
2. **Clients must be tolerant readers.** Unknown fields ignored, unknown enum values handled by a
   documented fallback. Verified by a client conformance test, not by hope.
3. **A breaking change means a new major version served in parallel.** Both versions run
   simultaneously for the whole deprecation window.
4. **Support policy per surface:**
   - `client` — a major version is supported for **at least 24 months** after its successor ships,
     and cannot be retired while a supported TV platform still has meaningful installed usage.
   - `admin` — 3 months. We control the client.
   - `partner` — contractual, minimum 12 months.
5. Deprecation is signalled with `Deprecation` and `Sunset` (RFC 8594) response headers, plus a
   changelog entry in `packages/api-contracts`.
6. **Clients send `X-KMS-Client` and `X-KMS-Client-Version` on every request.** Without this we
   cannot measure installed-version distribution, and without that measurement no version can ever
   be retired safely. The server can also signal `upgrade_required` for a genuinely unsupported
   client.
7. The internal API is versioned independently, is never routed at the edge, and carries no
   backwards-compatibility promise beyond one deploy cycle.

## Consequences

**Accepted costs**
- Multiple client majors run concurrently, with the branching and test burden that implies.
- Some duplication across surfaces for similar resources — accepted, because the alternative is
  coupling audiences with incompatible change rates.

**Made easier**
- Admin features ship without waiting on TV apps.
- A TV app from three years ago keeps working, which is a product requirement, not a courtesy.
- Version usage is measurable, so retirement becomes a data-driven decision.

**Revisit when**
- Client version telemetry shows the 24-month window is materially wrong in either direction.
