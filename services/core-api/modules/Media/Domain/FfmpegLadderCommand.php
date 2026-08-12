<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use RuntimeException;

/**
 * The FFmpeg encode arguments for one ladder.
 *
 * ## What this is, and what it deliberately is not
 *
 * This builds the **encode** stage: one input, one scaling graph, and one set
 * of encoder options per rung, such that every rendition shares a presentation
 * timeline and places IDR frames at identical instants. That stage is fully
 * determined by the ladder and the packaging profile, so it is derived here and
 * asserted by golden-file tests on the argument vector.
 *
 * It stops before the **muxer**. Packager selection is an open Phase 4/5
 * decision with a licence gate on it, and the muxing flags differ enough
 * between candidates that writing them now would be writing code against a
 * guess (`CLAUDE.md` §1.3). The caller supplies the output stage; without one,
 * `toShellCommand()` refuses rather than emitting something that looks runnable
 * and is not.
 *
 * Nothing here executes FFmpeg. There is no process adapter in this phase, and
 * none is implied: the container this repository was initialised in has no
 * FFmpeg binary (inspection finding E3), so an adapter written here could not
 * be exercised even once — not even on its failure path
 * (`CLAUDE.md` §8, §12). The arguments are the contract with FFmpeg; that
 * the binary accepts them must be confirmed on a machine that has one, and the
 * items below marked `[UNVERIFIED]` are the ones to confirm first.
 *
 * `[UNVERIFIED]` — pending execution against a pinned FFmpeg build:
 *  - `-level:v:N` accepts the decimal form (`4.0`) as well as the integer one.
 *  - `-sc_threshold 0` remains the effective way to suppress scene-cut IDR
 *    insertion for the selected encoder, rather than an encoder-specific
 *    parameter.
 *  - Filter-graph label syntax survives the shell quoting used by the runner.
 */
final readonly class FfmpegLadderCommand
{
    public function __construct(
        private Ladder $ladder,
        private PackagingProfile $profile,
        private string $inputUrl,
    ) {}

    /**
     * The argument vector, without the `ffmpeg` program name.
     *
     * A list of arguments rather than a string: a command assembled by string
     * concatenation is a command with a quoting bug in it, and the input URL
     * comes from configuration that an operator edits.
     *
     * @return list<string>
     */
    public function arguments(): array
    {
        $arguments = [
            '-hide_banner',
            // Never read the terminal: under a supervisor there is no terminal,
            // and FFmpeg consuming stdin has been known to swallow a keystroke
            // and stop a live encode.
            '-nostdin',
            '-loglevel', 'warning',
            // Timestamps from a live contribution feed are frequently absent or
            // discontinuous, and a missing PTS invalidates the whole timeline
            // that segment alignment depends on.
            '-fflags', '+genpts',
            '-i', $this->inputUrl,
            '-filter_complex', $this->filterGraph(),
        ];

        foreach ($this->ladder->rungs as $index => $rung) {
            array_push($arguments, ...$this->videoArguments($rung, $index));
        }

        foreach ($this->ladder->audio as $index => $rendition) {
            array_push($arguments, ...$this->audioArguments($rendition, $index));
        }

        return $arguments;
    }

    /**
     * A copy-pasteable command, for an operator reproducing an encode by hand.
     *
     * @param  list<string>  $outputStage  muxer arguments, from whichever packager was selected
     */
    public function toShellCommand(array $outputStage): string
    {
        if ($outputStage === []) {
            throw new RuntimeException(
                'No output stage was supplied. The packager is an open decision (OQ-8 and the P3 checklist), '
                .'and a command with no muxer would look runnable without being so.',
            );
        }

        $parts = array_merge(['ffmpeg'], $this->arguments(), $outputStage);

        return implode(' ', array_map(escapeshellarg(...), $parts));
    }

    /**
     * `split` then one `scale` per rung.
     *
     * One decode feeding every rung, rather than one FFmpeg process per rung:
     * decoding once is the larger saving, and — more importantly — it is what
     * guarantees the rungs share a timeline. Independent processes would each
     * establish their own, and the resulting misalignment appears only at
     * rendition switches, which is to say only for viewers on variable
     * networks.
     */
    private function filterGraph(): string
    {
        $count = count($this->ladder->rungs);
        $outputs = '';

        for ($i = 0; $i < $count; $i++) {
            $outputs .= '[t'.$i.']';
        }

        $graph = ['[0:v]split='.$count.$outputs];

        foreach ($this->ladder->rungs as $index => $rung) {
            $graph[] = sprintf(
                '[t%d]scale=w=%d:h=%d:force_original_aspect_ratio=decrease,setsar=1[v%d]',
                $index,
                $rung->width,
                $rung->height,
                $index,
            );
        }

        return implode(';', $graph);
    }

    /** @return list<string> */
    private function videoArguments(Rung $rung, int $index): array
    {
        $gopFrames = $rung->frameRate->framesIn($this->profile->gopDurationMs);

        return [
            '-map', '[v'.$index.']',
            '-c:v:'.$index, $rung->encoder,
            '-b:v:'.$index, $rung->videoBitrateKbps.'k',
            '-maxrate:v:'.$index, $rung->maxBitrateKbps.'k',
            /*
             * A one-second VBV buffer at the peak rate. Larger buffers let the
             * encoder overshoot for longer, which produces a stream whose
             * instantaneous rate exceeds what the ABR rung advertises — so a
             * player that selected it on a matching connection stalls.
             */
            '-bufsize:v:'.$index, $rung->maxBitrateKbps.'k',
            '-profile:v:'.$index, $rung->profile,
            '-level:v:'.$index, $this->levelString($rung->level),
            '-pix_fmt:v:'.$index, 'yuv420p',
            '-r:v:'.$index, $rung->frameRate->toString(),
            /*
             * The alignment triple. A fixed GOP, a minimum equal to it so the
             * encoder cannot shorten one, and scene-cut detection off so it
             * cannot insert an IDR anywhere else. All three are required: any
             * one of them alone still allows IDR frames to land in different
             * places in different rungs, which breaks ABR switching in a way no
             * single-rendition test can see.
             */
            '-g:v:'.$index, (string) $gopFrames,
            '-keyint_min:v:'.$index, (string) $gopFrames,
            '-sc_threshold:v:'.$index, '0',
        ];
    }

    /** @return list<string> */
    private function audioArguments(AudioRendition $rendition, int $index): array
    {
        return [
            // No `?` suffix: a missing audio stream must fail the encode. The
            // optional form would produce a silent channel that looks healthy
            // in every server-side metric.
            '-map', 'a:'.$rendition->sourceStreamIndex,
            '-c:a:'.$index, $rendition->codec === 'aac-lc' ? 'aac' : $rendition->codec,
            '-b:a:'.$index, $rendition->bitrateKbps.'k',
            '-ac:a:'.$index, (string) $rendition->channels,
            '-ar:a:'.$index, (string) $rendition->sampleRateHz,
        ];
    }

    /** Levels are stored as level × 10 and written as a decimal. */
    private function levelString(int $level): string
    {
        return number_format($level / 10, 1, '.', '');
    }
}
