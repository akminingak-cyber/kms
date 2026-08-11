<?php

declare(strict_types=1);

namespace Modules\Identity\Domain;

/**
 * Length over composition rules.
 *
 * Composition rules ("one uppercase, one symbol") push people toward
 * predictable transformations of short passwords; length is the property that
 * actually resists guessing. A breached-password check is the other control
 * that matters and is a Phase 2 item — it needs an external corpus, which the
 * initialisation environment cannot reach.
 */
final class PasswordPolicy
{
    /** @return list<string> violated rule identifiers; empty means acceptable */
    public static function violations(string $password, int $minLength): array
    {
        $violations = [];

        if (mb_strlen($password) < $minLength) {
            $violations[] = 'TOO_SHORT';
        }

        if (mb_strlen($password) > 4096) {
            // Bounded to keep the hashing cost of a single request bounded.
            $violations[] = 'TOO_LONG';
        }

        if (trim($password) === '') {
            $violations[] = 'BLANK';
        }

        return $violations;
    }
}
