<?php

declare(strict_types=1);

namespace Modules\Playback\Domain;

/**
 * How a viewer's territory was concluded.
 *
 * Recorded with every decision. IP geolocation is probabilistic, VPN use is
 * common, and the contractual definition of "where the user is" may differ from
 * where the packet came from — so which signal wins when they disagree is a
 * commercial decision (OQ-20), not an engineering default. Until it is taken,
 * the method is recorded so the answer can be reconstructed either way.
 */
final class TerritoryDetermination
{
    public const IP = 'ip';

    public const ACCOUNT_DEFAULT = 'account_default';

    public const PLATFORM_DEFAULT = 'platform_default';

    public const UNDETERMINED = 'undetermined';
}
