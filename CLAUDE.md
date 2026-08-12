# Engineering Rules — KMS TV

Applies to **every contributor, human or AI**. Read this before writing code in this repository.

**Current status: the control plane is built and the media plane is specified.** `services/core-api`
carries identity, catalog, commercial, rights, playback and media modules with CI-enforced
boundaries. What is absent is absent on purpose — payments, DRM, IP geolocation, a CDN adapter and a
running encoder all await decisions recorded as open questions in
[`ARCHITECTURE.md`](ARCHITECTURE.md#13-open-questions). Nothing among them is stubbed, and §1.1 below
is why.

---

## 1. Prime directives

These are not style preferences. Violating them produces work that looks finished and is not.

### 1.1 Build the real thing
No fake functionality. No plausible-looking placeholder data. No feature marked done that is stubbed.

An unbuilt feature returns `501` or is absent from the API spec entirely. It does **not** return
sample data. A stub that returns plausible data is worse than nothing, because downstream work gets
built on it and the hollowness is only discovered much later, in integration.

### 1.2 No mocks where a real implementation is required
Mocks belong at **ports** — the boundaries with third parties — and nowhere else.

| Situation | Use |
|---|---|
| Collaborators inside a bounded context | The real objects |
| A repository in an integration test | **Real PostgreSQL.** Not SQLite, not an in-memory array |
| A third-party in a unit test | The **one** fake adapter for that port, owned by us |
| A third-party in staging | The **real vendor sandbox** |

Every port fake is kept honest by a scheduled contract test against the vendor sandbox
([`docs/architecture/05-integration-boundaries.md`](docs/architecture/05-integration-boundaries.md)).
There is one fake per port, not ad-hoc mocks per test.

### 1.3 Do not invent APIs or vendor capabilities
If you do not know what a vendor's API does, **do not guess and do not write code against the guess**.
Vendor documentation is unreachable from the initialisation environment, so:

- Mark unverified claims `[UNVERIFIED]`.
- Add the question to the port's verification checklist.
- **Do not build the adapter until the checklist is complete.**

The same applies to our own APIs: the OpenAPI spec is the contract. If it is not in the spec, it does
not exist.

### 1.4 Verify dependencies and licences before use
Read the version and licence from the registry — never from memory. Confirm the licence is compatible
with **how we will use it** (server-side versus shipped in a client app: this distinction matters, and
it is what makes FFmpeg's build flags and Bento4's dual licensing real decisions).

Record it in
[`docs/architecture/09-dependency-policy.md`](docs/architecture/09-dependency-policy.md) with a
reason. "It is the standard choice" is not a reason.

### 1.5 Default deny
Absence of a right, an entitlement, or a permission is a **prohibition**. Never a permission. This
holds in code, in data, and in migrations.

### 1.6 State uncertainty plainly
If a decision depends on something unknown, say so, record it as an open question, and proceed with
everything that does not depend on it. Do not silently pick a default for a decision that belongs to
the product owner.

---

## 2. Where things go

```
apps/          client applications — delivery mechanism only, no business rules
services/      backend services; core-api holds the control-plane contexts as modules
packages/      shared libraries; api-contracts is the API source of truth
infrastructure/ containers, IaC, edge config, observability-as-code
docs/          architecture · database · api · security · streaming · operations
```

Full layout and rationale:
[`docs/architecture/03-repository-structure.md`](docs/architecture/03-repository-structure.md).

**Do not create a directory before the phase that needs it.**

---

## 3. The boundary rule

> Inside `core-api`, a module may import from another module **only** through that module's
> `Contracts/` namespace. Nothing else — not an Eloquent model, not a helper, not a constant.

Enforced by CI. If you need something from another context:

1. Call its inbound port (`RightsQuery::isAvailable(...)`) — preferred.
2. Subscribe to a domain event.
3. Read a purpose-built read model.

Never reach into another module's tables.

This rule is what makes [ADR-0001](docs/architecture/adr/ADR-0001-modular-monolith-first.md) valid.
Without enforcement, the modular monolith becomes a monolith within weeks and extraction later becomes
archaeology.

---

## 4. Code standards

### General
- Match the surrounding code. Consistency beats personal preference.
- Names come from the domain vocabulary in
  [`docs/architecture/02-bounded-contexts.md`](docs/architecture/02-bounded-contexts.md). Do not
  invent a synonym for a term the domain already has.
- Comments explain **why**, never what. Code that needs a comment to explain what it does should be
  rewritten.
- No `TODO` standing in for functionality the change claims to deliver. A `TODO` is acceptable only
  with a linked issue and only for work that is genuinely out of scope.

### PHP
- Strict types. Typed properties, parameters and returns everywhere.
- `Domain/` contains **no framework imports**. Entities and value objects are plain PHP.
- `Application/` holds use cases and transaction boundaries.
- `Infrastructure/` holds Eloquent, adapters and migrations.
- `Http/` is a delivery mechanism: validate, delegate, present. **No business logic in a controller.**
- Money is a `Money` value object. **Never a float. Never a bare integer without a currency.**
- Times are `timestamptz` in UTC; broadcast-local times carry an explicit IANA timezone.

### TypeScript
- `strict` on. No `any` without a written justification in the PR.
- The API client is **generated** from the spec. No hand-written fetch calls to our own API.
- Runtime validation at the boundary where data enters the application.

### Everywhere
- Errors are specific. A caught exception either handles the failure or re-raises with context.
  Swallowing an error is a bug, always.
- No secret, credential, token, key or production identifier in code, config, tests, fixtures,
  comments or commit messages.

---

## 5. Database

- One schema per bounded context. **No foreign key crosses a schema boundary.**
- Migrations live with their module and touch **one schema**.
- **Expand/contract for anything live code touches.** Three releases to rename a column is the price
  of never taking the platform down.
- `CREATE INDEX CONCURRENTLY` on any table with volume. Set `lock_timeout`.
- Backfills are separate, resumable, throttled jobs — never inline in a migration.
- Time-based high-volume tables are **partitioned in their first migration**.
- Public identifiers are UUIDv7 in `uuid` columns. Sequential integers are never exposed in an API.

Detail: [`docs/database/`](docs/database/)

---

## 6. API

- **Change the spec first**, in a reviewed PR. Then implement.
- Within a major version the client API is **append-only**. Breaking-change detection in CI can be
  bypassed only by a PR that introduces the new major version. There is no exception path.
- Errors are RFC 9457 problem documents with a **stable `code`**. Clients switch on `code`, never on
  prose.
- **Playback denials get specific reason codes.** A generic "not available" makes support impossible
  and hides bugs.
- Cursor pagination on anything growable. Idempotency keys on anything that moves money or
  entitlement.

Detail: [`docs/api/`](docs/api/)

---

## 7. Security

- The client is never a security boundary. Every check that matters is server-side.
- **Fail closed** on rights and entitlement checks. "Allow because the check was unavailable" never
  ships.
- Content keys exist only in the vault and the licence proxy. If a key value can be selected out of
  PostgreSQL, the design has failed.
- No production data outside production. Not anonymised, not partial, not for one debugging session.
- Changes to authentication, authorization, rights, entitlement, payment or key handling require
  **security review** — enforced by `CODEOWNERS`.

Detail: [`docs/security/`](docs/security/)

---

## 8. Testing

- Test behaviour at a context's boundary, not a class's internals.
- Integration tests run against **real PostgreSQL and Redis**.
- Rights, entitlement and money logic get the strongest testing in the codebase: property tests,
  golden fixtures, and reproducibility tests. A bug there is a contractual or financial event.
- Manifests get **golden-file tests**. Manifest regressions are invisible in API tests and
  catastrophic in players.
- Every change includes at least one **failure-path** test.
- A skipped test is a failing test with a nicer colour.

Detail: [`docs/architecture/07-testing-strategy.md`](docs/architecture/07-testing-strategy.md)

---

## 9. Commits and pull requests

### Commits
Conventional commits, scoped by module or package:

```
feat(rights): add territory rule evaluation
fix(playback): release concurrency slot on heartbeat expiry
docs(architecture): record CDN abstraction decision
chore(deps): update lockfile after security audit
```

- One logical change per commit. Squash-merge to `main`.
- Never mention model names, tooling, or session identifiers in commit messages, PR bodies or code.

### Pull requests
Every PR states:

1. **What** changed and **why** — the why is the part reviewers cannot reconstruct.
2. Which ADR or open question it relates to, if any.
3. Any new dependency, with its licence and the reason it is needed.
4. Any migration, and whether it is expand or contract.
5. What was verified, and how.

---

## 10. Definition of done

A change is done when **all** of these are true:

- [ ] Behaviour is covered by tests at the appropriate layer, including a failure path
- [ ] No new module-boundary violation (CI-checked)
- [ ] OpenAPI spec updated **and** conformance verified, if the API changed
- [ ] Migration is expand/contract-safe, if the schema changed
- [ ] Observability added for anything that can fail in production
- [ ] Documentation updated if an architectural decision changed
- [ ] No secret, credential or production identifier added
- [ ] No `TODO` standing in for claimed functionality
- [ ] Nothing stubbed that the change claims to deliver

---

## 11. When a decision is needed

Write an **ADR** for anything that:

- Constrains future work
- Chooses between real alternatives with real trade-offs
- Selects a vendor
- Changes a boundary — between contexts, services, or trust zones
- Would prompt someone in a year to ask *"why on earth did they do it this way?"*

Template and index: [`docs/architecture/adr/`](docs/architecture/adr/).

An ADR marked **Proposed must not be built on.**

If the decision belongs to the product owner rather than to engineering, add it to the open questions
in [`ARCHITECTURE.md`](ARCHITECTURE.md) and proceed with everything that does not depend on it.

---

## 12. Environment reality

The container this repository was initialised in has **no Docker daemon, no PostgreSQL server, no
Redis server, no FFmpeg, no Nginx**, and no mobile or TV toolchain. Network egress reaches package
registries but **not vendor documentation**.

So: backend, web, schema, contract and documentation work can be done and verified here. Anything
needing a container runtime, a database server, media processing or a device build **must be
validated elsewhere** — and work that assumes otherwise is work nobody has actually run.

Full detail: [`docs/operations/local-development.md`](docs/operations/local-development.md)

---

## 13. The short version

> Build the real thing. Verify before you depend on it. Fail closed. Enforce the boundaries
> mechanically. Say what you do not know.
