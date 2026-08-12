<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use InvalidArgumentException;

/**
 * An ABR ladder: the video rungs and audio renditions of one encode.
 *
 * The invariants here are not style. Every one of them describes a defect that
 * is invisible in a test that plays a single rendition end to end, and that
 * appears only when a player switches rendition — which happens for viewers on
 * variable networks and almost never in an office.
 */
final readonly class Ladder
{
    /**
     * @param  list<Rung>  $rungs  ordered lowest bitrate first
     * @param  list<AudioRendition>  $audio
     */
    public function __construct(
        public string $slug,
        public string $contentClass,
        public array $rungs,
        public array $audio,
    ) {
        if ($rungs === []) {
            throw new InvalidArgumentException("Ladder '{$slug}' has no rungs.");
        }

        if ($audio === []) {
            throw new InvalidArgumentException("Ladder '{$slug}' has no audio rendition; a video-only ladder produces a silent stream.");
        }

        $this->assertLabelsUnique();
        $this->assertBitratesStrictlyIncreasing();
        $this->assertResolutionsNonDecreasing();
        $this->assertAspectRatiosConsistent();
        $this->assertFrameRatesCompatible();
        $this->assertExactlyOneDefaultAudio();
    }

    public function lowestRung(): Rung
    {
        return $this->rungs[0];
    }

    public function highestRung(): Rung
    {
        return $this->rungs[count($this->rungs) - 1];
    }

    /**
     * The rungs at or below a height cap.
     *
     * This is where a licensor's `max_resolution` usage rule and a plan's
     * resolution cap actually take effect: a capped viewer is served a manifest
     * that does not mention the rungs they may not have. Filtering in the
     * client would be a client-side security boundary, which is never one
     * (`CLAUDE.md` §7).
     *
     * @return list<Rung>
     */
    public function rungsUpTo(?int $maxHeight): array
    {
        if ($maxHeight === null) {
            return $this->rungs;
        }

        $permitted = array_values(array_filter(
            $this->rungs,
            static fn (Rung $rung): bool => $rung->height <= $maxHeight,
        ));

        /*
         * A cap below the lowest rung yields the lowest rung rather than an
         * empty manifest. An empty manifest is not a stricter outcome, it is a
         * broken one: the viewer sees a player error rather than a permitted,
         * lower-quality stream, and the support contact that follows costs more
         * than the resolution difference.
         */
        return $permitted === [] ? [$this->lowestRung()] : $permitted;
    }

    public function defaultAudio(): AudioRendition
    {
        foreach ($this->audio as $rendition) {
            if ($rendition->isDefault) {
                return $rendition;
            }
        }

        // Unreachable: the constructor refuses a ladder without exactly one.
        return $this->audio[0];
    }

    /**
     * The ladder's nominal frame rate: the highest any rung runs at.
     *
     * The highest rather than the first, because lower rungs are the ones
     * permitted to drop frames. The nominal rate is what the GOP length has to
     * be a whole number of frames at.
     */
    public function frameRate(): FrameRate
    {
        $highest = $this->rungs[0]->frameRate;

        foreach ($this->rungs as $rung) {
            if ($rung->frameRate->numerator * $highest->denominator > $highest->numerator * $rung->frameRate->denominator) {
                $highest = $rung->frameRate;
            }
        }

        return $highest;
    }

    private function assertLabelsUnique(): void
    {
        $labels = array_merge(
            array_map(static fn (Rung $r): string => $r->label, $this->rungs),
            array_map(static fn (AudioRendition $a): string => $a->label, $this->audio),
        );

        // Labels become path segments, so a duplicate is two renditions writing
        // over each other on the origin.
        $duplicates = array_keys(array_filter(array_count_values($labels), static fn (int $n): bool => $n > 1));

        if ($duplicates !== []) {
            throw new InvalidArgumentException("Ladder '{$this->slug}' reuses rendition labels: ".implode(', ', $duplicates).'.');
        }
    }

    private function assertBitratesStrictlyIncreasing(): void
    {
        foreach ($this->rungs as $index => $rung) {
            if ($index === 0) {
                continue;
            }

            $previous = $this->rungs[$index - 1];

            if ($rung->videoBitrateKbps <= $previous->videoBitrateKbps) {
                throw new InvalidArgumentException(
                    "Ladder '{$this->slug}' rung '{$rung->label}' ({$rung->videoBitrateKbps}kbps) does not exceed "
                    ."'{$previous->label}' ({$previous->videoBitrateKbps}kbps). A rung that costs storage and encoding "
                    .'time without offering more quality is never selected by an adaptive player.',
                );
            }
        }
    }

    private function assertResolutionsNonDecreasing(): void
    {
        foreach ($this->rungs as $index => $rung) {
            if ($index === 0) {
                continue;
            }

            $previous = $this->rungs[$index - 1];

            // Equal heights at different bitrates are legitimate — the same
            // resolution encoded harder for high-motion content. Going *down*
            // as bitrate goes up is not.
            if ($rung->height < $previous->height) {
                throw new InvalidArgumentException(
                    "Ladder '{$this->slug}' rung '{$rung->label}' has a higher bitrate but a lower resolution than '{$previous->label}'.",
                );
            }
        }
    }

    private function assertAspectRatiosConsistent(): void
    {
        $reference = $this->rungs[0]->aspectRatio();

        foreach ($this->rungs as $rung) {
            // One percent absorbs the rounding forced by even dimensions —
            // 854x480 is not exactly 16:9 and never can be — while still
            // catching a rung that was actually authored at the wrong shape.
            if (abs($rung->aspectRatio() - $reference) / $reference > 0.01) {
                throw new InvalidArgumentException(
                    "Ladder '{$this->slug}' rung '{$rung->label}' ({$rung->width}x{$rung->height}) has a different aspect ratio "
                    .'from the rest of the ladder, which would make the picture jump shape when the player switches.',
                );
            }
        }
    }

    private function assertFrameRatesCompatible(): void
    {
        // Measured against the *highest* rate in the ladder, not the first
        // rung's: dropping frames is something lower rungs do, so the top rate
        // is the one everything else has to divide into.
        $reference = $this->frameRate();

        foreach ($this->rungs as $rung) {
            /*
             * Rungs may drop frames — 50fps down to 25fps on the lowest rung is
             * a normal way to save bits — but only by an integer divisor, so
             * that every frame in the lower rung has an exactly coincident
             * frame in the higher one. A non-integer relationship puts IDR
             * frames at different instants and breaks switching.
             */
            $ratioNumerator = $reference->numerator * $rung->frameRate->denominator;
            $ratioDenominator = $rung->frameRate->numerator * $reference->denominator;

            if ($ratioNumerator % $ratioDenominator !== 0) {
                throw new InvalidArgumentException(
                    "Ladder '{$this->slug}' rung '{$rung->label}' runs at {$rung->frameRate->toString()}fps, which is not an integer "
                    ."divisor of the ladder's {$reference->toString()}fps. Renditions would not share IDR positions.",
                );
            }
        }
    }

    /**
     * Exactly one default audio rendition in the whole ladder — not one per
     * language.
     *
     * Every audio rendition lands in a single HLS rendition group, and RFC 8216
     * §4.3.4.1 permits at most one member of a group to carry `DEFAULT=YES`. A
     * playlist with two is malformed; players that reject it show nothing, and
     * players that accept it each pick a different track, which is the worse
     * outcome because it looks like it works.
     *
     * Other languages are still offered — they carry `AUTOSELECT=YES` and their
     * language tag, which is how a player honours a viewer's language
     * preference. "Default" means only "what plays when there is no preference
     * to honour".
     */
    private function assertExactlyOneDefaultAudio(): void
    {
        $defaults = array_values(array_filter($this->audio, static fn (AudioRendition $a): bool => $a->isDefault));

        if ($defaults === []) {
            throw new InvalidArgumentException(
                "Ladder '{$this->slug}' marks no audio rendition as default; players would each pick a different track.",
            );
        }

        if (count($defaults) > 1) {
            $labels = implode(', ', array_map(static fn (AudioRendition $a): string => $a->label, $defaults));

            throw new InvalidArgumentException(
                "Ladder '{$this->slug}' marks more than one audio rendition as default ({$labels}). "
                .'An HLS rendition group permits exactly one, and a playlist with two is malformed.',
            );
        }

        if ($defaults[0]->role === 'description') {
            throw new InvalidArgumentException(
                "Ladder '{$this->slug}' makes an audio-description track the default; it would play for every viewer "
                .'who has expressed no preference.',
            );
        }
    }
}
