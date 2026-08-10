# Identity and Access

## 1. Two populations, two systems

| | **Viewers** | **Staff** |
|---|---|---|
| Scale | Potentially millions | Tens to hundreds |
| Authentication | Email/password, optional social or operator SSO | SSO with **mandatory** MFA |
| Authorization | Entitlements (what they bought) | RBAC (what their job requires) |
| Session length | Long — a TV must not ask for a password weekly | Short, with re-authentication for sensitive actions |
| Blast radius | One account | Potentially the whole platform |

These are **separate identity systems** with separate credential stores and separate token issuers.
A staff member with a viewer account has two unrelated identities. Merging them creates a path from
the internet-facing viewer login to administrative capability, which is exactly the path an attacker
looks for.

## 2. Viewer authentication

### Credentials
- **Argon2id** password hashing with parameters reviewed at least annually.
- Minimum length over composition rules; checked against breached-password lists at registration and
  change.
- Email verification required before entitlement is granted.
- Password reset via single-use, short-lived, single-purpose tokens; **all sessions invalidated on
  reset** — a reset after a compromise that leaves the attacker's session alive achieves nothing.

### Tokens

| Token | Life | Storage | Scope |
|---|---|---|---|
| **Access token** | Minutes | Memory (web: memory, not `localStorage`) | The API surface |
| **Refresh token** | Long, rotating | Secure device storage; web: `HttpOnly` `Secure` `SameSite` cookie | Refresh only |

- Access tokens are signed asymmetrically with rotating keys identified by `kid`, so the resource
  servers verify without holding signing material.
- **Refresh tokens rotate on every use and are tracked as a family.** Presenting a rotated token
  means either theft or a client bug: the whole family is revoked and the account holder is notified.
  This is the single most effective control against stolen refresh tokens on shared or compromised
  devices.
- Every token is **bound to a device**. A token replayed from a different device fails.
- Sign-out revokes the family server-side, not just the client copy.

### TV device activation

Televisions have no usable keyboard. Typing an email address and a password with a remote control is
so painful that it materially reduces sign-up conversion, and it pushes people toward weak passwords.

The flow is a device authorization grant in the style of **RFC 8628**:

```
TV                          KMS TV                        Phone/Web
 │  request activation         │                              │
 ├────────────────────────────►│                              │
 │  short code + verify URL    │                              │
 │◄────────────────────────────┤                              │
 │  display code               │                              │
 │                             │◄──sign in, enter the code────┤
 │                             │   (already-authenticated     │
 │                             │    session on a real         │
 │  poll (backoff)             │    keyboard)                 │
 ├────────────────────────────►│                              │
 │  tokens, once approved      │                              │
 │◄────────────────────────────┤                              │
```

Rules: codes are short but high-entropy relative to their lifetime; they expire in minutes; they are
single-use; polling is rate-limited with mandated backoff; **approval names the device** so the
account holder sees what they are authorising rather than approving a blank.

This flow is built and proven in **Phase 1**, before any TV application exists. It is the hardest
authentication interaction in the platform and the one that most affects conversion, so it should not
be discovered late.

### Optional additional methods
Social sign-in, operator SSO, and MSISDN-based identification are market-dependent (**OQ-26**). All
sit behind the `IdentityProvider` port (P9) and never bypass device binding or entitlement checks.

## 3. Profiles and parental controls

- Profiles are **not** security principals. Switching profiles is not authentication.
- A profile's rating limit is enforced **server-side at authorization**. The client showing or hiding
  a title is presentation only.
- PIN-protected profiles: the PIN gates profile selection and rating overrides. It is not a
  substitute for account authentication, and rate limits apply to prevent brute forcing a 4-digit
  code.
- **The parental decision is recorded with the playback decision.** Disputes about what a child was
  able to watch are answered from records.

## 4. Devices

- Every device registers on first use and receives a stable device identifier.
- Registration records device class, platform, model where available, and first/last seen.
- **Device class is authoritative platform data**, used as an input to rights and DRM policy — not
  a display label. A client claiming a different device class must not gain capability, so class is
  corroborated server-side wherever possible.
- Device limits per plan are enforced at authorization, and removal is self-service with a cool-down
  to stop the limit being trivially cycled.
- Device removal revokes that device's token family immediately.

## 5. Staff access

- **SSO with mandatory MFA.** No local passwords, no shared accounts, no exceptions for service or
  break-glass accounts (which use separate, individually attributed credentials).
- **RBAC** with roles matching real job functions — content operator, rights manager, commercial
  operator, support agent, platform engineer — not a generic "admin".
- **Least privilege by default.** Elevated capability is time-boxed and approved.
- **Separation of duties:** whoever can change rights or pricing cannot approve their own change.
- **Every action is audited** with actor, timestamp, before, after, and reason. Audit records are
  append-only and are not deletable by the staff population they cover.
- **Acting on behalf of a customer** is a distinct, explicitly logged mode with a visible banner —
  never an invisible impersonation.
- **No standing production database access.** Diagnosis goes through observability; direct access is
  a break-glass procedure that is approved, time-boxed, fully logged, and reviewed afterwards.
- Access is reviewed on a schedule and revoked promptly on role change or departure.

## 6. Service-to-service

- Every internal call is authenticated. Network position grants nothing.
- Each deployment profile has its own identity and its own database role
  ([`../database/README.md`](../database/README.md) §7).
- Credentials are short-lived and rotated automatically; long-lived static secrets are a last resort
  with a documented justification and an expiry date.
- The mechanism (mTLS vs signed service tokens) is decided in Phase 4 alongside hosting (**OQ-16**).

## 7. Session security

| Concern | Approach |
|---|---|
| Session fixation | New session identifier on every privilege change |
| CSRF | `SameSite` cookies plus token validation on cookie-authenticated flows |
| XSS | Strict CSP; no `dangerouslySetInnerHTML` without review; access tokens never in `localStorage` |
| Clickjacking | Frame-ancestors restricted |
| Concurrent sessions | Visible to the account holder, individually revocable |
| Idle timeout | None for viewers (a TV must not log itself out); short for staff |
| Absolute timeout | Long for viewers, enforced for staff |
