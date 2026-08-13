# docs/legal — KMS TV Legal and Compliance Records

This directory holds the **records** that make KMS TV's legal position provable rather
than assumed. It contains documentation only — no application code.

Governing rules: `CLAUDE.md` §2 (Legal and content policy) and §3 (Third-party
dependencies and open-source licenses). Where this README and `CLAUDE.md` differ,
`CLAUDE.md` governs.

## Contents

| File | Purpose |
|---|---|
| `CONTENT_RIGHTS_POLICY.md` | How KMS TV determines it may distribute a given piece of content. |
| `THIRD_PARTY_LICENSES.md` | The register of every third-party component and its license review. |
| `DECISION_LOG.md` | Dated record of every legal/licensing decision taken. |

Files added in later phases (per `IMPLEMENTATION_PLAN.md`):

- `rights-model.md` — Phase 13
- `timeshift-rights.md` — Phase 26
- `ffmpeg-license-review.md` — Phase 16
- `drm-compliance.md` — Phase 30
- `license-compliance-report.md` — Phase 33
- `compliance-evidence.md` — Phase 13

## The two questions this directory must always answer

1. **For any piece of content in the catalogue:** why are we allowed to serve this, to
   whom, where, and until when?
2. **For any third-party component in the product:** what license governs it, what does
   that license require of us, and who decided it was compatible?

If either question cannot be answered from these records, that is a defect, and it is
recorded in `PROJECT_STATE.md` under *Known issues*.

## Standing prohibitions

Restated here because this is where they will be looked for. KMS TV must never be
designed to bypass DRM, bypass authentication, bypass geo-restrictions, steal stream
credentials, extract protected streams, evade content-provider controls, or redistribute
unauthorized copyrighted content. See `CLAUDE.md` §1 and §2.2.

## Scope note

These records support engineering discipline and create an audit trail. They are not
legal advice and do not replace review by qualified counsel. Decisions marked as
requiring legal review must not be treated as settled until that review is recorded here.
