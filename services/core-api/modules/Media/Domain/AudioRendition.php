<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use InvalidArgumentException;

/**
 * One audio rendition.
 *
 * Separate from the video rungs because audio and video are switched
 * independently by the player, and because a dub or a commentary track can be
 * licensed differently from the picture
 * (`docs/streaming/ingest-and-transcoding.md` §4).
 */
final readonly class AudioRendition
{
    /** @var list<string> */
    public const ROLES = ['main', 'description', 'commentary', 'dub'];

    public function __construct(
        public string $label,
        public string $language,
        public string $role,
        public string $codec,
        public int $bitrateKbps,
        public int $channels,
        public int $sampleRateHz,
        /**
         * Which audio stream of the contribution feed carries this rendition.
         *
         * Declared rather than inferred from position: the stream order of a
         * live feed is a property of the feed, it differs between sources, and
         * a wrong guess silently produces a channel whose "English" track is
         * the Spanish commentary.
         */
        public int $sourceStreamIndex,
        public bool $isDefault,
        public ?string $codecString = null,
    ) {
        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/', $label) !== 1) {
            throw new InvalidArgumentException("Audio label '{$label}' must be lowercase alphanumeric with hyphens or underscores; it becomes a URL path segment.");
        }

        // BCP 47 in its common shapes: `en`, `en-GB`, `pt-BR`. Manifests carry
        // this verbatim and players match on it, so a malformed tag silently
        // means "no audio in your language".
        if (preg_match('/^[a-z]{2,3}(-[A-Z][a-z]{3})?(-[A-Z]{2})?$/', $language) !== 1) {
            throw new InvalidArgumentException("Audio language '{$language}' is not a well-formed BCP 47 tag.");
        }

        if (! in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException("Audio role '{$role}' is not one of: ".implode(', ', self::ROLES).'.');
        }

        if ($codec !== 'aac-lc' && $codecString === null) {
            throw new InvalidArgumentException(
                "Audio rendition '{$label}' uses '{$codec}', whose RFC 6381 codec string this platform does not derive. "
                .'Supply it explicitly from the encoder output rather than guessing it.',
            );
        }

        if ($bitrateKbps <= 0) {
            throw new InvalidArgumentException("Audio rendition '{$label}' must have a positive bitrate.");
        }

        if ($channels < 1 || $channels > 16) {
            throw new InvalidArgumentException("Audio rendition '{$label}' has an implausible channel count of {$channels}.");
        }

        // DASH requires the sampling rate in the manifest, and a wrong value
        // makes a player mis-time the audio track against the video one.
        if (! in_array($sampleRateHz, [44_100, 48_000], true)) {
            throw new InvalidArgumentException("Audio rendition '{$label}' has an unsupported sample rate of {$sampleRateHz}Hz.");
        }

        if ($sourceStreamIndex < 0) {
            throw new InvalidArgumentException("Audio rendition '{$label}' has a negative source stream index.");
        }
    }

    public function codecString(): string
    {
        return $this->codecString ?? VideoCodec::aacLcCodecString();
    }
}
