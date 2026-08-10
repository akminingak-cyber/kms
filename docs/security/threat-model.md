# Threat Model

A living document. It is revised whenever a new context, external surface, or integration is added —
not written once and archived.

## 1. Adversaries

| Adversary | Capability | Motivation | Realistic? |
|---|---|---|---|
| **Casual account sharer** | A password and a browser | Free access for friends and family | Certain. The most common revenue leak in OTT |
| **Opportunistic attacker** | Public tooling, credential dumps | Account resale, free access | Certain |
| **Stream ripper** | DRM removal tools, modified players, capture hardware | Redistribution, piracy sites | Certain for premium content |
| **Commercial pirate** | Funded, persistent, restreaming infrastructure | Revenue from a competing paid service | Likely for live sport and premium content |
| **Fraudster** | Stolen cards, automation | Monetising stolen payment credentials | Certain once payments are live |
| **Competitor / scraper** | Automation | Catalog, pricing, EPG data | Likely |
| **Insider** | Legitimate credentials | Curiosity, grievance, or coercion | Low probability, very high impact |
| **Targeted attacker** | Sophisticated, patient | Content keys, subscriber database | Lower probability, catastrophic impact |
| **Untargeted automation** | Scanners, bots | Whatever is exposed | Continuous background noise |

## 2. Threats and controls

### T1 — Credential stuffing and account takeover
*Very likely. Subscriber credentials are reused from breached sites.*

Controls: Argon2id hashing; strict rate limits per IP, per account, and per device on auth endpoints;
bot detection; breached-password checking at registration and password change; anomalous-login
alerting to the account holder; optional MFA; **session and device visibility so a viewer can see
and remove unknown devices**. Detection: failed-login rate by IP and by account; sign-ins from
implausible locations; sudden device registrations.

### T2 — Account sharing beyond household terms
*Certain. Distinguishing "sharing" from "legitimate household use" is a product decision before it is
a technical one.*

Controls: concurrency limits enforced at authorization; device registration limits per plan;
household signals (IP, network, viewing pattern) reported rather than automatically enforced —
false-positive enforcement against a paying customer is worse than the leak it prevents. Any
enforcement threshold is a **product decision (OQ-25)**, not an engineering default.

### T3 — Content key extraction
*Lower probability, catastrophic impact. The one that ends content deals.*

Controls: keys only in the vault; **the licence proxy is the sole reader**, in its own network zone
with its own credentials; no key value ever in PostgreSQL, in logs, in an error message, or in an
API response; key rotation; every key access audited; per-content and per-period keys so one
extraction does not expose the library. Detection: any key read outside the licence proxy is a
**page-immediately** alert.

### T4 — Stream ripping and restreaming
*Certain for premium content. Cannot be eliminated; the goal is to raise cost and shorten the window.*

Controls: DRM with rights-derived policy (security level, HDCP, output protection); short-lived,
session- and device-bound tokens ([ADR-0006](../architecture/adr/ADR-0006-playback-authorization-tokens.md));
concurrency enforcement; anomaly detection on session patterns; forensic watermarking under
consideration for high-value live content (Phase 10). Detection: one account with implausible
session volume or geography; unusual manifest request patterns in CDN logs.

### T5 — Entitlement manipulation
*Direct revenue loss if it works.*

Controls: entitlements derived server-side only; the client never asserts entitlement; every grant
records its source; playback decisions record the grant identifiers used; reconciliation between
billing state and entitlement grants, alerting on divergence. Detection: grants with no valid source;
playback authorized against an expired or cancelled subscription.

### T6 — Payment fraud
*Certain once payments are live.*

Controls: PSP-side fraud tooling; velocity limits on sign-up and payment attempts; trial abuse
controls; **entitlement granted only on confirmed payment**, never on payment initiation;
chargeback handling that revokes entitlement. Detection: elevated decline rates; many accounts from
one device or payment instrument; chargeback rate by cohort.

### T7 — Admin account compromise
*Low probability, total impact — rights, pricing and subscriber data in one step.*

Controls: **MFA mandatory, no exceptions**; RBAC with least privilege; separate admin hostname and
network policy, ideally IP-restricted; every action audited; 4-eyes approval on rights and pricing
changes; no standing production database access; session timeouts; anomalous-action alerting.
Detection: privilege changes; bulk exports; rights or price changes outside business hours; access
from a new location.

### T8 — Insider misuse
*Low probability, high impact, and the hardest to detect.*

Controls: least privilege; separation of duties (whoever can change rights cannot approve their own
change); support tooling that shows what is needed rather than allowing free query; audit logs the
audited cannot alter; access reviews; **no production data in non-production environments**.
Detection: bulk customer-record access; access to accounts with no support ticket.

### T9 — Denial of service
*Continuous background noise, occasionally targeted — especially around major live events.*

Controls: edge DDoS protection; layered rate limits; **the decision plane deployed separately so it
survives control-plane saturation**; graceful degradation with defined behaviours; capacity headroom
sized for peak live events, which are predictable and should be planned for explicitly.

### T10 — Scraping of catalog, EPG and pricing
*Likely; low direct harm, but it distorts capacity and can expose unreleased content.*

Controls: rate limits; authentication required for detail endpoints; **unreleased content never
present in an API response**, not merely flagged as hidden — a "coming soon" item in a response is a
leak whatever the flag says.

### T11 — Supply-chain compromise
*Rising and real. A malicious dependency runs with our privileges.*

Controls: committed lockfiles; dependency audit in CI; reviewed rather than auto-merged updates on
payment, playback and key paths; SBOM per release; pinned base images; signed build artifacts;
minimal build-time network access.

### T12 — Rights misconfiguration
*Not an attack, and more likely than most attacks. Consequences are contractual.*

Controls: availability **preview before commit**; 4-eyes approval; effective-dated, append-only
rights data; every decision records the rules applied; alerting on unusual availability changes
(a rule change that suddenly opens a large catalog in a new territory is either a mistake or a very
good day). Detection: diffing the availability projection between versions.

### T13 — Personal data exposure
*Regulatory and reputational.*

Controls: minimisation — collect only what is needed; encryption in transit and at rest;
**no production data outside production**; field-level protection for sensitive attributes; access
logging; retention limits with automated enforcement; erasure that reaches backups and archives
([`privacy-and-compliance.md`](privacy-and-compliance.md)).

## 3. Assumptions

Stated so they can be challenged, since a threat model built on unexamined assumptions is decorative:

1. Client devices are hostile. Some are rooted, jailbroken, or instrumented.
2. TLS protects data in transit; endpoint compromise is out of our control.
3. DRM systems function as specified — **to be verified**, not assumed
   ([`../streaming/drm.md`](../streaming/drm.md)).
4. The CDN faithfully enforces token validation — verified in P3, and never relied on alone.
5. The PSP handles card data correctly, keeping us out of PCI scope — verified in P1.
6. Staff are trustworthy, but controls assume they might not be.
7. Any secret that reaches a client device is public.

## 4. Detection and response

Security events go to a **separate, append-only stream** with independent retention and
access control from application logs — an attacker who reaches application logging must not be able
to erase evidence.

Alert immediately on:
- Any content key read outside the licence proxy
- Privilege escalation or role change
- Bulk customer data access
- Rights or price changes without an approval record
- Playback authorized against an invalid entitlement
- Anomalous authentication failure rates
- Any production database access outside the break-glass procedure

Incident response, severity definitions and escalation:
[`../operations/release-and-incident-management.md`](../operations/release-and-incident-management.md).
