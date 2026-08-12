# `@kms/ts-api-client`

Typed clients for the KMS TV APIs, generated from the OpenAPI contracts.

```
packages/api-contracts/{client,admin}/v1/openapi.yaml
        │  pnpm contracts:generate
        ▼
src/types/{client,admin}.ts        ← generated, committed, never hand-edited
src/index.ts, src/problem.ts       ← the small glue a contract cannot express
```

## Why the generated output is committed

So that diffs are reviewable and builds are reproducible. `pnpm contracts:verify` regenerates into
memory and fails if the result differs from what is on disk — that check runs in CI, and it is the
only thing standing between a spec change and a client that compiles against an API which no longer
exists. Both sides would still type-check on their own; nothing else would notice.

## What is hand-written, and why

Three things a contract cannot express:

- **How a token is attached.** Supplied as a function rather than a value, so a token refreshed
  mid-session is picked up by calls already in flight.
- **How a correlation id is propagated.** Originated here rather than left to the server, so the
  value is known to the caller and can be shown in an error message.
- **How a problem document becomes a typed error.** Every failure carries a stable machine-readable
  `code`; clients switch on that and never on prose, because the title is not localised, is not
  stable, and building behaviour on it means a copy edit changes what the application does.

The shape is **validated**, not asserted. A gateway timeout returning HTML can arrive where a
problem document was expected, and casting one of those to a typed object produces `undefined` deep
inside rendering code rather than an error at the boundary.
