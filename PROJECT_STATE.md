# PROJECT_STATE.md — KMS TV

> **Purpose.** This is the living state of the project. It is updated at the end of
> **every** phase, and whenever a blocker, decision, or known issue appears or clears.
> It is the first document to read when resuming work and the last to write before
> stopping. See `CLAUDE.md` §22 for the phase gate rules.

---

## Header

| Field | Value |
|---|---|
| **Project** | KMS TV |
| **Product** | Production-grade IPTV/OTT platform |
| **Current phase** | **STEP 1 / PHASE 1 — Product specification** |
| **Status** | **IN PROGRESS** |
| **Phase 0** | **COMPLETE** — final gate passed, human-confirmed 2026-08-13 |
| **Last updated** | 2026-08-13 |
| **Updated by** | Engineering agent (Claude Code) |
| **Repository** | `akminingak-cyber/kms` |
| **Working branch** | `claude/kms-tv-step-0-audit-peahvj` |
| **Default branch** | `main` |

---

## 1. Environment status

**Verdict:** Sufficient for Phases 0–2 (documentation and specification) with no
installation required. **Not yet sufficient** for Phase 7 (backend foundation) — the
Docker daemon is not running, and no database or cache server is running.

**Host**

| Property | Value |
|---|---|
| Operating system | Ubuntu 24.04.4 LTS (Noble Numbat) |
| Kernel | 6.18.5-fc-v20 |
| Architecture | x86_64 |
| CPU | Intel Xeon @ 2.80GHz — 4 vCPU, 1 thread/core, KVM guest, AVX-512 available |
| RAM | 15 GiB total, ~15 GiB available, **no swap** |
| Disk | `/` 252 G apparent, **~30 G writable allowance remaining** (20% used) |
| Privileges | root (uid 0) |
| Timezone | UTC (`TZ` unset) |
| Locale | `POSIX` (`LANG` unset) |
| Environment type | **Ephemeral remote container** — reclaimed after inactivity |

**Critical environment property:** this container is ephemeral. Anything not committed
and pushed is lost. All durable state must live in git.

**Toolchain — installed**

| Tool | Version |
|---|---|
| git | 2.43.0 |
| Node.js | 22.22.2 |
| npm | 10.9.7 |
| pnpm | 10.33.0 |
| yarn | 1.22.22 (classic) |
| bun | 1.3.11 |
| corepack | 0.34.6 |
| PHP (CLI) | **8.4.19** NTS, with OPcache |
| Composer | 2.8.12 |
| Docker CLI | 29.3.1 |
| Docker Compose (plugin) | v5.1.1 |
| PostgreSQL client (`psql`, `pg_dump`) | 16.13 |
| redis-cli / redis-server binary | 7.0.15 |
| OpenJDK (java, javac) | 21.0.10 |
| Gradle | 8.14.3 |
| Python | 3.11.15 (pip 24.0) |
| Go | 1.24.7 |
| Rust (cargo) | 1.94.1 |
| curl | 8.5.0 |
| wget | 1.21.4 |
| make | 4.3 (GNU) |
| bash | 5.2.21 |
| ripgrep | 14.1.0 |
| jq | 1.7 |
| OpenSSL | 3.0.13 |
| zip / unzip | 3.0 / 6.00 |

**Global npm packages available:** eslint 10.1.0, prettier 3.8.1, typescript 6.0.2,
ts-node 10.9.2, playwright 1.56.1, chromedriver 147, http-server, serve, nodemon.

**PHP extensions — present:** ctype, curl, dom, fileinfo, filter, gd, hash, iconv,
igbinary, intl, json, mbstring, openssl, pcntl, pcre, PDO, pdo_pgsql, pdo_mysql,
pdo_sqlite, pgsql, Phar, posix, random, readline, **redis**, session, SimpleXML, sockets,
sodium, sqlite3, tokenizer, xml, xmlreader, xmlwriter, xsl, OPcache, zip, zlib.

**PHP extensions — missing:** `bcmath` (apt candidate `8.4.19-1+ubuntu24.04.1` available).

**Services — not running**

| Service | State |
|---|---|
| Docker daemon | **Not running.** `dockerd` binary present at `/usr/bin/dockerd`; `/var/run/docker.sock` absent. CLI cannot connect. |
| PostgreSQL server | **Not installed.** Client tools only. `pg_isready` → no response on `:5432`. |
| Redis server | **Not running.** Binary present; connection to `127.0.0.1:6379` refused. |
| Nginx | Not installed. |
| php-fpm | Not installed (PHP CLI only). |

**Network egress** (through the configured agent proxy)

| Target | Result |
|---|---|
| `repo.packagist.org` | HTTP 200 — Composer installs expected to work |
| `registry.npmjs.org` | HTTP 200 — npm/pnpm installs expected to work |
| `github.com` / `api.github.com` | HTTP 400 on direct curl — direct HTTP to GitHub is not the supported path. Git operations use the configured git proxy; GitHub API work uses the GitHub MCP tools. |

**Secrets handling:** environment variables were enumerated by **name only**. Names
indicating credential material are present (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`,
`GH_TOKEN`, `GITHUB_TOKEN`, `CLOUDSDK_AUTH_ACCESS_TOKEN`, and session tokens). These are
harness-injected. **No value was read, printed, or recorded.** No `.env` file exists in
the repository, and no credential-bearing file is tracked in git.

---

## 2. Repository status

| Property | Value |
|---|---|
| Remote | `https://github.com/akminingak-cyber/kms` |
| Default branch | `main` (`origin/HEAD` is **not set** on the remote) |
| Working branch | `claude/kms-tv-step-0-audit-peahvj` (exists locally and on origin) |
| Commits | 2 — `7d283f5` "Initial commit", `056cd8f` "Add files via upload" |
| Working tree at audit start | Clean |
| Tracked files at audit start | 12 |
| Repository size | 380 K `.git`, 200 K working tree |
| `.gitignore` | Present — ignores `node_modules`, `dist`, `*.log`, `.env`, editor files |
| `.gitattributes` | **Absent** |
| `.github/` CI | **Absent** |
| `docs/` | Absent at audit start; `docs/legal/` created in this phase |
| Git hooks | None installed |
| Commit identity | `Claude <noreply@anthropic.com>`, SSH commit signing enabled |

### 2.1 Pre-existing content — the repository was **not** empty

The task framing was "absolute zero", but the repository already contained an
**orphaned, non-buildable frontend scaffold** committed as "Add files via upload":

```
.gitignore  README.md  eslint.config.js  index.html  package.json
package-lock.json  postcss.config.js  tailwind.config.js
tsconfig.json  tsconfig.app.json  tsconfig.node.json  vite.config.ts
```

Findings:

1. **It cannot build.** `index.html` loads `/src/main.tsx`, and **there is no `src/`
   directory**. The scaffold has configuration and dependencies but zero source files.
2. **It is a generated starter.** `package.json` is named `vite-react-typescript-starter`
   at version `0.0.0`; `index.html` carries `bolt.new` Open Graph image tags.
3. **It contradicts the target stack.** It is **Vite + React 18**; the stated target is
   **Next.js**.
4. **It contradicts the target backend.** It declares `@supabase/supabase-js` as a
   dependency; the stated target backend is **Laravel 12 + PostgreSQL**.
5. **It describes a different product.** The `<title>` reads
   *"KMS – Enterprise Technology Infrastructure & Security"* — not an IPTV/OTT platform.
6. `node_modules` is not installed, so nothing has been executed from this tree.

**No action was taken on these files in Phase 0.** Deciding their fate is a Phase 1
decision (see *Decisions → D-006, open*). They are inert and harmless while untouched,
but they must not be mistaken for a foundation to build on.

---

## 3. Completed work

**Phase 0 — STEP 0**

- [x] Full environment audit performed across all 25 requested inspection points, plus
      streaming, web-server, mobile, and package-manager tooling.
- [x] Docker, PostgreSQL, and Redis **runtime** status verified, not just binary presence.
- [x] PHP extension inventory checked against Laravel's documented requirement set.
- [x] Network egress to Packagist and npm verified.
- [x] Git repository state, branches, remotes, config scopes, and history inspected.
- [x] Pre-existing scaffold discovered, read, and assessed (§2.1).
- [x] Secret hygiene check — no secret values read or recorded; no secrets tracked in git.
- [x] `CLAUDE.md` created — the permanent engineering constitution.
- [x] `PROJECT_STATE.md` created — this document.
- [x] `IMPLEMENTATION_PLAN.md` created — Phases 0–37 with gates.
- [x] `docs/legal/` created with the license register, content-rights policy, and
      decision log templates.

**Explicitly NOT done in Phase 0**, by instruction: no application code, no `package.json`,
no `composer.json`, no Laravel, no React, no database schemas, no Docker services, no
dependency installation, and no modification of the operating system.

**Phase 0 gate:** PASSED. Verification report delivered, final gate re-run read-only,
human confirmation received 2026-08-13. Phase 0 is **COMPLETE**.

**Phase 1 — STEP 1 (product specification)**

- [x] Product identity, goal, target users, markets, and business-model options defined,
      with every commercial and market statement marked CONFIRMED / PROPOSED / OPEN.
- [x] All viewer and operator user types defined with purpose, capabilities, restrictions,
      and security requirements.
- [x] Account, profile, and device/session behaviour specified including all flows.
- [x] Home, Live TV, channel metadata, EPG, and player experience specified.
- [x] **Playback authorization product behaviour specified** — the ten mandatory checks,
      the denial reason-code set, and the behaviour for every failure mode.
- [x] Packages, subscriptions, and payment product requirements specified.
- [x] **Rights management specified as a first-class product requirement**, including
      independent distribution modes and dual-mechanism expiry enforcement.
- [x] VOD, catch-up, restart, search, favorites, watch history, and recommendations
      specified; deterministic recommendation fallback defined (no ML).
- [x] Admin Control Center — all 25 sections — and seven admin roles specified.
- [x] Analytics separated into product / operational / security-audit systems.
- [x] Internationalization (Georgian primary, four languages, three scripts),
      accessibility, error states, platform requirements, and TV remote UX specified.
- [x] Performance targets stated as PROPOSED — REQUIRES VALIDATION; scalability
      dimensions defined with no capacity claimed.
- [x] Content and rights safety restated as binding product constraints.
- [x] **319 requirements** written (190 P0 · 101 P1 · 22 P2 · 6 P3) with priority reasoning —
      259 functional and 60 non-functional; counts derived by counting rows, not estimated.
      Includes the **FR-TER-\*** territory domain added when PD-004 was approved.
- [x] **Acceptance criteria in GIVEN/WHEN/THEN form for every P0 requirement** — 196 criteria
      covering all 190 P0 requirements; coverage verified programmatically, none missing.
- [x] **30 edge cases** defined across every critical domain.
- [x] **25 user and operator flows** specified with happy, alternate, and failure paths.
- [x] Feature × platform × priority matrix produced for all six client platforms plus
      admin and backend.
- [x] **95 product decisions recorded** — none silently chosen — of which **1 is APPROVED
      (PD-004)**, **25 remain blocking**, and **25 require legal verification** (counts
      corrected 2026-08-13 from the estimates first published; see `DECISIONS.md`
      register summary).
- [x] `docs/product/DECISION_BRIEF.md` prepared for the product owner: the three primary
      decisions analysed across 22 dimensions each, all blocking decisions summarized
      (24 at the time of writing; 25 open after the PD-004 approval added PD-094/PD-095),
      A/B/C ordering classification, and a recommended decision sequence. Every
      recommendation labelled **RECOMMENDATION — NOT APPROVED**.
- [x] **12 ambiguities in the brief itself recorded** rather than resolved by assumption —
      of which **A-02 (no target territory stated) is now RESOLVED** by the PD-004 approval.
- [x] **PD-004 APPROVED and propagated** (2026-08-13): launch territory **Georgia**,
      **multi-territory architecture**, future territories **configurable**. Recorded in
      `DECISIONS.md`, `DECISION_BRIEF.md`, `PRODUCT_SPEC.md` §2.3/§2.3.1, `REQUIREMENTS.md`
      §A28, `USER_FLOWS.md`, `FEATURE_MATRIX.md`, and `docs/legal/DECISION_LOG.md` L-008/L-009.
      The three-concept separation (app distribution / service availability / content
      rights) is now binding, and playback authorization carries an **eleventh check**.

**Explicitly NOT done in Phase 1**, by instruction: no application code, no Laravel, no
React/Next.js, no database migrations or schema, no API implementation, no IPTV, EPG,
streaming, or FFmpeg functionality, no Android, iOS, Tizen, or webOS code, no payment
integration, and no dependency installation. The Phase 1 commit contains markdown only.

---

## 4. Current work

Phase 1 deliverables are written: `docs/product/PRODUCT_SPEC.md`, `REQUIREMENTS.md`,
`USER_FLOWS.md`, `FEATURE_MATRIX.md`, and `DECISIONS.md`. Remaining Phase 1 activity:
commit and push to `claude/kms-tv-step-0-audit-peahvj`, then **STOP** and report.

Phase 1 will be marked **COMPLETE** only by explicit human confirmation after review, and
**only once the blocking decisions it surfaced are resolved.** **PD-004 is now APPROVED**;
**PD-008** (multi-tenancy) and **PD-092** (launch platform scope) still gate Phase 3, and
**PD-095** (travelling-subscriber policy, arising from PD-004) now gates Phase 4. Per `CLAUDE.md` §22, work does not advance to Phase 2 automatically.

---

## 5. Blockers

**None blocking the production of the Phase 1 specification** — it required analysis and
documentation, both fully supported by the environment.

**Blocking the Phase 1 gate and downstream phases:** 21 open product decisions flagged
`[BLOCKING]` in `docs/product/DECISIONS.md` §12. The three that gate Phase 3 and should be
answered first are:

| ID | Decision | Gates |
|---|---|---|
| ~~PD-004~~ | ~~Target territories~~ | ✅ **APPROVED 2026-08-13** — Georgia launch, multi-territory architecture, future territories configurable |
| **PD-008** | Multi-tenancy: one operator or several | Phase 3 — the most expensive decision on the register to defer; retrofitting touches every table, query, and authorization check |
| **PD-092** | Launch platform scope and order | Phase 3 — determines client sequence, certification lead times, device procurement, and whether B-004 (Apple toolchain) is on the critical path |
| **PD-095** | Travelling-subscriber policy — home vs. current territory in authorization | Phase 4 — determines whether an account carries a home territory distinct from its determined current territory |

The Phase 0 environment blockers below are unchanged and still apply.

**Blockers that will apply to later phases**, listed now so they are not discovered late:

| ID | Blocker | Blocks from | Nature |
|---|---|---|---|
| B-001 | **Docker daemon not running.** `dockerd` exists; the socket does not. | Phase 7 | Must be started, or an alternative local runtime agreed. Requires an explicit decision — Phase 0 does not modify the OS. |
| B-002 | **No PostgreSQL server.** Client tools only. | Phase 7 | Resolved by B-001 (containerized) or by installing `postgresql-16`. |
| B-003 | **No running Redis server.** Binary present, not started. | Phase 7 | Resolved by B-001 or by starting the service. |
| B-004 | **No Apple platform toolchain.** Swift and Xcode do not exist on Linux and **cannot** be installed here. | Phase 22 | Structural. iOS/iPadOS builds require macOS hardware or a hosted macOS CI runner. Must be procured. |
| B-005 | **No Android SDK.** `ANDROID_HOME` unset, no `sdkmanager`/`adb`. JDK 21 and Gradle 8.14.3 are present. | Phase 20 | Installable. Also requires a decision on emulator vs. device testing given 4 vCPU. |
| B-006 | **No Tizen Studio / webOS TV SDK.** | Phases 23–24 | Vendor SDKs plus developer-account registration with Samsung and LG. Registration lead time must be planned for. |
| B-007 | **Ephemeral container, ~30 G writable allowance.** No persistence across sessions; a full multi-platform toolchain will not fit alongside build caches. | Phase 16+ | Requires a decision on a persistent build environment and CI runners. |
| B-008 | **No FFmpeg.** | Phase 16 | Installable, but the build's license and enabled codecs must be reviewed first per `CLAUDE.md` §3.2. |

---

## 6. Known issues

| ID | Severity | Issue |
|---|---|---|
| K-001 | Medium | Orphaned, non-buildable Vite/React/Supabase scaffold in the repository (§2.1). It conflicts with the target stack and describes a different product. Unresolved, it will mislead future contributors and tooling. |
| K-002 | Low | `origin/HEAD` is not set on the remote, so the default branch is not discoverable via git alone. |
| K-003 | Low | `.gitattributes` absent. Line-ending and diff behaviour undefined — must be fixed before multi-platform client work (Phase 20+). |
| K-004 | Low | PHP `bcmath` extension missing. Needed for exact decimal arithmetic in billing (Phase 27). |
| K-005 | Low | Host locale is `POSIX` and `TZ` is unset. Harmless now; must be pinned explicitly in container images so EPG time handling is never environment-dependent. |
| K-006 | Info | No CI configuration exists. Required by `CLAUDE.md` §15 before any code phase completes. |
| K-007 | Info | No `git-lfs`. May matter if binary media test fixtures are ever versioned; avoid needing it. |

---

## 7. Decisions

| ID | Date | Decision | Status | Rationale |
|---|---|---|---|---|
| D-001 | 2026-08-13 | `CLAUDE.md` is the binding engineering constitution; conflicts resolve in its favour. | **Accepted** | Establishes one authority for quality, security, and legal rules across all phases. |
| D-002 | 2026-08-13 | Phase gates are hard. No automatic phase advancement. | **Accepted** | Prevents compounding defects across a 38-phase programme. |
| D-003 | 2026-08-13 | No software installed and no OS modification during Phase 0. | **Accepted** | Explicit instruction; also keeps the audit an honest picture of the environment as found. |
| D-004 | 2026-08-13 | Secrets audited by variable **name** only; no values read or recorded. | **Accepted** | `CLAUDE.md` §1, §6. |
| D-005 | 2026-08-13 | Rights and entitlements are separate systems from RBAC, and are enforced at playback-authorization time. | **Accepted** | Compliance must be enforced server-side; UI filtering is not enforcement. |
| D-006 | — | **Fate of the pre-existing Vite/React/Supabase scaffold** — remove, archive on a branch, or retain. | **OPEN — decide in Phase 1** | It is non-buildable and contradicts the target stack, backend, and product. Leaving it undecided guarantees future confusion. |
| D-007 | — | **Local runtime strategy** — start Docker daemon vs. install native PostgreSQL/Redis vs. use a remote development environment. | **OPEN — decide before Phase 7** | Blocks B-001/002/003. Depends on whether this ephemeral container remains the development environment. |
| D-008 | — | **PHP version target.** Environment provides 8.4.19; the stated target is 8.3+. | **OPEN — confirm in Phase 7** | 8.4.19 satisfies "8.3 or newer". The exact supported range for Laravel 12 and every intended package must be verified against official documentation before pinning — not assumed (`CLAUDE.md` §1). |
| D-009 | — | **Web framework.** Target direction is Next.js; the existing scaffold is Vite. | **OPEN — decide in Phase 3/19** | Ties to D-006. TV browsers are the binding constraint on the web client and must drive this choice. |
| D-010 | — | **Static analysis levels and coverage thresholds** (PHPStan/Larastan level, TS strictness, minimum coverage). | **OPEN — set in Phase 7** | `CLAUDE.md` §5, §13 require these recorded and monotonically non-decreasing. |
| D-011 | — | **RPO / RTO numeric targets.** | **OPEN — set before Phase 34** | `CLAUDE.md` §17 — undefined targets cannot be met. |
| D-012 | 2026-08-13 | Phase 1 records product decisions in a **separate register**, `docs/product/DECISIONS.md`, using `PD-nnn` identifiers. | **Accepted** | 93 product decisions would swamp this engineering register. Accepted `PD-nnn` decisions are mirrored back here; legal ones are mirrored to `docs/legal/DECISION_LOG.md`. |
| D-013 | 2026-08-13 | The Phase 1 specification chooses **no** open product decision on the owner's behalf; every one is recorded as OPEN or RECOMMENDED. | **Accepted** | `CLAUDE.md` §1 and the STEP 1 brief both prohibit silent selection. A recommendation with reasoning is useful; a silent default is a fabricated requirement. |
| D-014 | 2026-08-13 | The pre-existing scaffold was **left untouched** in Phase 1; no file was modified or deleted and no code was copied from it. | **Accepted** | STEP 1 was scoped to documentation only. Deleting files is a repository change requiring the owner's decision (PD-001 / D-006), not an engineering one. |
| D-015 | 2026-08-13 | **PD-004 APPROVED · FINAL — launch territory Georgia; multi-territory architecture; future territories configurable.** App distribution, service availability, and content rights are three separate concepts that MUST NOT be merged. | **Accepted — product owner decision** | The first product decision taken. Territory becomes a determinate input for PD-081, PD-035, PD-054, PD-075 and others, all of which remain [LEGAL] and open. Recorded in `docs/legal/DECISION_LOG.md` L-008 and L-009. |
| D-016 | 2026-08-13 | Playback authorization gains an **eleventh check — service availability** — evaluated separately from content rights, with a distinct denial reason code. | **Accepted — consequence of D-015** | Required by PD-004 item 15. Merging service availability into the content-rights check would make "licensed here but not served here" and "served here but not licensed here" indistinguishable; the second is how unlicensed distribution happens. |
| D-017 | 2026-08-13 | Two consequential decisions recorded rather than defaulted: **PD-094** (app distribution scope) and **PD-095** (travelling-subscriber policy, blocking Phase 4). | **Accepted** | The three-concept separation raises both; the approval answers neither. `CLAUDE.md` §1 prohibits silently choosing them. |

**Cross-register note.** D-006 ≡ PD-001, D-009 relates to PD-092, and D-011 ≡ PD-090.
The 93 product decisions surfaced in Phase 1 live in `docs/product/DECISIONS.md`; only
those with engineering consequence are duplicated here when accepted.

---

## 8. Next action

**STOP.** Phase 1 ends here, by instruction.

The immediate next action is **product owner review of the five specification documents**,
and specifically **decisions, not corrections**. The specification is deliberately
incomplete in exactly 93 places, and each is a question only the product owner can answer.

**Do not begin Phase 2 (Requirements and acceptance criteria) until Phase 1 is explicitly
confirmed COMPLETE.**

The three decisions to answer first, because they gate Phase 3 and much of Phase 4:

1. **PD-004 — target territories.** Determines rights, geo-enforcement, applicable privacy
   law, tax, payment methods, and content classification. The largest single unknown.
2. **PD-008 — multi-tenancy.** One operator or several. The most expensive decision here
   to defer; either answer is fine, silence is not.
3. **PD-092 — launch platform scope.** Determines client sequence, certification lead
   times, device procurement, and whether the Apple toolchain blocker (B-004) sits on the
   critical path.

Then **PD-001 / D-006** — the pre-existing scaffold — which should be settled before any
code lands beside it in Phase 7.

---

## 9. Phase ledger

Status values: `NOT STARTED` · `IN PROGRESS` · `BLOCKED` · `COMPLETE`
A phase moves to `COMPLETE` only when all eight gate conditions in `CLAUDE.md` §22 hold.

| Phase | Name | Status |
|---|---|---|
| 0 | Environment and project foundation | **COMPLETE** (2026-08-13) |
| 1 | Product specification | **IN PROGRESS** — deliverables written; **PD-004 APPROVED**; 25 blocking decisions open |
| 2 | Requirements and acceptance criteria | NOT STARTED |
| 3 | System architecture | NOT STARTED |
| 4 | Database and ERD | NOT STARTED |
| 5 | API and OpenAPI | NOT STARTED |
| 6 | Security and threat model | NOT STARTED |
| 7 | Backend foundation | NOT STARTED |
| 8 | Authentication and identity | NOT STARTED |
| 9 | Users, profiles, devices and sessions | NOT STARTED |
| 10 | Channels | NOT STARTED |
| 11 | EPG | NOT STARTED |
| 12 | Packages and subscriptions | NOT STARTED |
| 13 | Rights management | NOT STARTED |
| 14 | Entitlement engine | NOT STARTED |
| 15 | Playback authorization | NOT STARTED |
| 16 | Streaming infrastructure | NOT STARTED |
| 17 | Origin and CDN | NOT STARTED |
| 18 | Admin Control Center | NOT STARTED |
| 19 | Web TV | NOT STARTED |
| 20 | Android | NOT STARTED |
| 21 | Android TV | NOT STARTED |
| 22 | iOS/iPadOS | NOT STARTED |
| 23 | Samsung Tizen | NOT STARTED |
| 24 | LG webOS | NOT STARTED |
| 25 | VOD | NOT STARTED |
| 26 | Catch-up and Restart TV | NOT STARTED |
| 27 | Payments | NOT STARTED |
| 28 | Notifications | NOT STARTED |
| 29 | Analytics | NOT STARTED |
| 30 | DRM | NOT STARTED |
| 31 | Monitoring and observability | NOT STARTED |
| 32 | Load and performance testing | NOT STARTED |
| 33 | Security audit | NOT STARTED |
| 34 | Disaster recovery | NOT STARTED |
| 35 | Staging | NOT STARTED |
| 36 | Production deployment | NOT STARTED |
| 37 | Final production audit | NOT STARTED |

---

## 10. Update procedure

At the end of every phase, and whenever state materially changes:

1. Update the **Header** — current phase, status, date.
2. Update **Completed work** and **Current work**.
3. Add or clear **Blockers** and **Known issues**, with IDs retained for traceability.
4. Record any new **Decisions** with date and rationale; move `OPEN` items to `Accepted`
   or `Rejected` — never delete them.
5. Update the **Phase ledger**.
6. Set **Next action** to something specific and singular.
7. Commit this file **with** the work it describes, never separately.
