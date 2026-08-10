# API Strategy

Decision records:
[ADR-0004 versioning](../architecture/adr/ADR-0004-api-versioning.md) ·
[ADR-0010 contract-first OpenAPI](../architecture/adr/ADR-0010-contract-first-openapi.md)

## Contents

| Document | Covers |
|---|---|
| [`surfaces.md`](surfaces.md) | The four API surfaces and who may call them |
| [`versioning.md`](versioning.md) | Versioning, compatibility, deprecation, client version signalling |
| [`conventions.md`](conventions.md) | Resources, errors, pagination, idempotency, caching, rate limits |
| [`openapi-workflow.md`](openapi-workflow.md) | How the contract is authored, verified and generated from |

## The constraint that shapes everything

KMS TV's API has consumers it cannot update. A Samsung or LG television application reaches its
installed base over **months to years**, and some devices effectively stop receiving updates
altogether.

The practical consequence: **within a major version, the client API is append-only.** Not "we try
hard not to break it" — structurally append-only, with the check enforced in CI
([`openapi-workflow.md`](openapi-workflow.md)). Every other API decision here follows from that.

The corollary is that the API must be designed with more care than a purely web-facing API would
need, because mistakes are expensive to correct. Specifically:

- **Return identifiers and let clients resolve, rather than embedding deeply nested structures**
  which then cannot change shape.
- **Design list responses to accept new item types** — a TV rail rendering an unknown item type must
  skip it, not crash. Tested, not assumed.
- **Never make a field's absence meaningful.** A client three years old will not send a field a
  future server expects; a client three years old will not understand the absence of a field it
  relies on.
- **Server-drive what changes often.** TV UI layout comes from the server (the Discovery context) so
  merchandising can change without a client release. This is the single highest-value decision for
  a platform with slow clients.

## Principles

1. **The spec is the contract.** Not the implementation, not the documentation site.
2. **One surface per audience** — different audiences have irreconcilable change rates.
3. **Failures are specific.** A denial says *which* check failed, with a stable machine-readable
   code. Generic errors make support impossible and hide bugs.
4. **Anything that mutates money or entitlement is idempotent.**
5. **Pagination is cursor-based** anywhere the collection can grow.
6. **Responses carry cache directives that are actually correct** — catalog data is cacheable for a
   long time; an authorization decision is cacheable never.
7. **No endpoint returns data the caller is not entitled to see**, and no client is trusted to hide
   anything. Filtering is server-side, always. Note the corollary that follows in
   [`conventions.md`](conventions.md) §6: a personalised response can never be `public`-cacheable, so
   catalog metadata and the personalised availability overlay are **separate responses**.
