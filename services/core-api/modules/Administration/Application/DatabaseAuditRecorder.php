<?php

declare(strict_types=1);

namespace Modules\Administration\Application;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Administration\Contracts\AuditEvent;
use Modules\Administration\Contracts\AuditRecorder;
use Modules\Administration\Infrastructure\Eloquent\AuditEntry;
use Modules\Shared\Domain\Clock;
use Throwable;

final readonly class DatabaseAuditRecorder implements AuditRecorder
{
    public function __construct(private Clock $clock) {}

    public function record(AuditEvent $event): void
    {
        try {
            AuditEntry::query()->create([
                'uuid' => (string) Str::uuid7(),
                'actor_type' => $event->actorType,
                'actor_id' => $event->actorId,
                'action' => $event->action,
                'subject_type' => $event->subjectType,
                'subject_id' => $event->subjectId,
                'correlation_id' => $this->correlationId(),
                'ip_address' => request()?->ip(),
                'user_agent' => $this->userAgent(),
                'context' => $event->context === [] ? null : $event->context,
                'occurred_at' => $this->clock->now(),
            ]);
        } catch (Throwable $e) {
            /*
             * Recording never throws.
             *
             * Turning a successful password change into a 500 because the audit
             * write failed would be a worse outcome than the missing row: the
             * user retries, and now there are two password changes and still no
             * audit trail. The failure is logged at error level and alerted on
             * instead.
             *
             * Playback authorization from Phase 4 is the deliberate exception —
             * there an unwritable decision record denies playback, because an
             * unauditable decision is indefensible to a licensor.
             */
            Log::error('audit.write_failed', [
                'action' => $event->action,
                'actor_type' => $event->actorType,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function correlationId(): ?string
    {
        $value = request()?->attributes->get('kms.correlation_id');

        return is_string($value) ? $value : null;
    }

    private function userAgent(): ?string
    {
        $agent = request()?->userAgent();

        return is_string($agent) ? mb_substr($agent, 0, 512) : null;
    }
}
