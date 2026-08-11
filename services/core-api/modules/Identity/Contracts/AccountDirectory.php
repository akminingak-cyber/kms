<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

/**
 * Identity's inbound port.
 *
 * The only thing other modules may call. Nothing outside Identity may touch an
 * Eloquent model, a repository or a table in the `identity` schema — see
 * docs/architecture/03-repository-structure.md §4 and the CI check in
 * tools/check-module-boundaries.php.
 *
 * It is deliberately narrow: everything here is a question about an account's
 * existence or state, never a way to mutate one.
 */
interface AccountDirectory
{
    /** True when the account exists and is not closed. */
    public function exists(string $accountUuid): bool;

    /** True when the account exists, is active, and has a verified email. */
    public function isUsable(string $accountUuid): bool;
}
