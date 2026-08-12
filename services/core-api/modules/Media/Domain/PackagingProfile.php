<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use InvalidArgumentException;

/**
 * How a ladder is cut into segments and described to players.
 *
 * Segment duration is the single most consequential number in the media plane.
 * It trades latency and channel-change time against compression efficiency,
 * request volume and cache behaviour
 * (`docs/streaming/packaging-and-delivery.md` §1), and changing it later
 * invalidates every cached object for the affected content.
 */
final readonly class PackagingProfile
{
    /** @var list<string> */
    public const CONTAINERS = ['cmaf', 'ts'];

    public function __construct(
        public string $slug,
        public string $container,
        public int $segmentDurationMs,
        public int $gopDurationMs,
        public int $timescale,
        public int $playlistWindowSegments,
        public int $timeShiftBufferSeconds,
        public int $suggestedPresentationDelayMs,
        /**
         * Null until Phase 6. Absent rather than stubbed: a packaging profile
         * that claims an encryption scheme it does not apply would produce
         * manifests advertising protection that is not there.
         */
        public ?string $encryptionScheme = null,
    ) {
        if (! in_array($container, self::CONTAINERS, true)) {
            throw new InvalidArgumentException("Container '{$container}' is not one of: ".implode(', ', self::CONTAINERS).'.');
        }

        if ($segmentDurationMs <= 0 || $gopDurationMs <= 0) {
            throw new InvalidArgumentException('Segment and GOP durations must be positive.');
        }

        /*
         * The alignment requirement, enforced rather than documented.
         *
         * A segment boundary must fall on an IDR frame in every rendition, so
         * the segment must be a whole number of GOPs. Misalignment does not
         * fail cleanly: it produces artefacts and stalls *at switch points
         * only*, which means it looks perfect on a fast connection and fails
         * for viewers on variable networks — the exact population ABR exists to
         * serve (`docs/streaming/ingest-and-transcoding.md` §3).
         */
        if ($segmentDurationMs % $gopDurationMs !== 0) {
            throw new InvalidArgumentException(
                "Segment duration {$segmentDurationMs}ms is not a whole number of {$gopDurationMs}ms GOPs. "
                .'Segment boundaries would not land on IDR frames in every rendition.',
            );
        }

        if ($timescale <= 0) {
            throw new InvalidArgumentException('Timescale must be positive.');
        }

        // The segment duration has to be an exact integer in the timescale, or
        // the presentation timeline drifts a little on every segment.
        if (($segmentDurationMs * $timescale) % 1000 !== 0) {
            throw new InvalidArgumentException(
                "Segment duration {$segmentDurationMs}ms is not an exact integer at timescale {$timescale}.",
            );
        }

        if ($playlistWindowSegments < 3) {
            throw new InvalidArgumentException('A live playlist window shorter than three segments leaves no room for a player to buffer.');
        }

        if ($timeShiftBufferSeconds < $this->windowDurationSeconds()) {
            throw new InvalidArgumentException(
                'The time-shift buffer must cover at least the advertised playlist window, '
                ."or players will request segments that have already been removed ({$timeShiftBufferSeconds}s < {$this->windowDurationSeconds()}s).",
            );
        }
    }

    public function segmentDurationInTimescale(): int
    {
        return intdiv($this->segmentDurationMs * $this->timescale, 1000);
    }

    public function windowDurationSeconds(): int
    {
        return (int) ceil($this->playlistWindowSegments * $this->segmentDurationMs / 1000);
    }

    /**
     * `EXT-X-TARGETDURATION` is an integer number of seconds and must be no
     * less than any segment's actual duration, so it rounds up.
     */
    public function targetDurationSeconds(): int
    {
        return (int) ceil($this->segmentDurationMs / 1000);
    }

    /**
     * fMP4 segments need HLS 7; MPEG-TS with a separate audio rendition group
     * needs 4, which every ladder here has.
     *
     * Advertising a lower version than the features used makes a strict player
     * reject the playlist; advertising a higher one makes an old player reject
     * a playlist it could have handled.
     */
    public function hlsVersion(): int
    {
        return $this->container === 'cmaf' ? 7 : 4;
    }

    public function segmentSuffix(): string
    {
        return $this->container === 'cmaf' ? 'm4s' : 'ts';
    }

    /** Whether the packaged output carries an initialisation segment. */
    public function hasInitialisationSegment(): bool
    {
        return $this->container === 'cmaf';
    }

    /** Whether every rendition's GOP is a whole number of frames at this rate. */
    public function gopIsWholeFrames(FrameRate $rate): bool
    {
        return $rate->containsWholeFramesIn($this->gopDurationMs);
    }
}
