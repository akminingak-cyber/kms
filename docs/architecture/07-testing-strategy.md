# Testing Strategy

## 1. Principles

1. **Tests exist to let us change the system safely.** Coverage percentage is a diagnostic, never a
   target. A codebase at 90% coverage that cannot survive a refactor is worse tested than one at 60%
   that can.
2. **Test behaviour at the boundary of a bounded context, not the internals of a class.** Tests
   coupled to internal structure are the main reason teams stop refactoring.
3. **No mocks for collaborators inside a context.** Use the real objects and a real database.
   Mock only at ports (external systems), and every port fake is validated against the vendor by a
   scheduled contract test — see
   [`05-integration-boundaries.md`](05-integration-boundaries.md).
4. **Some things cannot be tested in CI and must not be pretended.** Real devices, real DRM licence
   servers, real CDNs, real payment flows. Those get a device lab and staged environments, not a
   mock that produces a green tick and a false sense of safety.
5. **Money, rights and entitlements get the strongest testing in the codebase.** A bug there is a
   contractual or financial event, not a defect.

## 2. Layers

| Layer | What it covers | Runs | Speed budget |
|---|---|---|---|
| **Unit** | Domain logic in isolation: rights evaluation, price/proration maths, window arithmetic, state machines | Every commit | Whole suite < 60 s |
| **Integration** | A module against real PostgreSQL and Redis: repositories, migrations, projections, queue handlers | Every commit | < 5 min |
| **Contract (internal)** | Implementation conforms to the OpenAPI spec, both directions | Every commit | < 2 min |
| **Contract (external)** | Our adapters against vendor **sandboxes** | Nightly + before release | Minutes |
| **End-to-end (API)** | Business journeys across contexts: register → subscribe → entitle → authorize → play | Every merge to main | < 15 min |
| **End-to-end (UI)** | Critical web/admin journeys (Playwright) | Every merge to main | < 15 min |
| **Player conformance** | Manifest correctness, DRM handshake, ABR behaviour, error handling | Per media-pipeline or player change | Minutes |
| **Device matrix** | Real TVs, phones, consoles, browsers | Per release candidate | Hours, partly manual |
| **Load / soak** | Playback authorization at target concurrency; sustained live delivery | Weekly + before launch | Long |
| **Security** | SAST, dependency audit, secret scanning, DAST, external penetration test | Continuous + pre-launch | Varies |
| **Disaster recovery** | Restore from backup; origin/CDN failover; region loss | Quarterly drill | Scheduled |

## 3. What "no mock implementations" means in practice

The brief's rule — *do not use mock implementations where a real implementation is required* — has a
precise reading here:

| Situation | Allowed |
|---|---|
| Unit-testing rights evaluation | Real evaluator, in-memory data. **No mock of the evaluator itself** |
| Repository in an integration test | **Real PostgreSQL** (container). Not SQLite, not an in-memory array — SQLite silently accepts SQL PostgreSQL rejects, and vice versa |
| Payment provider in a unit test | The **fake adapter** for the `PaymentGateway` port, owned by us, validated nightly against the sandbox |
| Payment provider in staging | The **real provider's sandbox**. Never our fake |
| DRM licence server in CI | Fake adapter + recorded fixtures for the request/response shape |
| DRM in staging | The **real licence server** with test content and test devices |
| CDN in CI | Fake adapter; manifest and token generation verified by golden-file tests |
| CDN in staging | A **real CDN** configuration, even at trivial volume |
| "Feature not built yet" | **Nothing.** No stub returning plausible data. An unbuilt feature returns `501` or is absent from the spec |

The last row is the important one. Stubs that return plausible data are how a system appears
finished while being hollow — and the appearance is what makes the hollowness dangerous, because
downstream work is built on it.

## 4. High-risk areas and their specific treatment

### Rights and availability evaluation
- **Property-based tests**: for any rights configuration, the evaluator must never return "allow"
  when a blackout is active, must never widen a restriction when combining rules, and must be
  deterministic for a given input set.
- **Golden decision fixtures**: a corpus of real-world-shaped scenarios (overlapping windows,
  territory exclusions with inheritance, mid-event blackouts, relative catch-up windows) with
  expected decisions, reviewed by a rights owner, and version-controlled.
- **Reproducibility test**: replaying a recorded decision against its recorded input versions
  reproduces it exactly (requirement 3 in [`06-rights-management.md`](06-rights-management.md)).
- **Mutation testing** on this module specifically — it is worth the runtime here.

### Billing and entitlements
- State machine transition tests covering every legal and illegal transition.
- **Idempotency tests**: every payment and entitlement operation replayed with the same idempotency
  key produces one effect. Replayed webhooks likewise.
- Time-travel tests for renewals, trials, proration, grace periods, and timezone/DST boundaries.
- **Money never as float.** A test asserts this at the type level; the shared kernel's `Money` type
  makes it structurally impossible.

### Playback authorization
- Load tested against the target concurrency **before** each phase that increases audience.
- Explicit tests for every degraded mode: entitlement cache stale, rights projection unavailable,
  geo lookup failing, concurrency store unavailable. Each has a defined, tested outcome — never an
  unhandled exception, never a silent allow.
- Concurrency accounting tested under parallel load, including session leakage after client crash.

### Media pipeline and manifests
- **Golden-file tests** on generated HLS and DASH manifests. Manifest regressions are invisible in
  application tests and catastrophic in players.
- Validation of outputs with an independent tool, not only our own parser.
- Fixture media assets are small, synthetic, and committed as generation scripts — **never large
  binaries in git**.

## 5. Test data

- **No production data in any non-production environment.** Not anonymised, not "just the catalog".
  This is a hard rule: production data in staging is the most common route to a personal-data breach.
- A **seed dataset** is maintained as code: a small fictional catalog, channels, schedules, rights
  configurations and accounts covering the interesting edge cases. It is a first-class deliverable
  and part of Phase 1.
- Media fixtures are generated (e.g. synthetic test patterns) rather than sourced from real content —
  which also avoids using licensed content in test environments.

## 6. Environments

| Environment | Purpose | Data | External systems |
|---|---|---|---|
| **local** | Development | Seed | Fakes + sandboxes |
| **ci** | Automated verification | Seed, ephemeral | Fakes only (sandboxes nightly) |
| **preview** | Per-PR review (Phase 2+) | Seed | Fakes |
| **staging** | Pre-production verification | Seed + synthetic scale | **Real sandboxes**, real CDN, real DRM test |
| **production** | Live | Real | Real |

Details in [`docs/operations/environments.md`](../operations/environments.md).

## 7. Definition of done

A change is done when all of the following are true. This list is enforced by CI and by review, and
is repeated in [`CLAUDE.md`](../../CLAUDE.md):

- [ ] Behaviour covered by tests at the appropriate layer, including at least one failure path
- [ ] No new module-boundary violation (checked automatically)
- [ ] OpenAPI spec updated **and** the implementation verified against it, if the API changed
- [ ] Migration is expand/contract-safe and reversible, if the schema changed
- [ ] Observability added for anything that can fail in production
- [ ] Documentation updated when the change alters an architectural decision
- [ ] No secret, credential, or production identifier added to the repository
- [ ] No `TODO` standing in for functionality the change claims to deliver
