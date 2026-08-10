# Dependency and Licence Policy

## 1. Policy

1. **Nothing is added without a stated need.** A dependency is a permanent liability: it must be
   patched, audited, and eventually removed. "It is the standard choice" is not a reason.
2. **Licence is verified before use**, from registry metadata or the project's own licence file —
   never from memory or a blog post.
3. **Copyleft is assessed by how we use it**, not by reputation. LGPL/GPL for a server-side tool we
   do not distribute is a different question from the same licence inside a shipped mobile app.
4. **Versions are pinned** — exact for applications (lockfiles committed), ranged only for
   libraries we publish internally.
5. **Every dependency has a named owner** in `CODEOWNERS` terms and a reason recorded in the PR.
6. **Supply chain is a security boundary**: lockfile integrity, `composer audit` / `pnpm audit` in
   CI, and dependency updates reviewed rather than auto-merged for anything on the playback,
   payment, or key-handling paths.
7. **Transitive dependencies count.** A small package with 40 transitive dependencies is not small.

## 2. Verified inventory

Read directly from `repo.packagist.org` and `registry.npmjs.org` on **2026-08-10**, in the
environment described in [`00-inspection-report.md`](00-inspection-report.md). These are current
latest-stable figures at that date, recorded so future decisions start from facts rather than
assumptions. **Presence in this table is not adoption** — it means the option was checked.

### PHP (Packagist)

| Package | Latest stable | PHP constraint | Licence | Status |
|---|---|---|---|---|
| `laravel/framework` | v13.24.0 (2026-08-04) | `^8.3` | MIT | **Decision pending — ADR-0007** |
| `laravel/framework` (12.x line) | v12.65.0 (2026-08-05) | `^8.2` | MIT | Requested in the brief — see ADR-0007 |
| `laravel/sanctum` | v4.3.3 | `^8.2` | MIT | Candidate — first-party token auth |
| `laravel/passport` | v13.7.5 | `^8.2` | MIT | Candidate — OAuth2 server |
| `league/oauth2-server` | 9.4.1 | `~8.2 – ~8.5` | MIT | Underlies Passport |
| `laravel/horizon` | v5.48.2 | `^8.0` | MIT | Likely — queue supervision |
| `laravel/octane` | v2.18.0 | `^8.1.0` | MIT | Evaluate for playback-authorizer only |
| `laravel/pennant` | v1.24.0 | `^8.1` | MIT | Candidate — feature flags |
| `laravel/telescope` | v5.22.0 | `^8.0` | MIT | Non-production only |
| `laravel/reverb` | v1.11.0 | `^8.2` | MIT | Only if realtime push is needed |
| `laravel/cashier` | v16.7.0 | `^8.1` | MIT | **Unlikely** — assumes a specific PSP and its subscription model; see ADR-0009 |
| `laravel/pint` | v1.30.4 | `^8.2.0` | MIT | Likely — formatting |
| `larastan/larastan` | v3.10.0 | `^8.2` | MIT | Likely — static analysis |
| `pestphp/pest` | v5.0.4 | `^8.4` | MIT | Candidate — testing |
| `rector/rector` | 2.6.1 | `^7.4 \|\| ^8.0` | MIT | Candidate — automated refactoring |
| `spatie/laravel-permission` | 8.3.0 | `^8.3` | MIT | Candidate — **staff RBAC only**, not viewer entitlements |
| `spatie/laravel-data` | 4.23.0 | `^8.1` | MIT | Candidate — typed DTOs |
| `league/flysystem-aws-s3-v3` | 3.35.2 | `^8.0.2` | MIT | Likely — object storage port |
| `aws/aws-sdk-php` | 3.391.1 | `>=8.1` | Apache-2.0 | Only behind a port |
| `lcobucci/jwt` | 5.6.0 | `~8.2 – ~8.5` | BSD-3-Clause | Candidate — playback/licence tokens |
| `predis/predis` | v3.5.1 | `^7.2 \|\| ^8.0` | MIT | Not needed — `ext-redis` is present and faster |
| `brick/money` | 0.14.1 | `^8.2` | MIT | Candidate — money type |
| `moneyphp/money` | v4.9.0 | `~8.1 – ~8.5` | MIT | Alternative money type |
| `symfony/messenger` | v8.1.4 | `>=8.4.1` | MIT | Alternative to Laravel queues — evaluate only if needed |
| `dedoc/scramble` | v0.13.39 | `^8.1` | MIT | Code-first OpenAPI — **rejected**, see ADR-0010 |
| `zircote/swagger-php` | 6.5.2 | `>=8.2` | Apache-2.0 | Annotation-driven OpenAPI — **rejected**, see ADR-0010 |
| `php-open-source-saver/jwt-auth` | 2.9.2 | `^8.3` | MIT | Community fork — noted, not preferred |

`laravel/scout` could not be retrieved (connection reset during lookup) and is therefore **not**
recorded as verified.

### JavaScript / TypeScript (npm)

| Package | Latest | Licence | Node engines | Status |
|---|---|---|---|---|
| `next` | 16.3.0 | MIT | `>=20.9.0` | Web + admin apps |
| `react` / `react-dom` | 19.2.8 | MIT | — | With Next |
| `typescript` | 7.0.2 | Apache-2.0 | `>=16.20.0` | **Pin explicitly** and validate against the chosen Next version before adopting a new major |
| `hls.js` | 1.6.17 | Apache-2.0 | — | Web HLS candidate |
| `shaka-player` | 5.2.4 | Apache-2.0 | `>=18` | Web DASH+HLS+multi-DRM candidate — likely primary |
| `dashjs` | 5.2.0 | BSD-3-Clause | `>=20` | Web DASH alternative |
| `video.js` | 8.23.9 | Apache-2.0 | — | Only if a UI framework is wanted on top |
| `@tanstack/react-query` | 5.101.4 | MIT | — | Candidate — server state |
| `zod` | 4.4.3 | MIT | — | Candidate — runtime validation at the API edge |
| `vitest` | 4.1.10 | MIT | `^20 \|\| ^22 \|\| >=24` | Unit tests |
| `@playwright/test` | 1.62.1 | Apache-2.0 | `>=20` | E2E; browser preinstalled in this environment |
| `eslint` | 10.8.1 | MIT | `^20.19 \|\| ^22.13 \|\| >=24` | Linting |
| `prettier` | 3.9.6 | MIT | — | Formatting |
| `tailwindcss` | 4.3.3 | MIT | — | Styling — note the v3→v4 configuration change from the existing scaffold |
| `turbo` | 2.10.9 | MIT | — | Task graph — **defer** until build times justify it |
| `nx` | 23.1.1 | MIT | — | Alternative to Turborepo — not selected |
| `openapi-typescript` | 7.13.0 | MIT | — | Spec → TS types |
| `orval` | 8.24.0 | MIT | — | Spec → typed client |
| `@redocly/cli` | 2.46.0 | MIT | — | Spec linting/bundling/docs |
| `@stoplight/spectral-cli` | 6.16.3 | Apache-2.0 | — | Spec style rules in CI |
| `msw` | 2.15.0 | MIT | — | Network-level fakes for client tests |
| `@types/node` | 26.2.0 | MIT | — | |
| `@supabase/supabase-js` | 2.112.2 | MIT | — | **Present in the existing scaffold. Not part of this architecture** — see OQ-17 |

Node 22.22.2 in this environment satisfies every engine constraint above.

### Media toolchain (licence text read from each project's repository)

| Tool | Licence position (verbatim from the project's own licence file) | Implication |
|---|---|---|
| **FFmpeg** | *"Most files in FFmpeg are under the GNU Lesser General Public License version 2.1 or later (LGPL v2.1+) … Some optional parts of FFmpeg are licensed under the GNU General Public License version 2 or later (GPL v2+) … None of these parts are used by default, you have to explicitly pass `--enable-gpl` to configure to activate them. In this case, FFmpeg's license changes to GPL v2+."* | **The build configuration determines the licence.** Server-side use we do not distribute is low-risk either way, but any FFmpeg code shipped inside a mobile or TV application, or in a publicly distributed container image, must be reviewed by counsel. **Record the exact build flags of the FFmpeg image we use.** |
| **Shaka Packager** | Google, 2014 — three-clause BSD-style permissive terms (redistribution in source and binary permitted with notice retention) | Permissive; suitable. Confirm the current file for any change before adoption. |
| **Bento4** | *"Bento4 is available under two different licenses … For applications that are entirely distributable under the terms of the GPL, the Bento4 GPL license applies. For applications that cannot be entirely distributable under the terms of the GPL … a non-GPL commercial license is available from Axiomatic Systems LLC."* | **Dual GPL/commercial.** Using it in a proprietary platform likely requires a commercial licence. Legal review required before adoption. |

## 3. Explicitly rejected or restricted

| Item | Position | Reason |
|---|---|---|
| `@supabase/supabase-js` and Supabase generally | Not used | Conflicts with the stated direction (Laravel + self-managed PostgreSQL + our own auth). Two systems of record for identity is a security and correctness hazard |
| Code-first / annotation-driven OpenAPI generators | Not used for the client API | The spec is the contract and must be reviewable independently of implementation — ADR-0010 |
| Any package under AGPL | Requires legal sign-off before evaluation | Network-use obligations are incompatible with a proprietary SaaS platform without deliberate legal review |
| Any package with no licence declared | Prohibited | No licence means no permission |
| Unmaintained packages (no release in 24 months, or unresolved security advisories) | Prohibited without an accepted risk record | |
| DRM client SDKs | Legal review before any commit | Redistribution terms are typically restrictive and specific |

## 4. Adding a dependency

1. State the need and the alternative of not adding it.
2. Verify latest stable version, licence, maintenance status, and transitive dependency count from
   the registry.
3. Confirm the licence is compatible **with how we will use it** (server-side vs distributed client).
4. Add it to the correct workspace — never to the repository root.
5. Record it in this document with its status and owner.
6. Confirm CI's audit steps pass with it in the lockfile.

## 5. Review cadence

- **Weekly:** automated vulnerability scan; anything critical on the playback, payment, or key paths
  is handled immediately.
- **Monthly:** review of pending updates; batch low-risk, individually review high-risk.
- **Quarterly:** full inventory review — remove what is no longer used, re-verify licences, retire
  unmaintained packages.
- **Before each major release:** regenerate and archive a full dependency and licence manifest
  (SBOM) as a release artifact.
