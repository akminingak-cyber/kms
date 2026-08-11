<?php

declare(strict_types=1);

namespace Modules\Rights\Contracts;

/**
 * The rights answer, with everything needed to explain it later.
 *
 * `availabilityVersion` and `sourceRuleIds` are recorded on the playback
 * decision so that replaying it against the recorded versions reproduces the
 * same result — the property that makes a licensor dispute answerable from a
 * record rather than from today's data.
 */
final readonly class AvailabilityVerdict
{
    /** @param list<string> $sourceRuleIds */
    private function __construct(
        public bool $permitted,
        public ?string $reason,
        public ?int $availabilityVersion = null,
        public array $sourceRuleIds = [],
        public ?UsageRules $usageRules = null,
    ) {}

    /** @param list<string> $sourceRuleIds */
    public static function allow(int $version, array $sourceRuleIds, UsageRules $usage): self
    {
        return new self(true, null, $version, $sourceRuleIds, $usage);
    }

    /**
     * @param  string  $reason  one of the DENIED_* constants
     * @param  list<string>  $sourceRuleIds
     */
    public static function deny(string $reason, ?int $version = null, array $sourceRuleIds = []): self
    {
        return new self(false, $reason, $version, $sourceRuleIds);
    }

    // Precedence order, per docs/architecture/06-rights-management.md §2.
    // A blackout always overrides; absence of any right is the last resort and
    // is a prohibition, never a permission.
    public const DENIED_BLACKOUT = 'blackout';

    public const DENIED_TERRITORY = 'territory';

    public const DENIED_PLATFORM = 'platform';

    public const DENIED_WINDOW = 'window';

    public const DENIED_EXPLOITATION = 'exploitation';

    public const DENIED_MONETIZATION = 'monetization';

    public const DENIED_NO_RIGHT = 'no_right';
}
