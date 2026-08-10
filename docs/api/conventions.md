# API Conventions

Enforced by Spectral rules in CI where mechanically checkable
([`openapi-workflow.md`](openapi-workflow.md)), by review otherwise.

## 1. Resources and methods

- Plural nouns: `/titles`, `/channels`, `/profiles`.
- Nesting only where the child cannot exist without the parent: `/series/{id}/seasons`.
- Verbs only for genuine operations that are not resource state:
  `/playback/sessions/{id}/heartbeat`, `/subscriptions/{id}/cancel`.
- `GET` never mutates. `PUT` and `DELETE` are idempotent. `POST` creates or acts.
- `PATCH` uses a documented merge semantic; no ambiguity about null vs absent.

## 2. Identifiers and naming

- Public identifiers are **UUIDv7**. Internal integer keys are never exposed.
- JSON fields are `snake_case`. Consistency matters more than the choice; this one matches the
  backend and avoids per-layer transformation bugs.
- Timestamps are RFC 3339 in UTC with an explicit offset: `2026-08-10T14:30:00Z`.
- Durations are integer seconds with a `_seconds` suffix. No ISO 8601 durations — too many client
  platforms parse them badly.
- Money is an object, never a bare number:
  ```json
  { "amount_minor": 999, "currency": "EUR" }
  ```
  A bare `9.99` invites float arithmetic on the client and rounding disputes at the till.
- Languages are BCP 47 (`en-GB`, `pt-BR`), territories ISO 3166-1 alpha-2.

## 3. Errors

**RFC 9457 Problem Details**, extended with a stable machine-readable code:

```http
HTTP/1.1 403 Forbidden
Content-Type: application/problem+json
```
```json
{
  "type": "https://api.kmstv.example/problems/playback-not-entitled",
  "title": "Not entitled to this content",
  "status": 403,
  "detail": "This title is not included in your current package.",
  "instance": "/api/client/v1/playback/sessions",
  "code": "PLAYBACK_NOT_ENTITLED",
  "correlation_id": "0199c1a4-7e3b-7c21-9f4d-2b6a8e05d913",
  "meta": { "required_package_ids": ["0199c1a4-7e3b-7c21-9f4d-2b6a8e05d914"] }
}
```

Rules:

- **`code` is stable forever.** Clients switch on `code`, never on `detail` or `title`.
- **`detail` is human-readable but not user-facing.** Clients localise from `code`; a server-supplied
  English sentence rendered on a Portuguese television is a bug.
- **`correlation_id` is always present**, so a viewer's screenshot is enough to find the request.
- Validation errors list every failure, not the first:
  ```json
  { "code": "VALIDATION_FAILED",
    "errors": [ { "field": "email", "code": "INVALID_FORMAT" } ] }
  ```

### Playback denial codes must be specific

This is the highest-value application of the rule. Every denial reason gets its own code:

| Code | Meaning |
|---|---|
| `PLAYBACK_NOT_ENTITLED` | Not in the customer's package |
| `PLAYBACK_NOT_AVAILABLE_IN_TERRITORY` | Rights exclude this territory |
| `PLAYBACK_OUTSIDE_LICENCE_WINDOW` | Rights window not open |
| `PLAYBACK_BLACKED_OUT` | Active blackout |
| `PLAYBACK_PLATFORM_NOT_PERMITTED` | Rights exclude this device class |
| `PLAYBACK_CONCURRENCY_EXCEEDED` | Too many simultaneous streams |
| `PLAYBACK_DEVICE_LIMIT_EXCEEDED` | Too many registered devices |
| `PLAYBACK_PARENTAL_BLOCKED` | Above the profile's rating limit |
| `PLAYBACK_DEVICE_NOT_SUPPORTED` | Device cannot satisfy required protection |
| `PLAYBACK_CONTENT_UNAVAILABLE` | Media not ready |

A single generic "not available" here would mean support cannot help, product cannot measure, and a
rights misconfiguration is indistinguishable from a billing failure. The specificity of these codes
is a deliberate architectural choice, and it is why the decision engine records *which* check failed
([ADR-0006](../architecture/adr/ADR-0006-playback-authorization-tokens.md)).

**One caveat:** codes are specific but never leak information the caller should not have. "Not
available in your territory" is safe; an error revealing another account's state is not.

## 4. Pagination

**Cursor-based** for every collection that can grow:

```
GET /api/client/v1/titles?limit=50&cursor=eyJ...
```
```json
{ "data": [ … ],
  "page": { "next_cursor": "eyJ...", "has_more": true } }
```

- Offset pagination is not used on growable collections: it is unstable under concurrent writes
  (items shift between pages) and degrades on deep pages.
- **EPG is the exception, and deliberately so.** Schedule queries are `channel × time range`, which
  is a bounded window over an ordered, time-partitioned table, not an open-ended scroll. EPG
  endpoints take an explicit time range with a documented maximum span rather than a cursor. Forcing
  a cursor onto a time-range query buys nothing and makes the most cacheable endpoint in the product
  harder to cache.
- Cursors are opaque and versioned. Clients never construct or parse them.
- `limit` has a documented default and maximum.
- Total counts are omitted unless a specific screen needs one — they are expensive and usually
  decorative.

## 5. Idempotency

Every non-`GET` request that creates state or moves money accepts:

```
Idempotency-Key: <client-generated UUIDv7>
```

- The key is stored with the response for a documented window (24 hours minimum).
- Replaying the same key returns the original response without re-executing.
- Same key with a **different** body is a `409` — this catches client bugs rather than silently
  doing the wrong thing.
- **Mandatory** on: subscription creation and change, payment operations, voucher redemption, device
  registration, playback session start.

Playback session start is on that list because TV clients retry aggressively on flaky networks, and
without idempotency each retry burns a concurrency slot — producing "too many streams" errors for a
viewer sitting alone in their living room.

## 6. Caching

### The rule that makes the rest safe

> **A response may be `public` only if it is identical for every caller who could share that cache
> entry.** Anything varying by account, profile, entitlement or device is `private`.

This matters because two other rules in this repository pull against each other: rights and
entitlement filtering is server-side ([`surfaces.md`](surfaces.md)), and catalog responses should be
cacheable for cost and for TV performance. A `public` response that has been filtered per viewer
would be served by a shared cache to the **wrong** viewer — a cross-account data leak wearing the
costume of a performance optimisation.

The resolution is to split the response, not to weaken either rule:

| Layer | Content | Varies by | Cacheability |
|---|---|---|---|
| **Catalog metadata** | Titles, synopses, artwork, credits, ratings | **Locale and territory only** | `public` — keyed on locale + territory |
| **Availability overlay** | Playable now? In your package? Which exploitations? Badges | Account, profile, device | **`private`** |

Clients fetch metadata (cheap, highly cacheable, long-lived) and the overlay (small, personal,
short-lived) separately and compose them. Territory is part of the **cache key**, not a `Vary`
header — an unbounded `Vary` on a header a client controls fragments the cache to uselessness and
invites cache-poisoning attempts.

### Directives

| Data | Directive | Note |
|---|---|---|
| Catalog metadata | `public, max-age=300, stale-while-revalidate=3600` | Non-personalised. `ETag`. Territory + locale in the cache key |
| Images | `public, max-age=31536000, immutable` | Content-hashed URLs |
| EPG (past) | `public, max-age=3600` | Immutable in practice |
| EPG (now/next) | `public, max-age=30` | |
| **Availability overlay** | `private, max-age=60` | Personalised |
| Page layout | `private, max-age=60` | Personalised |
| Entitlements, profiles | `private, no-store` | |
| **Playback authorization** | **`no-store`** | Never cached, anywhere, at any layer |
| Config | `public, max-age=300` | Must be reachable when everything else fails |

### Two kinds of "not available"

The split above also forces a distinction the product needs anyway:

- **Absent** — the item is not in any response at all. Used for content that is embargoed, or not
  licensed in the territory in a way that makes its very existence confidential. Absence is the only
  safe treatment; a flagged-but-present item is a leak whatever the flag says.
- **Present but not playable** — the item appears with an availability state (`not_in_package`,
  `coming_soon`, `expired`). This is what makes upsell and "leaving soon" possible.

Which content falls in which bucket is an **editorial and rights decision**, expressed as data on the
availability projection — not a hardcoded API behaviour.

`ETag` and `If-None-Match` on catalog and EPG endpoints matter disproportionately for TV clients on
constrained hardware and slow connections.

### Client identification headers must not reach the cache key

`X-KMS-Client` and `X-KMS-Client-Version` are used for measurement, diagnostics and minimum-version
enforcement ([`versioning.md`](versioning.md) §4). They must **not** vary a cacheable response, or
every client build gets its own cache entry. Where a response genuinely must differ per client
platform — a device-specific page layout — that response is `private`, or the platform is a path
segment rather than a header.

## 7. Rate limiting

- Per account, per device, and per IP — different limits, all enforced.
- `429` with `Retry-After`; clients must honour it with exponential backoff and jitter. Verified in
  client conformance tests, because a fleet of TVs retrying in lockstep is a self-inflicted denial of
  service.
- Authentication endpoints are limited far more tightly than reads (credential stuffing —
  [`../security/threat-model.md`](../security/threat-model.md)).
- Playback authorization has a **high** limit but is not unlimited; the limit is set from the
  measured legitimate maximum, not guessed.

## 8. Request tracing

- `X-Correlation-Id` accepted from the client, generated if absent, echoed in the response, present
  in every log line and every downstream call — including into player telemetry and CDN logs where
  possible.
- One identifier must connect: client action → API request → decision record → CDN request → licence
  request → QoE event. Without that chain, diagnosing "it does not play on my TV" is guesswork.

## 9. Content negotiation

- `application/json` only for request and response bodies. No XML, no form encoding except where a
  provider-hosted payment flow requires it.
- `Accept-Language` drives localised catalog text; the resolved locale is echoed in the response so
  a fallback is visible rather than mysterious.
