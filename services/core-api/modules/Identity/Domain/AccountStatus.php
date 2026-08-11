<?php

declare(strict_types=1);

namespace Modules\Identity\Domain;

enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';

    /**
     * Default deny: only an explicitly active account may authenticate.
     * A new status added later is denied until someone decides otherwise.
     */
    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
