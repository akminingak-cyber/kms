# DRM Architecture

> ## ⚠ Everything in this document about vendor behaviour is `[UNVERIFIED]`
>
> Vendor documentation is unreachable from the environment where this was written
> ([`../architecture/00-inspection-report.md`](../architecture/00-inspection-report.md), E6).
> This document states **what KMS TV needs and how it is structured**. It does **not** report what any
> DRM system, device, or vendor actually supports. Every capability claim must be confirmed by a
> human against current vendor documentation and real test devices before it is built on.
>
> Verification checklist: [`../architecture/05-integration-boundaries.md`](../architecture/05-integration-boundaries.md) §P2.

## 1. Start the agreements now

**Widevine (Google), PlayReady (Microsoft) and FairPlay Streaming (Apple) each require a separate
commercial agreement**, with separate processes and separate timelines. They are, with high
confidence, **the longest-lead items in the entire programme** — considerably longer than the
engineering work that depends on them.

The engineering integration is comparatively short. The agreements are not. Starting them in Phase 6,
when the code is ready, means waiting with a finished pipeline and nothing to test it against.

**Action: begin the agreement process during Phase 1, in parallel with everything else.**

A multi-DRM vendor may consolidate this into one commercial relationship. Whether to use one, or to
integrate each system directly, is a P2 checklist decision and should be taken early for the same
reason.

## 2. What DRM does and does not do

| DRM does | DRM does not |
|---|---|
| Encrypt content so only a licensed client can decrypt | Know what a licence window is |
| Enforce output protection (HDCP) at the device | Know what a territory is |
| Enforce resolution caps tied to device security level | Know what a subscription is |
| Bind a licence to a device | Prevent a determined attacker with capture hardware |
| Support persistent licences for offline playback | Make rights compliance automatic |

**DRM enforces a subset of what rights require.** Everything in the right-hand column is enforced by
playback authorization ([`../security/playback-authorization.md`](../security/playback-authorization.md)),
which is why that path is the platform's critical path and DRM is a downstream consequence of it.

## 3. Architecture

```
   RIGHTS (D1)                    PLAYBACK AUTH (D2)                PROTECTION (D3)
   usage rules:                   evaluates and issues              licence policy +
   max resolution                 a licence token                   key resolution
   HDCP level          ─────►     (short-lived, content-,   ─────►  (in the protected zone)
   security level                  session- and device-bound)             │
   offline allowed                                                        │
   concurrency cap                                                        ▼
                                                              ┌──────────────────────┐
   player ──licence request + licence token──────────────────►│ drm-license-proxy    │
                                                              │ · validates token    │
                                                              │ · resolves policy    │
                                                              │ · resolves key       │
                                                              │   (sole key resolver) │
                                                              │ · applies output     │
                                                              │   constraints        │
                                                              │ · audits the request │
                                                              └──────────┬───────────┘
                                                                         │
                                                              ┌──────────▼───────────┐
                                                              │ DRM vendor licence   │
                                                              │ server(s)            │
                                                              └──────────────────────┘
```

### Why a licence proxy rather than direct client-to-vendor

1. **The client must not choose its own policy.** The licence token carries a **reference** to a
   policy, never the policy inline; the proxy resolves it server-side. A client that can assert
   "give me a 4K licence with no HDCP requirement" defeats the usage rules the rights depend on.
2. **Key access is contained.** The proxy is the only component that can resolve a key by identifier;
   the packager receives keys pushed per job and cannot query the vault
   ([`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md) §3).
3. **Licence issuance is audited** — who asked for what, when, and the outcome. Required for both
   security investigation and licensor questions.
4. **The vendor is replaceable.** All three DRM systems sit behind one port (P2); the client speaks to
   us, not to a vendor.
5. **Policy comes from rights, dynamically.** Changing a licensor's resolution cap changes the issued
   licences without a code change or a client update.

## 4. Encryption scheme

Design target: **one CMAF encode encrypted with `cbcs`, serving all three DRM systems**
([ADR-0005](../architecture/adr/ADR-0005-cmaf-multi-drm.md)), with a `cenc` fallback set retained as a
design option for devices that cannot handle `cbcs`.

This is **a design target subject to verification**, not a statement of fact. The blocking checklist
is in ADR-0005 and includes verifying support on the **oldest** device in the target matrix — not the
newest, which is the test everyone runs by default and which proves the least.

## 5. Licence policy derived from rights

Policy is **computed from rights usage rules**, never hardcoded:

| Rights usage rule | Licence policy effect |
|---|---|
| Max resolution | Resolution cap in the issued licence |
| Required output protection | HDCP requirement |
| Required security level | Minimum device security level for a licence to be issued |
| Offline permitted | Whether a persistent licence may be issued, and for how long |
| Concurrency cap | Enforced at authorization; reflected in session policy |

A test in the Phase 6 exit criteria verifies this directly: **change a rights usage rule and observe
the issued licence policy change**, with no deployment. If that test cannot pass, policy is hardcoded
somewhere, and the platform will breach a contract the first time a licensor changes terms.

## 6. Content keys

Full lifecycle: [`../security/secrets-and-key-management.md`](../security/secrets-and-key-management.md) §3.

- Generated in the vault; the plaintext key never exists outside it.
- The database stores `key_id` and a vault **reference** — **never a key value**.
- Rotated per policy: periodically within live streams, per title for VOD.
- **Rotation must not interrupt active sessions**: new keys apply to new segments; clients acquire
  new licences as needed. Exercised in Phase 6, not merely documented.
- Every key access audited; **any read outside the licence proxy pages immediately**.

## 7. Offline / persistent licences

Only if downloads are in scope (**OQ-12**). If they are, it adds:

- A **separate rights dimension** — download is its own exploitation, licensed separately.
- Persistent licences with their own duration and renewal rules.
- Licence revocation for cancelled subscriptions — with the honest caveat that a device that never
  comes back online cannot be reached, so licence duration is the real control.
- Device storage management and content expiry on-device.
- A materially larger client implementation on every platform.

**Answer OQ-12 before Phase 6.** Adding downloads afterwards touches rights, protection, all client
applications, and the packaging pipeline simultaneously.

## 8. Testing

| What | How |
|---|---|
| Licence issuance logic | Unit tests against the fake adapter |
| Policy derivation from rights | Integration tests: change a rule, assert the policy |
| Token validation and binding | Integration tests, including replay and cross-device attempts |
| **Real DRM handshakes** | **Staging against real licence servers.** Never mocked |
| **Device playback** | **Real devices, one per DRM system minimum.** Emulators do not exercise the security path |
| Key rotation | Staging, with sessions active across the rotation |
| Degraded modes | Licence server unavailable, licence denied, device below required security level |

The two bold rows are the ones that cannot be substituted. A DRM integration that has only been
tested against a fake has not been tested — the interesting failures live in the device's security
implementation, which is exactly what a fake cannot reproduce.

## 9. Open items blocking this area

- [ ] **OQ-7** — DRM vendor(s): direct integration or a multi-DRM provider
- [ ] **OQ-3** — device matrix, which determines the required security levels and schemes
- [ ] **OQ-12** — offline downloads in scope?
- [ ] **OQ-14** — 4K/HDR in scope? Higher-value content typically carries stricter security-level and
      output-protection requirements
- [ ] P2 verification checklist complete
- [ ] Test devices procured — **one per DRM system at minimum**, and ideally the oldest supported
      model of each TV platform
