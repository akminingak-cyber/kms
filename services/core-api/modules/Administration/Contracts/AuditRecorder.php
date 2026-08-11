<?php

declare(strict_types=1);

namespace Modules\Administration\Contracts;

/**
 * Administration's inbound port for the audit trail.
 *
 * Every module records security-sensitive operations through this, and through
 * nothing else. It is intentionally the one cross-cutting port in the platform:
 * an audit trail that some modules bypass is not an audit trail.
 *
 * Recording never throws. A failure to write an audit entry must not turn a
 * successful password change into a 500 — it is logged at error level and
 * alerted on instead. (Playback authorization from Phase 4 is the deliberate
 * exception: there, an unwritable decision record denies playback, because an
 * unauditable decision is indefensible to a licensor.)
 */
interface AuditRecorder
{
    public function record(AuditEvent $event): void;
}
