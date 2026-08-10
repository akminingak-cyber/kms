# ADR-0007: Laravel major version for `core-api`

**Status:** **Proposed — requires a decision from the product/technical owner before Phase 1**
**Date:** 2026-08-10
**Deciders:** Product owner, Architecture

## Context

The brief specifies **Laravel 12 + PHP 8.3+**. Verification against Packagist on 2026-08-10 produced
facts that make this worth an explicit decision rather than a silent implementation:

| Fact (verified 2026-08-10, `repo.packagist.org`) | Value |
|---|---|
| `laravel/framework` latest stable | **v13.24.0**, released **2026-08-04**, requires PHP `^8.3`, MIT |
| Laravel 13.0.0 release date | **2026-03-17** |
| Laravel 12 latest release | v12.65.0, released 2026-08-05, requires PHP `^8.2`, MIT |
| Laravel 12.0.0 release date | **2025-02-24** |
| Laravel 11.0.0 release date | 2024-03-12 |
| PHP available in the target environment | **8.4.19** |

Laravel 12 is therefore approximately **18 months old** as of today, and Laravel 13 has been
available for roughly five months.

Laravel's published support policy (bug fixes for a period after release, security fixes for a
longer period) determines how much runway Laravel 12 has left. **That policy page could not be
retrieved from this environment** — `laravel.com` is blocked by the egress proxy
([`../00-inspection-report.md`](../00-inspection-report.md), E6). The release dates above are
verified; the resulting end-of-support dates are **not**, and must be confirmed at
`laravel.com/docs/12.x/releases` before this ADR is accepted.

The concern is straightforward regardless of the exact dates: KMS TV is a greenfield platform whose
Phase 1 has not started and whose launch date is unknown (OQ-21). Starting on a major version that
is already 18 months into its lifecycle means a framework upgrade will likely land **during** the
build, competing with feature delivery — the worst possible time for it.

## Options considered

**A. Laravel 12, as specified in the brief.** Matches the instruction exactly. PHP `^8.2` is
satisfied. Its ecosystem is fully mature today. But it has the shortest remaining support runway of
the two, and an upgrade to 13 will very likely be needed before launch.

**B. Laravel 13.** Newest, requires PHP `^8.3` — which **exactly matches the brief's stated "PHP
8.3+" requirement**, and is satisfied by the 8.4.19 already installed. Longest runway; five months of
point releases (through v13.24.0) suggest the line is well past its initial churn. Risk: some
third-party packages may still lag. That risk is checkable — the packages in
[`../09-dependency-policy.md`](../09-dependency-policy.md) can be verified against Laravel 13 before
committing.

**C. Start on 12, upgrade to 13 during Phase 2 or 3.** Combines the compatibility of A with a
planned upgrade. In practice this means doing the upgrade while the codebase is larger and the team
is under feature pressure — strictly worse than doing it now, when the codebase is empty.

## Decision (proposed, not accepted)

**Recommend option B — Laravel 13 — subject to confirming the support windows for both lines and
verifying that the Phase 1 dependency set supports 13.**

Rationale: the cheapest moment to be on the newest supported major is before any code exists. Option
B also aligns the framework's own PHP floor (`^8.3`) with the brief's stated PHP requirement, and
with the PHP 8.4.19 already present.

If the owner prefers to hold to the brief exactly, **option A is entirely workable** — Laravel 12 is
actively maintained and the architecture in this repository is not version-specific. In that case the
upgrade to 13 should be scheduled explicitly as a Phase 2 work item rather than left implicit.

**This decision is required before the first line of Phase 1 code.**

### Verification checklist before acceptance
- [ ] Confirm Laravel 12 and 13 bug-fix and security-fix end dates from Laravel's published support
      policy
- [ ] Confirm the Phase 1 dependency set is compatible with the chosen major
- [ ] Confirm the target PHP version for production images (8.3 or 8.4) and enable `bcmath`
      (missing in this environment — E5)

## Consequences

**If B (Laravel 13):** longest runway; small risk of a lagging package, checkable now; PHP floor
`^8.3` matches the brief.

**If A (Laravel 12):** exactly as briefed, maximum ecosystem maturity today; a framework upgrade
almost certainly lands mid-build and should be scheduled rather than discovered.

Either way, the architecture, bounded contexts, database strategy and API contracts in this
repository are unaffected — the decision is contained to `services/core-api`.
