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
| **Current phase** | **STEP 1 — Product Decisions** |
| **Status** | **WAITING FOR PRODUCT OWNER DECISIONS** |
| **Phase 3 blockers** | **NONE REMAINING** |
| **Phase 4 blockers** | **PD-035** (legal classification) · **PD-096 / PD-097 / PD-098** recorded, phase not yet assigned. ~~structural part of PD-013 / PD-049~~ **RESOLVED 2026-08-16 — PD-049 Q1 APPROVED** |
| **Legal research** | **PRIMARY-SOURCE ACCESS UNAVAILABLE FROM CURRENT CLAUDE ENVIRONMENT** (B-009, L-012) |
| **STEP 2** | **NOT STARTED** |
| **Phase 0** | **COMPLETE** — final gate passed, human-confirmed 2026-08-13 |
| **Last updated** | 2026-08-16 (PD-099 concurrency sub-decision approved) |
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
- [x] **343 requirements** written (214 P0 · 101 P1 · 22 P2 · 6 P3) with priority reasoning —
      283 functional and 60 non-functional; counts derived by counting rows, not estimated.
      Includes **FR-TER-\*** (PD-004), **FR-OPR-\*** (PD-008), **FR-PLT-\*** (PD-092), and
      **FR-TRV-\*** (PD-095).
- [x] **Acceptance criteria in GIVEN/WHEN/THEN form for every P0 requirement** — 220 criteria
      covering all 214 P0 requirements; coverage verified programmatically, none missing.
- [x] **30 edge cases** defined across every critical domain.
- [x] **26 user and operator flows** specified with happy, alternate, and failure paths —
      UF-26 (travelling subscriber) added when PD-095 was approved.
- [x] Feature × platform × priority matrix produced for all six client platforms plus
      admin and backend.
- [x] **99 product decisions recorded** — none silently chosen — of which **5 are APPROVED
      (PD-004, PD-007, PD-008, PD-092, PD-095)**, **one is half-approved (PD-049 Q1)**,
      **22 remain blocking**, and **24 require legal verification**
      (counts corrected 2026-08-13 from the estimates first published, and re-derived
      2026-08-16 after PD-096/097/098 were added; see `DECISIONS.md` register summary.
      Blocking and legal counts are unchanged because the three new decisions carry no
      flag — the phase they gate depends on PD-035, which is unresolved).
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
- [x] **PD-008 APPROVED and propagated** (2026-08-13): **Option A — single-tenant KMS TV**,
      one operator, one product. Multi-tenancy, white-label, and SaaS operator platform are
      **OUT OF SCOPE**. Recorded in `DECISIONS.md`, `DECISION_BRIEF.md`, `PRODUCT_SPEC.md`
      §1.2, `REQUIREMENTS.md` §A29 (FR-OPR-01…06), `USER_FLOWS.md`, `FEATURE_MATRIX.md`,
      and `docs/legal/DECISION_LOG.md` L-010. **TENANCY ≠ TERRITORY** stated explicitly
      wherever both appear.
- [x] **PD-092 APPROVED and propagated** (2026-08-13): **Option B** — v1.0 ships **Web +
      Android + Android TV**; iOS/iPadOS, Samsung Tizen, LG webOS follow in v1.x, exact
      version numbers not fixed. Four additional binding principles recorded: launch
      principle, quality principle (ten gates), **platform-neutral API**, and **fifteen
      server-authoritative domains**. Recorded in `DECISIONS.md`, `DECISION_BRIEF.md`,
      `PRODUCT_SPEC.md` §1.3, `REQUIREMENTS.md` §A30 (FR-PLT-01…09), and
      `FEATURE_MATRIX.md` §0.4.
- [x] **PD-095 APPROVED and propagated** (2026-08-13): **Option A — subscription follows the
      subscriber.** An active subscription is **never a universal content license**; current
      territory, service availability, and content rights remain authoritative. **Roaming
      duration, country lists, and location-detection technology are NOT DEFINED**, and the
      approval explicitly prohibits inventing them. Recorded in `DECISIONS.md`,
      `DECISION_BRIEF.md`, `PRODUCT_SPEC.md` §2.3.2, `REQUIREMENTS.md` §A31 (FR-TRV-01…07),
      `USER_FLOWS.md` UF-26, `FEATURE_MATRIX.md` §12, and `docs/legal/DECISION_LOG.md`
      L-011.
- [x] **PD-035 primary-source legal research attempted 2026-08-16 — BLOCKED, and recorded
      as blocked.** External HTTPS access to the required official sources (**Matsne** and
      **ComCom**) was denied by the environment network policy. **No bypass was attempted.**
      **Zero legal facts were verified from a primary source, and none was invented to fill
      the gap.** Recorded in `docs/legal/DECISION_LOG.md` **L-012**, `DECISIONS.md` PD-035,
      and §5 **B-009** below. The problem is **source accessibility** — it is not a claim
      that the sources do not exist or that the law is unknown.
- [x] **PD-096, PD-097, PD-098 recorded as OPEN** (2026-08-16) in `DECISIONS.md` §4, arising
      from PD-035: behaviour for an asset unrated in a territory's scheme; which scheme
      governs a travelling subscriber's maturity limit; whether warnings/descriptors are a
      KMS TV product requirement in addition to legal requirements. **None was resolved, and
      no recommendation was offered for any of them.**
- [x] **Classification model recorded as a capability requirement only** (D-025) — nine
      representable attributes, selecting no scheme and asserting no legal obligation — and
      the **verified-fact / product-requirement / legal-interpretation separation** recorded
      as a binding documentation rule (D-026).
- [x] **PD-049 Q1 APPROVED and propagated** (2026-08-16): **a KMS TV account may hold more
      than one commercial grant simultaneously.** The model must not assume
      `1 account = 1 grant`; the conceptual shape is
      `1 account → 0..N commercial grants → effective entitlements → playback authorization`.
      **Structural only** — add-on sale at launch, tier names, prices, package contents, PPV,
      and promotions are all **NOT decided**. The **identifier PD-049 was preserved** and
      split into `Q1` (APPROVED) and `Q2` (OPEN) sub-entries; **no new decision ID was
      created for Q1** and nothing was renumbered. Recorded in `DECISIONS.md` §2,
      `PRODUCT_SPEC.md` §13.4/§13.5/§12.1/§2.7, `REQUIREMENTS.md` §A11 (FR-PKG-07,
      FR-PKG-08), and §7 D-028/D-029/D-030 below. **Adds no twelfth authorization check.**
      Compatibility with PD-004, PD-008, PD-092 and PD-095 verified — all **PASS**, and none
      of the four was modified.
- [x] **PD-099 recorded as OPEN** (2026-08-16): the deterministic entitlement-resolution
      rules that coexisting grants now require — quality limits, concurrency limits, device
      limits, content entitlements, territory eligibility, effective dates, and conflicting
      allowances. **No rule was chosen**, and Phase 4 must not encode one.
- [x] **PD-099 concurrency sub-decision APPROVED** (2026-08-16): **KMS TV uses an
      account-level concurrency pool**, and **effective commercial concurrency =
      MAX(applicable grant allowances)** — **allowances are NOT summed** (2 + 1 + 4 → **4**,
      not 7). **Independent constraints remain authoritative**, including rights-agreement
      concurrency limits, so a commercial allowance is never guaranteed usable concurrency
      for every asset. **PD-099 overall remains OPEN** — this is **one dimension of seven**,
      recorded as a **sub-decision under the existing PD-099 identifier**; **no new decision
      ID was created**. Recorded in `DECISIONS.md` §2, `PRODUCT_SPEC.md` §6.7/§12.1/§13.4,
      `REQUIREMENTS.md` FR-PKG-08, `USER_FLOWS.md` UF-20B, and §7 **D-032** below.
      **No concurrency value was set (PD-040 still OPEN), no anti-fraud mechanism was
      introduced, and PD-042 and PD-049 Q1 are unchanged.**
- [x] **PD-007 APPROVED and propagated** (2026-08-16): **Option A — no PPV at launch.**
      **KMS TV will not sell individual titles or events separately at launch.** Pay-per-view,
      one-time title purchase, one-time event purchase, transactional purchase flow and
      rental flow are **out of scope at launch**. This is a **launch-scope** decision and
      **not a permanent prohibition** — **future PPV remains possible as a separately
      approved future commercial capability.** The future-compatibility requirement is
      **already satisfied by PD-049 Q1** (`0..N` grants), so **no preparatory work is
      permitted and none was done**. Recorded in `DECISIONS.md` §2, `PRODUCT_SPEC.md` §2.5
      and **§13.6** (launch commercial scope), `USER_FLOWS.md` Appendix B,
      `docs/legal/DECISION_LOG.md` **L-013**, and §7 **D-031** below. **No PPV requirement,
      acceptance criterion, user flow, feature-matrix row, or implementation was created**,
      and **PD-049 Q1, PD-049 Q2, PD-099, PD-013 and PD-057 are all unchanged.**

**Explicitly NOT done in Phase 1**, by instruction: no application code, no Laravel, no
React/Next.js, no database migrations or schema, no API implementation, no IPTV, EPG,
streaming, or FFmpeg functionality, no Android, iOS, Tizen, or webOS code, no payment
integration, and no dependency installation. The Phase 1 commit contains markdown only.

---

## 4. Current work

**STEP 1 — Product Decisions. Status: WAITING FOR PRODUCT OWNER DECISIONS.**

Phase 1 deliverables are written: `docs/product/PRODUCT_SPEC.md`, `REQUIREMENTS.md`,
`USER_FLOWS.md`, `FEATURE_MATRIX.md`, and `DECISIONS.md`. Work is now **decision-gated, not
production-gated** — the documents cannot progress further without answers only the product
owner can give.

Phase 1 will be marked **COMPLETE** only by explicit human confirmation after review, and
**only once the blocking decisions it surfaced are resolved.** **PD-004, PD-008, PD-092, and
PD-095 are APPROVED.** **Phase 3 has no remaining blocking decisions.**

**Phase 4 blockers:**

| Blocker | Nature |
|---|---|
| **PD-035** — legal classification scheme | OPEN — **LEGAL REVIEW REQUIRED**. Primary-source research is blocked (B-009 / L-012). |
| ~~**Structural part of PD-013 / PD-049**~~ | ✅ **RESOLVED 2026-08-16 — PD-049 Q1 APPROVED.** The account→grant relation is `0..N`. **PD-013** and **PD-049 Q2** are catalogue and commercial questions for **Phase 12** and no longer gate Phase 4. |
| **PD-096 / PD-097 / PD-098** | OPEN — arising from PD-035. **Phase assignment deliberately not made**, because which phase they gate depends on how PD-035 resolves. |

**Not a Phase 4 blocker: PD-099** (entitlement resolution rules). It gates **Phase 14**
(entitlement engine). Phase 4 may model coexisting grants but **must not encode any
particular resolution rule** — the rules are behaviour, not structure.

**Legal research blocker: PRIMARY-SOURCE ACCESS UNAVAILABLE FROM CURRENT CLAUDE
ENVIRONMENT** — see B-009 and `docs/legal/DECISION_LOG.md` L-012.

Per `CLAUDE.md` §22, work does not advance automatically. **STEP 2: NOT STARTED.**

---

## 5. Blockers

**None blocking the production of the Phase 1 specification** — it required analysis and
documentation, both fully supported by the environment.

**Blocking the Phase 1 gate and downstream phases:** **22** open product decisions flagged
`[BLOCKING]` in `docs/product/DECISIONS.md` §12 — count re-derived 2026-08-16 by counting
flagged headings, correcting a stale figure of 21 in this section. The three that gate
Phase 3 and should be answered first are:

| ID | Decision | Gates |
|---|---|---|
| ~~PD-004~~ | ~~Target territories~~ | ✅ **APPROVED 2026-08-13** — Georgia launch, multi-territory architecture, future territories configurable |
| ~~PD-008~~ | ~~Multi-tenancy~~ | ✅ **APPROVED 2026-08-13** — Option A, single-tenant, one operator. Multi-tenancy, white-label, and SaaS operator platform out of scope |
| ~~PD-092~~ | ~~Launch platform scope~~ | ✅ **APPROVED 2026-08-13** — Option B. v1.0 = Web + Android + Android TV |
| ~~PD-095~~ | ~~Travelling-subscriber policy~~ | ✅ **APPROVED 2026-08-13** — Option A, subscription follows the subscriber; current territory authoritative |

### Legal research blocker — active

| ID | Blocker | Status |
|---|---|---|
| **B-009** | **PD-035 primary-source legal research cannot be performed from this environment.** "Primary-source legal research for PD-035 could not be completed from the current Claude environment because external HTTPS access to the required official sources was denied by the environment network policy. No bypass was attempted." Required primary sources: **Matsne** and the **Georgian Communications Commission / ComCom**. | **LEGAL RESEARCH BLOCKED — EXTERNAL SOURCE ACCESS REQUIRED** |

**This is a source *accessibility* problem.** It is **not** a claim that the sources do not
exist, and **not** a claim that Georgian law on this subject is unknown. The sources are
official and exist; they could not be read from here. Denial was a general egress
allow-list restriction — a neutral control host was refused identically — not a restriction
aimed at these sources. Full record: `docs/legal/DECISION_LOG.md` **L-012**.

**Resolution requires one of:** allowing `matsne.gov.ge` and `comcom.ge` in the
environment's network policy and re-running the research; supplying the official texts
directly with their URL, publication/version identifier, and retrieval date; or engaging
qualified Georgian counsel with direct source access. **Blocks:** Phase 4 classification
modelling, and the resolution of PD-035, PD-096, PD-097, PD-098.

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
| K-008 | Info | **NON-BLOCKING DOCUMENTATION OBSERVATION** — `DECISIONS.md` ambiguity **A-08** is still not marked **RESOLVED**, although PD-092 (approved 2026-08-13) supplies the launch platform scope A-08 was raised about. A-02 was marked RESOLVED when PD-004 was approved; A-08 was not given the same treatment. Recorded 2026-08-16 in `DECISIONS.md` §13.1 as **DO-01**. **Deliberately not fixed.** |
| K-009 | Info | **NON-BLOCKING DOCUMENTATION OBSERVATION** — the PD-008 supersession sentence in `docs/product/DECISION_BRIEF.md` enumerates §5–7 and §8–20 but omits **§4** and **§21**, leaving their supersession status unstated. Recorded 2026-08-16 in `DECISIONS.md` §13.1 as **DO-02**. **Deliberately not fixed.** |

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
| D-018 | 2026-08-13 | **PD-008 APPROVED · FINAL — Option A, single-tenant KMS TV. One operator, one product.** Multi-tenancy, white-label, and SaaS operator platform are **OUT OF SCOPE**. No tenant concept, no `tenant_id`, no tenant abstractions, no speculative multi-tenancy infrastructure. | **Accepted — product owner decision** | Second product decision taken, and **stricter than the Option-B recommendation** in `DECISION_BRIEF.md`. Closes the register's only rewrite-class risk by decision rather than by migration path. Recorded in `docs/legal/DECISION_LOG.md` L-010. |
| D-019 | 2026-08-13 | **TENANCY ≠ TERRITORY** is stated explicitly wherever both appear. Single-tenancy does not constrain territory support; territory support introduces no tenancy. | **Accepted — consequence of D-018** | One operator serving several territories is the approved model and requires no tenancy concept. Conflating them would either block PD-004's multi-territory requirement or smuggle tenancy in under a territory label. |
| D-020 | 2026-08-13 | **PD-092 APPROVED · FINAL — Option B. v1.0 ships Web + Android + Android TV; iOS/iPadOS, Samsung Tizen, LG webOS follow in v1.x.** Exact version numbers not fixed. | **Accepted — product owner decision** | Third product decision, and the one that **confirms** its recommendation rather than narrowing or extending it. **All three Phase 3 blocking decisions are now approved.** |
| D-021 | 2026-08-13 | **The backend API is platform-neutral.** No platform-specific business API paths. All clients consume one shared versioned API; platform-specific behaviour lives only in the client/player layer. | **Accepted — consequence of D-020** | Per-platform business APIs fork business logic by client, which is how six clients end up with six subtly different entitlement behaviours. Recorded now so Phase 5 designs one API, not six. |
| D-023 | 2026-08-13 | **PD-095 APPROVED · FINAL — Option A, subscription follows the subscriber.** Current territory, service availability, and content rights remain authoritative. Roaming duration, country lists, and location-detection technology are **NOT DEFINED**. | **Accepted — product owner decision** | Fourth product decision. Establishes that **subscription ownership is separate from content territory rights** — an active subscription is never a universal content license. Recorded in `docs/legal/DECISION_LOG.md` L-011. |
| D-024 | 2026-08-13 | Playback authorization gains **no twelfth check**. PD-095's eight required inputs map onto the existing eleven; the approval settles that checks 6, 9, and 11 evaluate the **current** territory, determined server-side. | **Accepted — consequence of D-023** | Resisting a check-count increase matters: the eight inputs were already covered, and inflating the count would imply new machinery where only a clarification was needed. |
| D-022 | 2026-08-13 | **Fifteen domains remain server-authoritative and platform-independent** and are never duplicated inside a client: authentication, authorization, users, profiles, devices, sessions, channels, EPG, packages, subscriptions, entitlements, rights, playback authorization, payments, account state. | **Accepted — consequence of D-020** | Extends `CLAUDE.md` §4.2 from entitlement alone to the full list. A rule duplicated in a client is a rule that will drift from the server's. |
| D-025 | 2026-08-16 | **The classification model must be *capable of representing*:** classification scheme · rating value · territory · `effective_from` · `effective_until` · authority/source reference · verification status · warnings/descriptors · parental-control policy. **This is a PRODUCT / ARCHITECTURAL capability requirement, not a legal conclusion.** It selects no classification scheme, asserts no legal obligation, defines no database schema, and does not resolve PD-035. | **Accepted — engineering design decision** | The *shape* of the model is stable across whichever scheme is ultimately determined, so Phase 4 structural work is not blocked on the legal answer — only its data is. Two elements are forced by decisions already approved rather than by any law: **territory** and **classification scheme** by PD-004 (multi-territory, configurable), and **verification status** by `CLAUDE.md` §1 and §24, which forbid treating unverified data as fact — the project's own classification knowledge is currently unverified, so a model that cannot represent that distinction would violate the constitution in its own structure. |
| D-026 | 2026-08-16 | **"Verified legal facts, product requirements, and legal interpretations must remain explicitly separated."** An unverified legal assumption MUST NOT be converted into a product requirement. Where a product requirement exists for product reasons, it is recorded as such and not attributed to law. **Search-index results and search-engine summaries are not verified legal evidence** and may be used only to identify candidate official documents to retrieve. | **Accepted — binding documentation rule** | Restates `CLAUDE.md` §1 and §24 for the specific failure mode this project is exposed to: a plausible-sounding legal statement acquires the authority of a requirement simply by being written in a requirements document. Mirrored in `docs/legal/DECISION_LOG.md` L-012. |
| D-027 | 2026-08-16 | **PD-096, PD-097 and PD-098 recorded as OPEN, with no `[BLOCKING]` or `[LEGAL]` flag and no phase assignment.** | **Accepted — recording decision only** | All three arise from PD-035. Which phase each gates, and whether each needs legal verification in its own right, depends on how PD-035 resolves; assigning a flag now would be a determination, and none has been made. They are listed in `DECISIONS.md` §12 beneath the phase table so they are not lost, and must be assigned or explicitly marked non-blocking when PD-035 is decided. |
| D-028 | 2026-08-16 | **PD-049 Q1 APPROVED · structural — a KMS TV account MAY hold more than one commercial grant simultaneously.** The model MUST NOT assume `1 account = exactly 1 commercial grant`. Conceptual shape: `1 account → 0..N commercial grants → effective entitlements → playback authorization`. **Structural only:** no add-on sale, tier name, price, package content, PPV, or promotion is approved. | **Accepted — product owner decision** | The structural half of PD-049 was the only part that gated Phase 4, and it is answerable with zero commercial input. Settling it now lets the Phase 4 ERD model the account→grant relation once, instead of restructuring checks 4 and 5 — the most heavily negative-tested path in the product — after the fact. The commercial half (Q2) remains open precisely because the specification contains no market input to decide it. |
| D-029 | 2026-08-16 | **Playback authorization gains no twelfth check.** The eleven checks in `PRODUCT_SPEC.md` §12.1 are unchanged. Multiple grants change what checks 4 (package) and 5 (entitlement) evaluate **over** — a set rather than a single grant — not which checks run. | **Accepted — consequence of D-028** | Same reasoning as D-024 for PD-095: the approval is absorbed by existing machinery, and inflating the check count would imply new enforcement where only the input cardinality changed. It also preserves §3.1.5's rule that *"Tier must never alter which checks run — only their outcome."* |
| D-032 | 2026-08-16 | **PD-099 concurrency sub-decision APPROVED — account-level concurrency pool; effective commercial concurrency = MAX(applicable grant allowances). Grant allowances are NOT summed** (2 + 1 + 4 → **4**, not 7). Independent constraints remain authoritative, including **rights-agreement concurrency limits**, so a commercial allowance is never guaranteed usable concurrency for every asset. **PD-099 overall remains OPEN** — one dimension of seven. | **Accepted — product owner decision** | Recorded as a **sub-decision under the existing PD-099 identifier**, not a new decision ID, so the register keeps one entry per question. MAX rather than SUM is what preserves the anti-sharing model: under a summing rule, stacking cheap grants would buy stream capacity and convert §6.8's abuse control into a commodity. It also settles the ambiguity this review found in §6.7 — *"the most restrictive applicable constraint"* was written when there was one package, and it now reads as governing the **commercial-vs-independent** comparison, with MAX deriving the commercial side across grants. **No concurrency value was set (PD-040 remains OPEN), no anti-fraud mechanism was introduced, and PD-042 is untouched.** |
| D-031 | 2026-08-16 | **PD-007 APPROVED · Option A — no PPV at launch.** KMS TV will not sell individual titles or events separately at launch. **Launch scope only — not a permanent prohibition**; future PPV remains possible as a separately approved future commercial capability. **No PPV purchase flow, billing, entitlement logic, rental logic, UI, admin tooling, reporting, payment flow, or refund logic may be built — including as preparation.** | **Accepted — product owner decision** | Fifth fully-approved product decision. It costs nothing in future optionality because **PD-049 Q1 already satisfies the future-compatibility requirement**: a future PPV grant would be an asset-scoped, one-off commercial grant inside the approved `0..N` model, so preserving the capability requires **no work at all**. Recording that explicitly is what keeps "keep it possible" from being read as "build scaffolding for it", which `PD-008`'s architectural rules prohibit. Rights consequence recorded as `docs/legal/DECISION_LOG.md` L-013: launch rights agreements need not cover transactional distribution, and future PPV would need a **separate** rights grant per L-002. |
| D-030 | 2026-08-16 | **Where several grants could each reach the same asset, the playback-authorization evidence must record which grant the decision rested on.** | **Accepted — consequence of D-028** | `CLAUDE.md` §12 requires authorization decisions to be logged "with enough detail to prove compliance to a rights holder". Under a single-grant model the grant was implicit; under `0..N` it is not. *"The account was entitled"* is not evidence a rights holder can audit. **No legal-log entry was created for this**: it makes no claim about law or about what any contract permits, and the rights-side rule it supports is already recorded as L-002. |

**Cross-register note.** D-006 ≡ PD-001, D-009 relates to PD-092, and D-011 ≡ PD-090.
The 99 product decisions surfaced in Phase 1 live in `docs/product/DECISIONS.md`; only
those with engineering consequence are duplicated here when accepted.

---

## 8. Next action

**STOP.** **STEP 2: NOT STARTED.** Status: **WAITING FOR PRODUCT OWNER DECISIONS.**

The immediate next action is **product owner decisions**, not corrections. The
specification is deliberately incomplete in exactly **94** places (99 decisions, 5 fully
approved, PD-049 half-approved), and each is a question only the product owner can answer.

**Do not begin Phase 2 (Requirements and acceptance criteria) until Phase 1 is explicitly
confirmed COMPLETE.**

**Phase 3 (System architecture): NO REMAINING BLOCKERS.** ✅ PD-004, PD-008, PD-092 and
PD-095 are all APPROVED. *(Blocker movement from PD-092: B-005 (Android SDK) is **on** the
launch critical path; B-004 (Apple toolchain) is **off** it but its procurement lead time
is unchanged; B-006 (Tizen/webOS registration) is deferred to v1.x yet remains calendar
time and should still begin in Phase 3.)*

**The single action that unblocks the most work is resolving B-009** — the legal research
blocker. Until the required official sources can be read, PD-035 cannot be answered, and
PD-096, PD-097 and PD-098 cannot be answered either, because all three depend on it. Choose
one:

1. Allow `matsne.gov.ge` and `comcom.ge` in the environment's network policy, then re-run
   the primary-source research unchanged.
2. Supply the official texts directly, with URL, publication/version identifier, and
   retrieval date, so provenance is recordable.
3. Engage qualified Georgian counsel with direct source access.

Then, in order:

1. **PD-035** — classification scheme. **OPEN — LEGAL REVIEW REQUIRED.** Gates Phase 4 and
   the three decisions below.
2. ~~**PD-013 / PD-049** — the structural part.~~ ✅ **RESOLVED — PD-049 Q1 APPROVED
   2026-08-16.** PD-035 is now the **only** remaining Phase 4 blocker.
3. **PD-096 / PD-097 / PD-098** — resolvable only after PD-035, and each must be assigned a
   phase or explicitly marked non-blocking at that time.
4. **PD-001 / D-006** — the pre-existing scaffold, settled before any code lands beside it
   in Phase 7.

**Now scheduled rather than blocking:** **PD-013**, **PD-014** and **PD-049 Q2** move to
**Phase 12** (packages and subscriptions); **PD-099** moves to **Phase 14** (entitlement
engine). None of them gates Phase 4. ~~**PD-007** (transactional / PPV) remains OPEN~~
✅ **APPROVED 2026-08-16 — Option A, no PPV at launch.** It never gated Phase 4 and does not
now; should PPV be reintroduced by a future decision, that decision would gate the
commercial implementation phase at that time. **No new phase was created.**

---

## 9. Phase ledger

Status values: `NOT STARTED` · `IN PROGRESS` · `BLOCKED` · `COMPLETE`
A phase moves to `COMPLETE` only when all eight gate conditions in `CLAUDE.md` §22 hold.

| Phase | Name | Status |
|---|---|---|
| 0 | Environment and project foundation | **COMPLETE** (2026-08-13) |
| 1 | Product specification | **IN PROGRESS — WAITING FOR PRODUCT OWNER DECISIONS.** Deliverables written; **PD-004, PD-007, PD-008, PD-092, PD-095 APPROVED**; **PD-049 Q1 APPROVED (structural)**; **PD-099 concurrency dimension APPROVED (6 of 7 dimensions still open)**; 22 blocking decisions open; **B-009 legal research blocked** |
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
