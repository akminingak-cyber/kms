<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Rights\Contracts\UsageRules;
use Modules\Rights\Domain\PlatformMask;
use Modules\Rights\Domain\TerritoryRules;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Rights evaluation, in isolation. No framework, no database. */
final class RightsDomainTest extends TestCase
{
    #[Test]
    public function territory_evaluation_is_default_deny(): void
    {
        // A territory no rule mentions is not permitted. Absence of a right is
        // a prohibition, never a permission.
        $rules = TerritoryRules::fromArray([['effect' => 'include', 'territories' => ['GB']]]);

        $this->assertTrue($rules->permits('GB'));
        $this->assertFalse($rules->permits('FR'));
        $this->assertFalse($rules->permits(''));
    }

    #[Test]
    public function a_later_exclusion_overrides_an_earlier_inclusion(): void
    {
        // "All of the EU except Germany" is one agreement, and the correction
        // that later adds Germany back must not require rewriting it.
        $rules = TerritoryRules::fromArray([
            ['effect' => 'include', 'territories' => ['DE', 'FR', 'IT']],
            ['effect' => 'exclude', 'territories' => ['DE']],
        ]);

        $this->assertFalse($rules->permits('DE'));
        $this->assertTrue($rules->permits('FR'));

        $corrected = TerritoryRules::fromArray([
            ['effect' => 'include', 'territories' => ['DE', 'FR', 'IT']],
            ['effect' => 'exclude', 'territories' => ['DE']],
            ['effect' => 'include', 'territories' => ['DE']],
        ]);

        $this->assertTrue($corrected->permits('DE'));
    }

    #[Test]
    public function a_wildcard_include_permits_everything(): void
    {
        $this->assertTrue(TerritoryRules::worldwide()->permits('JP'));
    }

    #[Test]
    public function identical_rulesets_hash_identically_regardless_of_order_or_case(): void
    {
        // Interning is what makes an agreement-wide correction a single-row
        // write rather than a rewrite of millions of projection rows.
        $a = TerritoryRules::fromArray([['effect' => 'include', 'territories' => ['gb', 'IE']]]);
        $b = TerritoryRules::fromArray([['effect' => 'include', 'territories' => ['IE', 'GB']]]);

        $this->assertSame($a->hash(), $b->hash());
    }

    #[Test]
    public function an_unknown_platform_is_denied_rather_than_waved_through(): void
    {
        $mask = PlatformMask::forPlatforms(['web', 'tizen']);

        $this->assertTrue(PlatformMask::permitsPlatform($mask, 'web'));
        $this->assertFalse(PlatformMask::permitsPlatform($mask, 'webos'));
        // Default deny extends to vocabulary we do not recognise.
        $this->assertFalse(PlatformMask::permitsPlatform($mask, 'fridge'));
        $this->assertSame(0, PlatformMask::forPlatforms(['fridge']));
    }

    #[Test]
    public function usage_rules_merge_restrictively(): void
    {
        // If any applicable right caps resolution at 720p, the cap is 720p.
        // Taking the maximum would breach the stricter agreement.
        $lenient = new UsageRules(maxResolution: '2160p', concurrencyCap: 5);
        $strict = new UsageRules(maxResolution: '720p', concurrencyCap: 2);

        $merged = $lenient->mergeRestrictive($strict);

        $this->assertSame('720p', $merged->maxResolution);
        $this->assertSame(2, $merged->concurrencyCap);
    }

    #[Test]
    public function an_unknown_resolution_can_never_relax_a_known_cap(): void
    {
        $known = new UsageRules(maxResolution: '720p');
        $unknown = new UsageRules(maxResolution: 'enormous');

        $this->assertSame('720p', $known->mergeRestrictive($unknown)->maxResolution);
        $this->assertSame('720p', $unknown->mergeRestrictive($known)->maxResolution);
    }

    #[Test]
    public function merging_with_an_absent_rule_keeps_the_present_one(): void
    {
        $capped = new UsageRules(maxResolution: '1080p');

        $this->assertSame('1080p', $capped->mergeRestrictive(new UsageRules)->maxResolution);
        $this->assertSame('1080p', (new UsageRules)->mergeRestrictive($capped)->maxResolution);
    }
}
