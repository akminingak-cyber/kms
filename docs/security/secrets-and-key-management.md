# Secrets and Key Management

Two categories with very different consequences, handled differently:

| | **Operational secrets** | **Content keys** |
|---|---|---|
| Examples | Database passwords, API credentials, signing keys | AES keys that decrypt the video |
| Compromise means | Access to a system — bad, recoverable | **Mass decryption of the library** — loss of content deals |
| Who may read | The service that needs it | **Only the DRM licence proxy** |
| Storage | Secret manager | Vault/HSM-backed, wrapped, never in application storage |

## 1. Absolute rules

1. **No secret in the repository. Ever.** Not in code, config, tests, fixtures, documentation,
   comments, commit messages, or CI definitions. Enforced by pre-commit hooks and CI secret scanning.
2. **No secret in a log line, an error message, an API response, or a monitoring dashboard.**
   Structured logging redacts by field name and by value pattern.
3. **No production secret in a non-production environment.** Staging uses staging credentials.
4. **A secret that reaches a client device is public.** Design as if it is.
5. **Content keys never enter PostgreSQL.** The database stores a key *identifier* and a *reference*
   to vault material. If a key value can be selected out of the database, the design has failed.
6. **Every secret has an owner, a rotation period, and a documented rotation procedure** that has
   been executed at least once outside an incident.

## 2. Operational secrets

- Stored in a secret manager (selected with hosting, **OQ-16**); injected at runtime as environment
  variables or mounted files. Never baked into an image.
- **Short-lived and automatically rotated wherever the platform supports it.** A static, long-lived
  credential requires written justification and an expiry date.
- Scoped per deployment profile: the `core-api` web tier's database credential differs from the
  `playback-authorizer`'s, which cannot read `protection` at all.
- **Local development uses `.env` files that are git-ignored**, containing only local, worthless
  values. A `.env.example` documents required variables with placeholder values, and is the file that
  is committed.
- CI secrets are scoped per pipeline, masked in output, and unavailable to pull requests from forks.

### Rotation

| Secret | Period | On compromise |
|---|---|---|
| Database credentials | Automated, frequent | Immediate rotation; audit access |
| Vendor API credentials | Per vendor policy | Immediate rotation; notify the vendor |
| Token signing keys | Regular, overlapping validity | Rotate; revoke affected token families |
| Internal service credentials | Automated, short-lived | Automatic |
| **Content keys** | Per policy — see below | Re-key and re-package affected content |

Signing keys rotate with **overlapping validity** and a `kid` in every token, so rotation never
invalidates every session at once. Rotation that logs out every viewer will not be performed when it
is needed, which defeats the purpose of having a rotation procedure.

## 3. Content keys

### Lifecycle

```
1. Generate      in the vault/HSM. The plaintext key never exists outside it
2. Reference     the packager receives the key for encryption through a controlled path;
                 the database stores key_id + a vault reference only
3. Serve         the licence proxy resolves key_id → key material, at licence issuance,
                 under policy, per request
4. Rotate        per policy (see below)
5. Retire        when content is withdrawn and the retention obligation ends
```

### Access control

**The licence proxy is the only workload with read access to content key material.** This is the
single most important access-control statement in the platform, and it is what makes
`drm-license-proxy` a separate service in its own network zone rather than a module
([ADR-0001](../architecture/adr/ADR-0001-modular-monolith-first.md), Phase 6).

- `core-api` cannot read content keys. Compromising it does not yield the library.
- The packager receives keys through a controlled, audited path for encryption only, and does not
  retain them.
- **Every key access is audited**: which key, which identity, when, for which content and session.
- **Any key read outside the licence proxy raises an immediate page.** Not a dashboard entry — a page.

### Rotation policy

| Content | Rotation | Why |
|---|---|---|
| Live channels | Periodic, keys rotating within the stream | Limits the value of any single extracted key to a bounded window |
| High-value VOD | Per title, rotated on re-packaging | Contains the blast radius |
| Standard VOD | Per title | Extraction exposes one title, not the library |
| Catch-up / nDVR | Inherits the live rotation | |

Rotation must **not interrupt active sessions**: new keys apply to new segments, and clients acquire
new licences as needed. This is exercised in the Phase 6 exit criteria, because a rotation procedure
that has only ever been described is not a procedure.

## 4. Separation of duties

- Whoever administers the vault cannot deploy application code, and vice versa. One person able to
  do both can extract keys and remove the evidence.
- Vault access changes require approval from a second person.
- Vault audit logs go to a stream the vault administrators cannot alter.
- Break-glass access to keys is time-boxed, requires two people, and triggers a mandatory review
  regardless of outcome.

## 5. TLS and certificates

- Certificate issuance and renewal automated; **expiry monitored with alerting well before expiry**.
  Certificate expiry is one of the most common self-inflicted outages in the industry, and it is
  entirely preventable.
- Private keys never leave the systems that use them, and never enter the repository.
- Internal traffic uses TLS with its own certificate authority; certificates are short-lived.

## 6. What CI enforces

- [ ] Secret scanning on every commit and every PR, including history on first enablement
- [ ] Pre-commit hooks so a secret is caught before it is pushed
- [ ] No `.env` file (other than `.env.example`) tracked
- [ ] IaC scanning for hardcoded credentials and over-permissive policies
- [ ] Dependency audit — a compromised dependency reads whatever the process can read

## 7. If a secret leaks

1. **Rotate first.** Do not investigate first, do not wait for a meeting. Every minute the credential
   is valid is a minute of exposure.
2. Revoke sessions or tokens derived from it.
3. Audit for use of the leaked credential.
4. Remove it from history if it was committed — and treat it as compromised **regardless**, because
   it may already have been cloned or indexed.
5. Notify the vendor if it was theirs.
6. Post-incident review: how it got there, and which control should have caught it.

For content keys specifically, add: assess which content is affected, decide whether re-keying and
re-packaging is required, and **notify the rights holder if the agreement requires it**. That
notification obligation is a contractual term, and it should be known before an incident, not looked
up during one.
