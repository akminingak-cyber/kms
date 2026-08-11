<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

use InvalidArgumentException;

/**
 * A public identifier: UUIDv7, rendered in canonical form.
 *
 * UUIDv7 rather than ULID or UUIDv4 — time-ordered so index locality is good
 * and cursor pagination is natural, but stored in PostgreSQL's native 16-byte
 * `uuid` type rather than a 26-character string on every unique index and every
 * cross-context reference. See docs/database/README.md §3.
 *
 * This is a plain value object: no framework imports, per the module layout
 * rule in docs/architecture/03-repository-structure.md.
 */
final readonly class Identifier
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $normalised = strtolower(trim($value));

        if (preg_match(self::PATTERN, $normalised) !== 1) {
            throw new InvalidArgumentException('Not a canonical UUIDv7.');
        }

        return new self($normalised);
    }

    /** Returns null instead of throwing, for values that arrive from a client. */
    public static function tryFromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        try {
            return self::fromString($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
