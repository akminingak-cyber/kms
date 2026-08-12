<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use Modules\Media\Domain\AudioRendition;
use Modules\Media\Domain\FfmpegLadderCommand;
use Modules\Media\Domain\FrameRate;
use Modules\Media\Domain\Ladder;
use Modules\Media\Domain\PackagingProfile;
use Modules\Media\Domain\Rung;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The FFmpeg encode arguments.
 *
 * The argument vector is the contract with FFmpeg, and it is what can be
 * verified here: this environment has no FFmpeg binary (inspection finding E3),
 * so that the binary *accepts* these arguments must be confirmed elsewhere.
 * What these tests prove is that the arguments say what the ladder and the
 * packaging profile mean — in particular the three settings that keep IDR
 * frames aligned across renditions, which is the property no playback test can
 * observe.
 */
final class FfmpegLadderCommandTest extends TestCase
{
    #[Test]
    public function it_decodes_once_and_scales_per_rung(): void
    {
        $arguments = $this->command()->arguments();
        $graph = $arguments[array_search('-filter_complex', $arguments, true) + 1];

        // One decode feeding every rung. Separate processes would each
        // establish their own timeline, and the resulting misalignment shows up
        // only at rendition switches.
        $this->assertSame(
            '[0:v]split=2[t0][t1];'
            .'[t0]scale=w=640:h=360:force_original_aspect_ratio=decrease,setsar=1[v0];'
            .'[t1]scale=w=1280:h=720:force_original_aspect_ratio=decrease,setsar=1[v1]',
            $graph,
        );
    }

    #[Test]
    public function the_gop_is_expressed_in_frames_and_pinned_three_ways(): void
    {
        $arguments = $this->command()->arguments();

        // 1000ms at 25fps is 25 frames.
        $this->assertContainsPair($arguments, '-g:v:0', '25');
        // A minimum equal to the target, so the encoder cannot shorten a GOP…
        $this->assertContainsPair($arguments, '-keyint_min:v:0', '25');
        // …and scene-cut detection off, so it cannot insert an IDR elsewhere.
        // Any one of the three alone still permits misalignment.
        $this->assertContainsPair($arguments, '-sc_threshold:v:0', '0');
    }

    #[Test]
    public function a_half_rate_rung_gets_half_the_gop_frames_for_the_same_duration(): void
    {
        $ladder = new Ladder('mixed', 'sport', [
            new Rung('a', 640, 360, 800, 1000, 'avc', 'test-avc', 'main', 30, new FrameRate(25, 1)),
            new Rung('b', 1280, 720, 2500, 3000, 'avc', 'test-avc', 'high', 31, new FrameRate(50, 1)),
        ], [$this->audio()]);

        $arguments = (new FfmpegLadderCommand($ladder, $this->packaging(), 'srt://ingest'))->arguments();

        // Both GOPs are one second long, which is what keeps the IDR frames
        // coincident — the frame counts differ precisely because the rates do.
        $this->assertContainsPair($arguments, '-g:v:0', '25');
        $this->assertContainsPair($arguments, '-g:v:1', '50');
    }

    #[Test]
    public function the_vbv_buffer_matches_the_advertised_peak(): void
    {
        $arguments = $this->command()->arguments();

        // A larger buffer would let the encoder overshoot for longer, producing
        // a stream whose instantaneous rate exceeds what the manifest
        // advertises — so a player that selected the rung on a matching
        // connection stalls.
        $this->assertContainsPair($arguments, '-maxrate:v:0', '1000k');
        $this->assertContainsPair($arguments, '-bufsize:v:0', '1000k');
    }

    #[Test]
    public function audio_is_mapped_from_its_declared_source_stream(): void
    {
        $ladder = new Ladder('multi', 'generic', [
            new Rung('a', 640, 360, 800, 1000, 'avc', 'test-avc', 'main', 30, new FrameRate(25, 1)),
        ], [
            new AudioRendition('audio-en', 'en', 'main', 'aac-lc', 128, 2, 48_000, 0, true),
            // Third stream of the feed, not the second: declared rather than
            // inferred from position.
            new AudioRendition('audio-de', 'de', 'main', 'aac-lc', 128, 2, 48_000, 2, false),
        ]);

        $arguments = (new FfmpegLadderCommand($ladder, $this->packaging(), 'srt://ingest'))->arguments();

        $maps = [];
        foreach ($arguments as $index => $argument) {
            if ($argument === '-map' && str_starts_with((string) $arguments[$index + 1], 'a:')) {
                $maps[] = $arguments[$index + 1];
            }
        }

        // No `?` suffix: a missing audio stream must fail the encode rather
        // than silently produce a channel with no sound.
        $this->assertSame(['a:0', 'a:2'], $maps);
    }

    #[Test]
    public function the_input_url_is_a_single_argument_and_is_never_interpolated(): void
    {
        $hostile = 'srt://ingest:9000?streamid=a b&x=1';
        $arguments = (new FfmpegLadderCommand($this->ladder(), $this->packaging(), $hostile))->arguments();

        // A command assembled by string concatenation is a command with a
        // quoting bug in it, and the input URL comes from configuration an
        // operator edits.
        $this->assertContainsPair($arguments, '-i', $hostile);
    }

    #[Test]
    public function it_refuses_to_produce_a_shell_command_with_no_muxer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/look runnable without being so/');

        // Packager selection is an open decision with a licence gate on it, so
        // there is no output stage to emit. A command that looks complete and
        // is not would be worse than a refusal.
        $this->command()->toShellCommand([]);
    }

    /** @param list<string> $arguments */
    private function assertContainsPair(array $arguments, string $flag, string $value): void
    {
        $index = array_search($flag, $arguments, true);

        $this->assertNotFalse($index, "expected argument {$flag}");
        $this->assertSame($value, $arguments[$index + 1], "expected {$flag} {$value}");
    }

    private function command(): FfmpegLadderCommand
    {
        return new FfmpegLadderCommand($this->ladder(), $this->packaging(), 'srt://ingest:9000');
    }

    private function ladder(): Ladder
    {
        $rate = new FrameRate(25, 1);

        return new Ladder('test', 'generic', [
            new Rung('v360p', 640, 360, 800, 1000, 'avc', 'test-avc', 'main', 30, $rate),
            new Rung('v720p', 1280, 720, 2500, 3200, 'avc', 'test-avc', 'high', 31, $rate),
        ], [$this->audio()]);
    }

    private function audio(): AudioRendition
    {
        return new AudioRendition('audio-en', 'en', 'main', 'aac-lc', 128, 2, 48_000, 0, true);
    }

    private function packaging(): PackagingProfile
    {
        return new PackagingProfile('test', 'cmaf', 2000, 1000, 90_000, 6, 60, 6000);
    }
}
