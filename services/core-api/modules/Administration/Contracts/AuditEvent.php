<?php

declare(strict_types=1);

namespace Modules\Administration\Contracts;

/**
 * A recorded security-sensitive action.
 *
 * `context` carries the facts that changed. It must never carry a secret, a
 * token, a password or a hash — the audit trail is read by support staff, and
 * anything in it is effectively disclosed to them.
 */
final readonly class AuditEvent
{
    /** @param  array<string,mixed>  $context */
    private function __construct(
        public string $actorType,
        public ?string $actorId,
        public string $action,
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public array $context = [],
    ) {}

    /** @param  array<string,mixed>  $context */
    public static function byAccount(
        string $accountUuid,
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        array $context = [],
    ): self {
        return new self('account', $accountUuid, $action, $subjectType, $subjectId, $context);
    }

    /** @param  array<string,mixed>  $context */
    public static function byStaff(
        string $staffUuid,
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        array $context = [],
    ): self {
        return new self('staff', $staffUuid, $action, $subjectType, $subjectId, $context);
    }

    /**
     * For actions with no authenticated actor — a failed sign-in, an expiry
     * sweep, a reuse detection triggered by an unknown party.
     *
     * @param  array<string,mixed>  $context
     */
    public static function bySystem(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        array $context = [],
    ): self {
        return new self('system', null, $action, $subjectType, $subjectId, $context);
    }
}
