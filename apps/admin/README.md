# `apps/admin` — the operator panel

A static single-page application over the [admin API](../../packages/api-contracts/admin/v1/openapi.yaml).

## What it is for

Six roles, one tool. The screen that justifies the whole thing is **Playback decisions**: "why could
this person not watch?" is the commonest support question an OTT platform receives, and without a
record it is answered by guesswork — nobody can reproduce a viewer's territory, device, subscription
and moment. Every decision was recorded with the inputs that produced it, so the answer is a row.

## Three properties worth knowing before changing anything here

**Every call goes through the generated client.** There are no hand-written `fetch` calls to our own
API anywhere in this repository. Types come from `@kms/ts-api-client`, which is generated from the
OpenAPI contracts and verified in CI; a URL typed by hand is a URL that can drift from the contract
without anything failing.

**The token is held in memory only.** Not `localStorage`, not `sessionStorage`, not a
script-readable cookie. A staff token reaches rights, pricing and the decision log for every
subscriber, so a single cross-site scripting flaw must not be able to walk away with a working
credential. The cost is accepted and is real: **refreshing the page signs you out.**

**Role-based navigation is a usability measure, not a security one.** Every route is enforced
server-side. Hiding a link stops an operator collecting refusals all day; it has never stopped
anyone typing a URL.

## Running it

```bash
pnpm install                 # from the repository root
pnpm --filter @kms/admin dev # http://localhost:5173
```

The dev server proxies `/api` to `http://127.0.0.1:8000` by default — override with
`KMS_API_ORIGIN`. The proxy exists so the browser's credential and CORS behaviour in development is
identical to production; a development setup that needs CORS relaxations is testing a configuration
nobody ships.

You will need a staff account with MFA enrolled. There is no branch of sign-in that issues a token
without a TOTP code.

## Commands

| Command | What it does |
|---|---|
| `pnpm --filter @kms/admin test` | Vitest with Testing Library, against the real API client and a stubbed network |
| `pnpm --filter @kms/admin typecheck` | `tsc --noEmit`, `strict` on |
| `pnpm --filter @kms/admin build` | Type-check, then bundle to `dist/` |
| `pnpm contracts:generate` | Regenerate the API types after a contract change |

## What is deliberately absent

- **A dashboard.** It would need numbers, the numbers would need endpoints that count rows on
  growing tables, and nobody would act on them.
- **A caching layer.** A handful of operators looking at lists; a stale cache is a worse failure
  than a refetch. An operator who has just created a channel and does not see it concludes the
  write failed.
- **A CSS framework.** The requirement is that a table is readable at 2am.
- **Subscriber search by name or email.** The decision log takes identifiers only. Searching it by
  email would turn a diagnostic tool into a viewing-history search engine over every subscriber.
