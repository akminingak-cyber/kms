<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use Modules\Media\Contracts\DeliveryPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Quality-class quantisation.
 *
 * Every rounding decision here is a licence decision. Rounding the wrong way
 * serves a viewer a rung a licensor did not permit, which is a contract breach;
 * rounding the safe way costs a little picture quality. The tests state which
 * is which.
 */
final class DeliveryPolicyTest extends TestCase
{
    #[Test]
    public function no_cap_means_unrestricted(): void
    {
        $this->assertSame('full', DeliveryPolicy::forCaps([])->slug);
        $this->assertNull(DeliveryPolicy::forCaps([null, null])->maxHeight);
    }

    #[Test]
    public function the_strictest_cap_wins(): void
    {
        // A licensor capping at 720p and a plan capping at 1080p means 720p.
        // Taking the higher would breach the stricter agreement.
        $this->assertSame('h720', DeliveryPolicy::forCaps(['1080p', '720p'])->slug);
        $this->assertSame('h576', DeliveryPolicy::forCaps(['576p', null, '2160p'])->slug);
    }

    #[Test]
    public function an_unrecognised_cap_is_treated_as_the_strictest_class(): void
    {
        // A usage rule we cannot interpret is not a usage rule we may
        // disregard. Ignoring it would silently uncap the content.
        $this->assertSame('h576', DeliveryPolicy::forCaps(['4320p-hdr-something'])->slug);
    }

    #[Test]
    public function quantisation_always_rounds_down(): void
    {
        // 900 lines is more than 720 and less than 1080. Rounding up would
        // serve a rung the cap forbids.
        $this->assertSame('h720', DeliveryPolicy::quantise(900)->slug);
        $this->assertSame('h1080', DeliveryPolicy::quantise(1080)->slug);
        $this->assertSame('h1080', DeliveryPolicy::quantise(2159)->slug);
    }

    #[Test]
    public function a_cap_below_every_class_lands_on_the_lowest(): void
    {
        // The ladder guarantees at least one playable rung, so the lowest class
        // is still serviceable — an empty manifest would not be.
        $this->assertSame('h576', DeliveryPolicy::quantise(240)->slug);
    }

    #[Test]
    public function narrowing_never_relaxes_an_existing_cap(): void
    {
        $capped = DeliveryPolicy::forCaps(['720p']);

        // A device that can do 1080p does not lift a 720p licence cap.
        $this->assertSame('h720', $capped->narrowTo(1080)->slug);
        // A device that can only do 576p does tighten it further.
        $this->assertSame('h576', $capped->narrowTo(576)->slug);
        // And an unrestricted policy takes the device's limit.
        $this->assertSame('h1080', DeliveryPolicy::unrestricted()->narrowTo(1080)->slug);
        $this->assertSame('full', DeliveryPolicy::unrestricted()->narrowTo(null)->slug);
    }

    #[Test]
    public function the_class_set_is_closed_and_small(): void
    {
        /*
         * The number of manifest variants per publication equals the number of
         * classes. If this set grew with viewer attributes, each viewer would
         * get a private manifest and cache offload — the dominant cost lever in
         * delivery — would collapse.
         */
        $this->assertSame(
            ['h576', 'h720', 'h1080', 'h2160', 'full'],
            array_map(static fn (DeliveryPolicy $p): string => $p->slug, DeliveryPolicy::all()),
        );

        $this->assertNull(DeliveryPolicy::fromSlug('h999'));
        $this->assertFalse(DeliveryPolicy::isKnownSlug('../../etc'));
    }
}
