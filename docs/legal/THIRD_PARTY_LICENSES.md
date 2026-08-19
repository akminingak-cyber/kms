# Third-Party License Register — KMS TV

**Status:** Established in Phase 0. **Empty by design** — no third-party component has
been adopted yet.
**Governing document:** `CLAUDE.md` §3.

---

## 1. Intake procedure (mandatory)

Before **any** third-party source code, library, container image, font, icon set, or
media asset is incorporated, complete all six steps and record the result in §4:

1. **Identify the repository** — canonical upstream URL, exact version or commit.
2. **Identify the license** — read the actual `LICENSE` file. Not the badge. Not the
   package-registry metadata field, which is frequently wrong.
3. **Identify the dependencies** — the transitive tree and the licenses within it.
4. **Determine compatibility** with the KMS TV distribution model: a proprietary
   server-side product plus proprietary client applications distributed through app
   stores.
5. **Record the decision** in §4 with date and reviewer.
6. **Prefer adapter/integration over copying source.** Depend on it; do not absorb it.

## 2. License classification

| Class | Examples | Default position |
|---|---|---|
| **Permissive** | MIT, BSD-2/3, ISC, Apache-2.0 | Generally acceptable. Attribution obligations must still be met in every client. |
| **Weak copyleft** | LGPL, MPL-2.0, EPL | Case by case. Dynamic linking and file-level boundaries matter. Record the reasoning. |
| **Strong copyleft** | GPL-2.0, GPL-3.0 | **Not incorporated into proprietary components without explicit legal review.** Separate-process invocation is a distinct case — see §3. |
| **Network copyleft** | AGPL, SSPL | **Must not be linked into or served by KMS TV backend services** without explicit written legal approval recorded here. |
| **Non-commercial / no-license** | CC BY-NC, unlicensed repositories | **Prohibited.** No license means no permission. |

**Never copy copyleft source into proprietary components without explicit legal review.**
This includes partial copying — individual files, functions, or algorithms in source form.

## 3. Special cases requiring explicit review

### FFmpeg (Phase 16)
FFmpeg builds differ in license depending on configuration. The specific build, its
license (LGPL vs. GPL), and its enabled components must be reviewed and recorded in
`ffmpeg-license-review.md` before production use. **Builds with `--enable-nonfree`
must not be distributed.** Invoking a binary as a separate process is legally distinct
from linking its source — but distinct is not the same as unrestricted, and it must still
be reviewed.

### Codec and patent licensing
Patent licensing for H.264/AVC, H.265/HEVC, AAC, and similar technologies is **separate
from software licensing** and carries real commercial cost. Software freedom does not
imply patent freedom. This must be resolved before commercial launch (Phases 16 and 37).

### Client attribution
Dependencies bundled into distributed applications carry attribution obligations. An
attribution/notices screen must exist in **every** client application before release —
web (Phase 19), Android (20), Android TV (21), iOS (22), Tizen (23), webOS (24).

### DRM provider SDKs
Typically proprietary, with their own contractual restrictions on use, distribution, and
disclosure. Reviewed and recorded in Phase 30.

## 4. Register

**No entries. No third-party component has been adopted.**

Every future adoption is recorded as a row here before it is merged:

| ID | Component | Version | Upstream URL | License | Class | Transitive licenses reviewed | Used in | Distribution impact | Decision | Reviewer | Date |
|---|---|---|---|---|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — | — | — | — | — | — |

## 5. Automated enforcement

From Phase 7, CI includes a blocking license-compliance check (`CLAUDE.md` §15). The
check verifies that every resolved dependency carries a license in the permitted set and
appears in this register. A dependency that is not registered fails the build.

Automated checks catch the common cases. They do not replace §1 — registry metadata is
frequently inaccurate, and the actual `LICENSE` file is the authority.

## 6. Supply chain rules

- Lockfiles are committed and are the source of truth for installed versions.
- Every dependency addition is justified in its pull request description.
- Vulnerability scanning runs in CI and blocks on known critical and high findings in
  production dependencies.
- Unmaintained dependencies are flagged in `PROJECT_STATE.md` under *Known issues*.

## 7. Scope note

This register is an engineering control and an audit trail. It is not legal advice.
Entries marked as requiring legal review are not settled until that review is recorded
here.
