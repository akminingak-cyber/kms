<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use InvalidArgumentException;
use Modules\Media\Domain\AudioRendition;
use Modules\Media\Domain\FrameRate;
use Modules\Media\Domain\Ladder;
use Modules\Media\Domain\PackagingProfile;
use Modules\Media\Domain\Rung;
use Modules\Media\Domain\VideoCodec;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The ladder and packaging invariants.
 *
 * Every one of these describes a defect that a single-rendition playback test
 * cannot see, because it only appears when a player switches rendition — which
 * is to say, only for viewers on variable networks, and never in an office.
 */
final class LadderInvariantsTest extends TestCase
{
    #[Test]
    public function a_gop_that_does_not_divide_the_segment_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/whole number of .* GOPs/');

        // 2000ms segments over 700ms GOPs: segment boundaries would fall
        // between IDR frames, so a player could not switch there.
        new PackagingProfile('bad', 'cmaf', 2000, 700, 90_000, 6, 60, 6000);
    }

    #[Test]
    public function a_gop_that_is_not_a_whole_number_of_frames_is_detected(): void
    {
        $profile = new PackagingProfile('ntsc', 'cmaf', 2000, 1000, 90_000, 6, 60, 6000);

        // 25fps: 1000ms is exactly 25 frames.
        $this->assertTrue($profile->gopIsWholeFrames(new FrameRate(25, 1)));

        // 29.97fps (30000/1001): 1000ms is 29.97 frames, so the encoder must
        // round — and rounds differently for a rung at half rate, which puts
        // IDR frames at different instants in different renditions.
        $this->assertFalse($profile->gopIsWholeFrames(new FrameRate(30_000, 1001)));
    }

    #[Test]
    public function a_time_shift_buffer_shorter_than_the_advertised_window_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already been removed/');

        // Six 2s segments is a 12s window, advertised to players, against a 5s
        // buffer: players would request segments the origin has already dropped.
        new PackagingProfile('bad', 'cmaf', 2000, 1000, 90_000, 6, 5, 6000);
    }

    #[Test]
    public function a_rung_whose_bitrate_does_not_increase_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/never selected by an adaptive player/');

        new Ladder('bad', 'generic', [
            $this->rung('a', 640, 360, 800),
            $this->rung('b', 1280, 720, 800),
        ], [$this->audio()]);
    }

    #[Test]
    public function a_rung_at_a_different_aspect_ratio_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/jump shape/');

        new Ladder('bad', 'generic', [
            $this->rung('a', 640, 360, 800),
            // 4:3 in a 16:9 ladder.
            $this->rung('b', 960, 720, 1600),
        ], [$this->audio()]);
    }

    #[Test]
    public function a_rung_at_a_non_divisor_frame_rate_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not share IDR positions/');

        new Ladder('bad', 'generic', [
            new Rung('a', 640, 360, 800, 1000, 'avc', 'e', 'main', 30, new FrameRate(50, 1)),
            // 30fps is not an integer divisor of 50fps.
            new Rung('b', 1280, 720, 1600, 2000, 'avc', 'e', 'high', 31, new FrameRate(30, 1)),
        ], [$this->audio()]);
    }

    #[Test]
    public function halving_the_frame_rate_on_a_lower_rung_is_permitted(): void
    {
        // A legitimate way to save bits: every frame in the 25fps rung has an
        // exactly coincident frame in the 50fps one.
        $ladder = new Ladder('good', 'sport', [
            new Rung('a', 640, 360, 800, 1000, 'avc', 'e', 'main', 30, new FrameRate(25, 1)),
            new Rung('b', 1280, 720, 2500, 3000, 'avc', 'e', 'high', 31, new FrameRate(50, 1)),
        ], [$this->audio()]);

        $this->assertCount(2, $ladder->rungs);
    }

    #[Test]
    public function two_default_audio_renditions_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/rendition group permits exactly one/');

        new Ladder('bad', 'generic', [$this->rung('a', 640, 360, 800)], [
            new AudioRendition('en', 'en', 'main', 'aac-lc', 128, 2, 48_000, 0, true),
            new AudioRendition('de', 'de', 'main', 'aac-lc', 128, 2, 48_000, 1, true),
        ]);
    }

    #[Test]
    public function an_audio_description_track_may_not_be_the_default(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/expressed no preference/');

        new Ladder('bad', 'generic', [$this->rung('a', 640, 360, 800)], [
            new AudioRendition('ad', 'en', 'description', 'aac-lc', 96, 2, 48_000, 0, true),
        ]);
    }

    #[Test]
    public function a_ladder_with_no_audio_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/silent stream/');

        new Ladder('bad', 'generic', [$this->rung('a', 640, 360, 800)], []);
    }

    #[Test]
    public function an_odd_resolution_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/4:2:0 chroma/');

        $this->rung('a', 641, 361, 800);
    }

    #[Test]
    public function a_codec_whose_string_cannot_be_derived_must_supply_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/rather than guessing it/');

        // HEVC's codec string encodes profile, tier, level and constraint bytes
        // whose correct values depend on the encoder's actual output. Guessing
        // them would be inventing a capability.
        new Rung('a', 640, 360, 800, 1000, VideoCodec::HEVC, 'e', 'main', 30, new FrameRate(25, 1));
    }

    #[Test]
    public function an_explicit_codec_string_is_accepted_for_such_a_codec(): void
    {
        $rung = new Rung('a', 640, 360, 800, 1000, VideoCodec::HEVC, 'e', 'main', 30, new FrameRate(25, 1), 'hvc1.1.6.L93.B0');

        $this->assertSame('hvc1.1.6.L93.B0', $rung->codecString());
    }

    #[Test]
    public function a_cap_below_the_lowest_rung_still_yields_a_playable_manifest(): void
    {
        $ladder = new Ladder('good', 'generic', [
            $this->rung('a', 640, 360, 800),
            $this->rung('b', 1280, 720, 2500),
        ], [$this->audio()]);

        // An empty manifest is not a stricter outcome, it is a broken one: the
        // viewer gets a player error rather than a permitted lower-quality
        // stream.
        $permitted = $ladder->rungsUpTo(240);

        $this->assertCount(1, $permitted);
        $this->assertSame('a', $permitted[0]->label);
    }

    #[Test]
    public function frame_rates_are_exact_rationals(): void
    {
        $ntsc = FrameRate::fromString('30000/1001');

        $this->assertSame('30000/1001', $ntsc->toString());
        $this->assertSame('29.970', $ntsc->toDecimalString());
        // 1001ms is exactly 30 frames at 30000/1001 — the reason broadcast
        // GOPs are chosen in multiples of 1001ms rather than 1000ms.
        $this->assertTrue($ntsc->containsWholeFramesIn(1001));
        $this->assertFalse($ntsc->containsWholeFramesIn(1000));
    }

    private function rung(string $label, int $width, int $height, int $bitrate): Rung
    {
        return new Rung($label, $width, $height, $bitrate, $bitrate + 200, 'avc', 'e', 'main', 30, new FrameRate(25, 1));
    }

    private function audio(): AudioRendition
    {
        return new AudioRendition('audio-en', 'en', 'main', 'aac-lc', 128, 2, 48_000, 0, true);
    }
}
