# API Versioning and Compatibility

Decision: [ADR-0004](../architecture/adr/ADR-0004-api-versioning.md)

## 1. Version in the path, per surface

```
/api/client/v1/…      /api/admin/v1/…      /api/partner/v1/…
```

Path-based rather than header-based because it is visible in logs, cacheable without `Vary`
complexity, trivially routable at the edge, and unambiguous in a support conversation with a device
manufacturer.

Each surface versions **independently**. The admin API reaching v3 while the client API is still on
v1 is the expected outcome, not a smell.

## 2. What is and is not a breaking change

### Allowed within a major version (append-only)

- Adding a new endpoint
- Adding an **optional** request field
- Adding a response field
- Adding a value to an enumeration **that is documented as extensible** and for which clients have a
  tested fallback
- Relaxing a validation rule
- Adding an optional query parameter with a backwards-compatible default

### Requires a new major version

- Removing or renaming anything
- Making an optional request field required
- Narrowing a type or a validation rule
- Changing a default value
- **Changing the meaning of an existing field** — the most dangerous case, because nothing detects it
  automatically. It is a review responsibility, and reviewers must look for it explicitly
- Changing an error code's meaning
- Changing pagination or sort semantics
- Adding a value to an enumeration that clients treat as exhaustive

## 3. Tolerant readers

Every client must, and this is verified by a conformance test in each client package:

- Ignore unknown fields rather than failing to parse
- Handle unknown enum values via a documented fallback
- Skip unknown item types in a collection rather than failing the whole list
- Treat a missing optional field as absent, never as an error
- Never depend on field ordering or on the absence of a field

The collection rule is the one that matters most in practice. When a new content type is added to a
rail, every TV in the field either renders what it understands and skips the rest, or shows an error
screen. That behaviour is decided years earlier, by whether the client was written as a tolerant
reader.

## 4. Client identification

Every request to the client surface carries:

```
X-KMS-Client:         web | android | androidtv | ios | tizen | webos
X-KMS-Client-Version: <semver>
X-KMS-Device-Id:      <opaque device identifier>
```

This enables:

- **Installed-version distribution measurement** — without it, no version can ever be retired safely
- Targeted diagnostics ("the Tizen 2023 build is failing licence acquisition")
- Minimum-version enforcement and forced-upgrade prompts
- Per-client-version feature flagging, which is how a workaround is shipped for a device that cannot
  be updated

## 5. Deprecation

1. Announce, with a date, in the `packages/api-contracts` changelog.
2. Serve `Deprecation` and `Sunset` (RFC 8594) response headers on affected endpoints.
3. Monitor usage by client and version until it falls below the agreed threshold.
4. Retire only when both the support window has elapsed **and** measured usage is negligible.

The client surface has a hard rule: **a major version is not retired while a supported TV platform
still shows meaningful installed usage**, regardless of elapsed time. Elapsed time is a floor, not a
trigger.

## 6. Minimum supported version

`/api/client/v1/config` returns, per client platform:

```json
{
  "minimum_supported_version": "2.1.0",
  "recommended_version": "3.4.0",
  "upgrade_required": false,
  "upgrade_message_key": "upgrade.required.tizen"
}
```

- `upgrade_required: true` means the client shows a blocking upgrade screen. Used sparingly — for
  security issues and genuinely unserviceable versions — because on some TV platforms the user may
  have no straightforward way to update, and a blocking screen is then simply a lost viewer.
- Message keys, not messages: the client localises. A server-supplied English string on a
  French-language television is a visible failure.

## 7. Internal API versioning

Internal service APIs are versioned by a header and carry no compatibility promise beyond one deploy
cycle, since callers and callees deploy together. During a rolling deploy both versions must
interoperate for the duration of the rollout — which means internal changes still follow expand /
contract, just over hours rather than releases.
