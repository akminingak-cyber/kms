# ADR-0013 — The operator panel is a static SPA, not a Next.js application

**Status:** Accepted
**Date:** 2026-08-12
**Supersedes:** the `apps/admin` (Next.js) note in
[`03-repository-structure.md`](../03-repository-structure.md)
**Related:** [ADR-0010](ADR-0010-contract-first-openapi.md) ·
[`../../security/threat-model.md`](../../security/threat-model.md)

## Context

Phase 0 named Next.js for both `apps/web` and `apps/admin`, before either existed and before the
admin API did. Building the panel forced the question properly, and the two applications turn out to
want different things.

`apps/web` is viewer-facing. Server rendering earns its keep there: first paint on a slow connection,
metadata for sharing, and search indexing all matter.

`apps/admin` is none of those. It is reached by six people behind staff authentication, it is
deliberately `noindex`, and every byte it shows comes from an API that requires a token the server
would not have. Server rendering would buy nothing and cost three things:

1. **A server to deploy.** Next.js is a Node runtime that must be built, shipped, patched and placed
   in a network zone. That zone question is not incidental — the panel talks to the admin API, which
   is served on its own hostname and network policy precisely so it is not reachable from where
   viewers are.
2. **A second place for a token to live.** Any server-rendered variant needs the staff token
   somewhere the server can read it, which in practice means a cookie. A JavaScript-readable cookie
   is the thing this design is specifically avoiding, and an `HttpOnly` one moves session handling
   into a server we would otherwise not be running.
3. **A framework's worth of surface** on the one application whose compromise hands over rights,
   pricing and subscriber data.

## Decision

**`apps/admin` is a static single-page application: React, TypeScript and Vite, built to files and
served by the existing edge.** No server runtime, no server-side rendering, no framework data layer.

Supporting decisions, each of which is a deviation worth naming:

| Decision | Instead of | Why |
|---|---|---|
| `openapi-typescript` + `openapi-fetch` | `orval` + TanStack Query (the Phase 0 candidate) | Generated types plus a 130-line typed client, against a code generator and a caching library. The panel has no cache-invalidation problem to solve |
| A ~90-line `useResource` hook | A data-fetching library | A stale cache is a worse failure here than a refetch: an operator who has just created a channel and does not see it concludes the write failed |
| Hand-written CSS | Tailwind | The requirement is that a table is readable at 2am. Tailwind was already in this repository — attached to a starter that did not build, and used by nothing |
| Access token in memory | `localStorage` / `sessionStorage` / a readable cookie | See below |

### The token is held in memory only

A staff token reaches rights data, pricing and the decision log for every subscriber. Every browser
storage is readable by any script that runs on the origin, so one cross-site scripting flaw — in
this application or in any dependency it ever acquires — would hand over a working staff credential
rather than merely defacing a page.

**The cost is accepted and is stated plainly to the operator: refreshing the page signs them out.**
For an internal tool used by a small number of people on short-lived tokens, that is a fair trade for
removing the most valuable thing an attacker could take from this origin.

If it proves unworkable in practice, the answer is a short-lived refresh mechanism on an `HttpOnly`
cookie — **not** moving the access token into storage where script can read it.

## Consequences

**`apps/web` is not bound by this.** It has different requirements and gets its own decision when it
is built. This ADR is about the panel.

**Deployment is a file copy.** The panel is a `dist/` directory served by the same edge as the API,
which is also what makes the API same-origin and removes cross-origin credential handling entirely.

**No server-side session.** Sign-out is a page action, and there is nothing to invalidate anywhere
else — the token simply stops existing.

**Refresh signs you out**, and will keep doing so until somebody decides the refresh mechanism is
worth building. That is a known, recorded annoyance rather than an oversight.

**The generated client is a hard dependency of the panel building at all.** A contract change that
nobody regenerated for fails `pnpm contracts:verify` in CI before it can fail a person.

## Alternatives considered

**Next.js as originally planned.** Rejected on the grounds above: it costs a deployed runtime, a new
trust-zone question and a session-storage question, in exchange for rendering benefits that a
token-authenticated internal tool cannot use.

**Server-rendered with a session cookie.** This is the shape to reach for if the panel ever needs to
be usable across page refreshes without re-authenticating, and it is the recorded fallback. It is
not worth a Node deployment today.

**Extend an existing admin framework** (Filament, Nova, or similar, inside `core-api`). Genuinely
tempting: it would have produced screens faster. Rejected because it would bypass the API contract
entirely — those frameworks bind to Eloquent models, which means the panel would read tables across
module boundaries, and the boundary rule that makes ADR-0001 valid would be broken by the one
application most likely to touch everything. It would also make the admin API optional, and an API
that only a machine uses is an API that quietly stops being maintained.
