<?php

declare(strict_types=1);

namespace Modules\Identity\Domain;

use InvalidArgumentException;

/**
 * Normalisation happens here, once.
 *
 * The database has a plain unique index on `email`, which is only
 * case-insensitive because every write goes through this object. Skipping it
 * anywhere would allow two accounts differing only by case.
 */
final readonly class EmailAddress
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $normalised = strtolower(trim($value));

        if ($normalised === '' || mb_strlen($normalised) > 320) {
            throw new InvalidArgumentException('Email address length out of range.');
        }

        if (filter_var($normalised, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email address is not valid.');
        }

        return new self($normalised);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
