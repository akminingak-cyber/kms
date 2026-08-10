# OpenAPI Workflow

Decision: [ADR-0010](../architecture/adr/ADR-0010-contract-first-openapi.md) — the spec is the source
of truth, not a description generated from the implementation.

## 1. Layout

```
packages/api-contracts/
├── client/
│   ├── v1/
│   │   ├── openapi.yaml           # entry document
│   │   ├── paths/                 # one file per resource group
│   │   ├── components/
│   │   │   ├── schemas/
│   │   │   ├── responses/
│   │   │   ├── parameters/
│   │   │   └── errors/            # shared problem types + the code registry
│   │   └── examples/              # doubles as test fixtures
│   └── CHANGELOG.md
├── admin/v1/…
├── partner/v1/…
├── .spectral.yaml                 # style rules enforced in CI
└── package.json
```

OpenAPI **3.1**, split across files and bundled for publication. One 12,000-line YAML file is not
reviewable, and review is the point.

## 2. Making an API change

```
1. Edit the spec in packages/api-contracts
2. make spec-lint          → Spectral style rules
3. make spec-diff          → breaking-change detection vs the released version
4. Open a PR — the spec diff is the reviewable artifact
5. Regenerate clients      → make spec-generate
6. Implement the server
7. make spec-conformance   → implementation checked against the spec, both directions
```

Steps 2–4 happen **before** implementation. That ordering is the whole mechanism: it makes an API
change a deliberate, reviewed decision rather than a side effect of a code change.

## 3. CI gates

| Gate | Fails the build when | Why |
|---|---|---|
| **Style** (Spectral) | Naming, error shape, pagination, or versioning conventions violated | Mechanical enforcement beats per-PR debate |
| **Breaking change** | A breaking change on an existing major version (per [`versioning.md`](versioning.md) §2) | **The single most valuable check in the pipeline** for slow-updating clients |
| **Conformance** | The implementation exposes something undocumented, or omits something documented | Prevents the spec becoming fiction |
| **Examples valid** | An example does not validate against its schema | Examples are used as fixtures |
| **Generated clients build** | Regenerated clients fail to compile | Catches spec changes that are technically valid but unusable |
| **Changelog** | A spec change with no changelog entry | Deprecations must be announced |

The breaking-change gate can be overridden **only** by a PR that also introduces the new major
version. There is no "approved exception" path, because the exception always looks reasonable in the
moment and the cost lands on a viewer with a television that will never be updated.

## 4. Code generation

| Target | Tool (candidate, verified in [`../architecture/09-dependency-policy.md`](../architecture/09-dependency-policy.md)) | Output |
|---|---|---|
| TypeScript types | `openapi-typescript` | `packages/ts-api-client/types` |
| TypeScript client | `orval` | Typed client + TanStack Query hooks |
| Client test fakes | `msw` handlers from examples | Client tests without a server |
| Kotlin | Selected in Phase 8 | |
| Swift | Selected in Phase 8 | |
| Documentation | `@redocly/cli` | Published reference |

**Generated code is committed** (so diffs are reviewable and builds are reproducible) and
**never hand-edited** — CI regenerates and fails on any difference.

## 5. Server conformance

Conformance runs in both directions, because each catches a different failure:

- **Spec → implementation:** every documented operation is exercised against the running service; the
  response is validated against the schema. Catches "documented but not implemented", and drift after
  a refactor.
- **Implementation → spec:** the route table is compared with the spec. Catches the more common and
  more dangerous case — an endpoint or field that exists in production but was never reviewed as part
  of the contract.

The second direction is what stops the spec quietly becoming a partial description of a larger,
undocumented API.

## 6. Error code registry

`components/errors/codes.yaml` is a single registry of every error code with its meaning, HTTP
status, and the surface it applies to.

- Codes are **append-only**. A code's meaning never changes once shipped.
- Adding a code requires a changelog entry, because clients may need a localised message for it.
- CI fails on a code returned by the implementation that is absent from the registry.

This registry is what makes localisation, support tooling, and client-side error handling possible
across a client fleet that spans several years of releases.

## 7. Publication

- Reference documentation is generated on merge to main and published for the relevant audience.
- The client surface's reference is available to client developers; the admin surface's is internal;
  the partner surface's is published per contract.
- Every published version is retained — a partner or a device manufacturer will ask about a version
  retired two years ago.
