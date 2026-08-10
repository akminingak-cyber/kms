# ADR-0006: Two-token playback authorization

**Status:** Accepted
**Date:** 2026-08-10
**Deciders:** Architecture, Security

## Context

Starting playback requires two different systems to trust the client for two different things:

1. The **CDN** must serve the manifest and segments to this viewer and no one else.
2. The **DRM licence server** must issue a decryption licence for this content, to this device, with
   the output constraints the rights require.

A viewer's long-lived session token must never be used for either. It is long-lived, broadly scoped,
and would end up in CDN access logs, in browser network panels, and in any manifest a user shares.

## Options considered

**A. Reuse the session access token for CDN and licence requests.** Simple and unacceptable: broad
scope, long life, and it leaks into logs and shared URLs.

**B. One playback token for both.** Better, but the CDN token and the licence token have different
audiences, lifetimes, and threat models — the CDN token appears in URLs, the licence token in a
request body — and one token forces the weakest constraints on both.

**C. Two purpose-scoped, short-lived tokens issued together by a single authorization decision.**

## Decision

**Option C.** One authorization decision, two tokens.

```
POST /api/client/v1/playback/sessions
     { content_ref, device_id, capabilities }
                    │
                    ▼
        playback-authorizer evaluates
        entitlement ∧ rights ∧ parental ∧ device ∧ concurrency ∧ territory
                    │
      ┌─────────────┴──────────────┐
      ▼                            ▼
 delivery token               licence token
 · audience: CDN              · audience: DRM licence proxy
 · scope: this asset's paths  · scope: this content, this session,
 · life: minutes                this device
 · in the URL/cookie          · life: minutes
                              · in the licence request body
                              · carries the rights-derived
                                usage policy reference
```

Rules:

1. **A single decision issues both tokens.** They cannot disagree, because they come from one
   evaluation, and both record the same `decision_id`.
2. Both are short-lived (minutes) and **renewed via session heartbeat**, not by re-authorizing from
   scratch. Renewal re-checks the cheap invariants: session still valid, concurrency still held,
   no blackout newly in force.
3. The licence token is **content-, session- and device-bound**. Replaying it elsewhere fails.
4. The licence token never carries the usage policy inline — it carries a **reference**. The licence
   proxy resolves the policy server-side, so a client cannot tamper with its own output constraints.
5. **Every decision is persisted** with its inputs, its rights rule identifiers, and the resulting
   policy — required by [`../06-rights-management.md`](../06-rights-management.md) §5.
6. Denials return a **specific reason code**, never a generic failure. Support and the client UX both
   depend on distinguishing "not in your package" from "not available in your country" from
   "too many devices playing".
7. **Playback rights are re-evaluated on heartbeat**, not only at start. A blackout beginning
   mid-event must stop playback.

## Consequences

**Accepted costs**
- Two token formats to specify, sign, rotate and monitor.
- Heartbeat traffic scales with concurrent viewers and must be budgeted (it is also what makes
  concurrency enforcement and QoE measurement possible, so it earns its cost).
- Clock skew across devices must be tolerated in token validation.

**Made easier**
- A leaked delivery token grants minutes of access to one asset; a leaked licence token grants
  nothing on another device.
- CDN and DRM vendors can be replaced independently — each sits behind one token contract.
- Concurrency enforcement, session analytics and QoE all derive from the same session record.

**Revisit when**
- Heartbeat cost at scale becomes significant enough to warrant a cheaper liveness signal, or
- A DRM or CDN vendor's constraints make one of the token shapes unworkable (a P2/P3 verification
  item in [`../05-integration-boundaries.md`](../05-integration-boundaries.md)).
