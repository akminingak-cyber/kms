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

Two components touch key material, and they touch it in opposite directions. Stating this precisely
matters: an absolute "only the licence proxy ever sees a key" would be false, and a security control
that is described inaccurately cannot be audited.

| Component | Direction | What it can do | What it cannot do |
|---|---|---|---|
| **Packager** (`media-pipeline`) | **Write path** | Receive a key **pushed** to it for a specific packaging job, use it to encrypt, discard it | **Query the vault.** It has no read credential, cannot request a key by id, and cannot enumerate keys. It never retains key material after the job |
| **Licence proxy** (`drm-license-proxy`) | **Read path** | Resolve `key_id` → key material, per licence request, under policy | Write, create or rotate keys |

**The licence proxy is the only component that can resolve a key by identifier, and the only one on a
request-driven path.** That is the property that matters: an attacker who reaches the packager gets,
at most, the keys for jobs running at that moment; an attacker who reaches the licence proxy could
ask for anything, which is why it is the most heavily constrained and most heavily audited workload
in the platform.

- `core-api` cannot read content keys at all. Compromising it does not yield the library.
- Key provisioning to the packager is **push-only, per job, time-boxed, and audited**. There is no
  packager-initiated key fetch to abuse.
- **Every key access is audited**: which key, which identity, when, for which content, job or session.
- **Any key access outside these two paths raises an immediate page.** Not a dashboard entry — a page.
- Both components sit in the protected zone ([`README.md`](README.md) §2).

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

## 3a. Key durability — the disaster-recovery case nobody plans for

**If the key vault is lost, every encrypted asset in the library becomes permanently unplayable.**
Not degraded — unrecoverable. The mezzanines survive, so the library could in principle be
re-encoded and re-packaged from scratch, but for a catalog of any size that is weeks of work, and for
nDVR content already recorded it is simply lost.

This is a larger single-event risk than losing the application database, and it is routinely omitted
from disaster-recovery planning because key management is filed under security rather than under
availability. It belongs in both.

Requirements:

1. **Key material is backed up**, encrypted under a separate root of trust, in a separate failure
   domain from the primary vault. A backup encrypted by the key it is protecting is not a backup.
2. **The vault's own root key / unseal material has a documented escrow**, held under split control —
   no single person can reconstruct it, and no single person's absence can prevent reconstruction.
   Both halves of that sentence are load-bearing.
3. **Restore is rehearsed**, on the same quarterly cadence as the database restore drill
   ([`../database/README.md`](../database/README.md) §6), including issuing a licence for an existing
   asset from restored key material. A key backup that has never been restored is an assumption.
4. **RPO for key material is effectively zero.** A key created after the last backup and used to
   encrypt content is a key whose loss orphans that content. Provisioning a new key and backing it up
   are one operation, not two.
5. **Key backup access is a break-glass path** with two-person control and mandatory review — the
   backup is as sensitive as the vault, and it is somewhere less well defended.
6. **Retention follows content, not a fixed schedule.** A key may be retired only when the content it
   protects is withdrawn *and* its retention obligation has ended.

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
