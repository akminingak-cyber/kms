<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use DateTimeImmutable;
use Modules\Media\Contracts\DeliveryPolicy;
use Modules\Media\Contracts\OriginAddressing;
use Modules\Media\Domain\AudioRendition;
use Modules\Media\Domain\DashManifestWriter;
use Modules\Media\Domain\FrameRate;
use Modules\Media\Domain\HlsMultivariantWriter;
use Modules\Media\Domain\Ladder;
use Modules\Media\Domain\PackagingProfile;
use Modules\Media\Domain\Rung;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Golden-file tests on generated manifests.
 *
 * A manifest is a **client contract**, and it behaves like one: a change that
 * looks harmless breaks a television that will never receive an update. Unlike
 * an API contract, a manifest regression is invisible in every API test —
 * status codes stay 200 and JSON shapes stay identical while players stall.
 *
 * So the assertion is byte-for-byte against a committed file. A diff in review
 * is the point: it forces someone to look at what a player will now receive and
 * say whether that was intended.
 */
final class ManifestGoldenTest extends TestCase
{
    private const GOLDEN = __DIR__.'/../../Golden/manifests/';

    #[Test]
    public function the_hls_multivariant_playlist_matches_its_golden_file(): void
    {
        $this->assertMatchesGolden(
            'live-full.m3u8',
            (new HlsMultivariantWriter)->write(
                $this->ladder(),
                $this->packaging(),
                DeliveryPolicy::unrestricted(),
                $this->origin(),
            ),
        );
    }

    #[Test]
    public function a_capped_hls_playlist_omits_the_rungs_the_viewer_may_not_have(): void
    {
        $capped = (new HlsMultivariantWriter)->write(
            $this->ladder(),
            $this->packaging(),
            DeliveryPolicy::fromSlug('h720'),
            $this->origin(),
        );

        $this->assertMatchesGolden('live-h720.m3u8', $capped);

        // Stated separately from the golden comparison, because this is the
        // property that makes a resolution cap real rather than advisory: the
        // rung is *absent*, not marked unavailable.
        $this->assertStringNotContainsString('1920x1080', $capped);
        $this->assertStringNotContainsString('v1080p', $capped);
    }

    #[Test]
    public function the_dash_manifest_matches_its_golden_file(): void
    {
        $this->assertMatchesGolden(
            'live-full.mpd',
            (new DashManifestWriter)->write(
                $this->ladder(),
                $this->packaging(),
                DeliveryPolicy::unrestricted(),
                $this->origin(),
                new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
                new DateTimeImmutable('2026-08-11T12:00:00+00:00'),
            ),
        );
    }

    #[Test]
    public function a_capped_dash_manifest_matches_its_golden_file(): void
    {
        $this->assertMatchesGolden(
            'live-h720.mpd',
            (new DashManifestWriter)->write(
                $this->ladder(),
                $this->packaging(),
                DeliveryPolicy::fromSlug('h720'),
                $this->origin(),
                new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
                new DateTimeImmutable('2026-08-11T12:00:00+00:00'),
            ),
        );
    }

    #[Test]
    public function the_dash_manifest_is_well_formed_xml(): void
    {
        $mpd = (new DashManifestWriter)->write(
            $this->ladder(),
            $this->packaging(),
            DeliveryPolicy::unrestricted(),
            $this->origin(),
            new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-08-11T12:00:00+00:00'),
        );

        // Independent of the golden file: a golden file records what we
        // produce, and would happily record malformed XML if we started
        // producing it.
        $document = new \DOMDocument;
        $this->assertTrue($document->loadXML($mpd), 'the MPD must parse');
        $this->assertSame('urn:mpeg:dash:schema:mpd:2011', $document->documentElement?->namespaceURI);
    }

    #[Test]
    public function generation_is_deterministic(): void
    {
        // Two runs of the same inputs must be byte-identical, or the content
        // hash recorded against a publication is noise and every republication
        // looks like a change.
        $writer = new HlsMultivariantWriter;

        $this->assertSame(
            $writer->write($this->ladder(), $this->packaging(), DeliveryPolicy::unrestricted(), $this->origin()),
            $writer->write($this->ladder(), $this->packaging(), DeliveryPolicy::unrestricted(), $this->origin()),
        );
    }

    private function assertMatchesGolden(string $name, string $actual): void
    {
        $path = self::GOLDEN.$name;

        if (! is_file($path)) {
            self::fail("Golden file {$name} is missing. Generated output was:\n\n{$actual}");
        }

        $this->assertSame(
            (string) file_get_contents($path),
            $actual,
            "Generated manifest differs from tests/Golden/manifests/{$name}. "
            .'If the change is intended, update the golden file in the same commit so a reviewer sees the diff.',
        );
    }

    private function ladder(): Ladder
    {
        $rate = new FrameRate(25, 1);

        return new Ladder(
            slug: 'golden',
            contentClass: 'generic',
            rungs: [
                new Rung('v360p', 640, 360, 800, 1000, 'avc', 'test-avc', 'main', 30, $rate),
                new Rung('v720p', 1280, 720, 2500, 3200, 'avc', 'test-avc', 'high', 31, $rate),
                new Rung('v1080p', 1920, 1080, 5000, 6500, 'avc', 'test-avc', 'high', 40, $rate),
            ],
            audio: [
                new AudioRendition('audio-en', 'en', 'main', 'aac-lc', 128, 2, 48_000, 0, true),
                // A second language and an audio-description track, so the
                // golden files cover the attributes those add rather than only
                // the single-track case that never breaks.
                new AudioRendition('audio-de', 'de', 'main', 'aac-lc', 128, 2, 48_000, 1, false),
                new AudioRendition('audio-en-ad', 'en', 'description', 'aac-lc', 96, 2, 48_000, 2, false),
            ],
        );
    }

    private function packaging(): PackagingProfile
    {
        return new PackagingProfile(
            slug: 'golden',
            container: 'cmaf',
            segmentDurationMs: 2000,
            gopDurationMs: 1000,
            timescale: 90_000,
            playlistWindowSegments: 6,
            timeShiftBufferSeconds: 60,
            suggestedPresentationDelayMs: 6000,
        );
    }

    private function origin(): OriginAddressing
    {
        return OriginAddressing::fromPrefix('/live/019ff0f4-0000-7000-8000-000000000001');
    }
}
