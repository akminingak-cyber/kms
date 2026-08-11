<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure;

use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Serialises operations that enforce a per-account count limit.
 *
 * Row locks are the wrong tool for this. `SELECT ... FOR UPDATE` locks the rows
 * that exist; it does nothing about the row a concurrent transaction is about
 * to insert. Two simultaneous device registrations against a limit of five,
 * each counting four existing rows, would both proceed — and PostgreSQL rejects
 * `FOR UPDATE` alongside an aggregate anyway.
 *
 * A transaction-scoped advisory lock keyed on the account serialises the whole
 * check-then-insert sequence, and is released automatically when the
 * transaction ends, including on rollback.
 */
final class AccountLock
{
    public static function acquire(string $accountUuid, string $purpose): void
    {
        if (DB::transactionLevel() === 0) {
            // A transaction-scoped lock outside a transaction would be released
            // immediately, which would look like it worked and protect nothing.
            throw new LogicException('AccountLock::acquire() must be called inside a transaction.');
        }

        DB::statement(
            'SELECT pg_advisory_xact_lock(hashtext(?)::bigint)',
            [$purpose.':'.$accountUuid],
        );
    }
}
