<?php

declare(strict_types=1);

namespace Modules\Billing\Domain;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /** Statuses that still confer access. Default deny: anything else does not. */
    public function isLive(): bool
    {
        return match ($this) {
            self::Trialing, self::Active, self::PastDue => true,
            self::Cancelled, self::Expired => false,
        };
    }

    /** @return list<string> */
    public static function liveValues(): array
    {
        return array_values(array_map(
            static fn (self $s): string => $s->value,
            array_filter(self::cases(), static fn (self $s): bool => $s->isLive()),
        ));
    }
}
