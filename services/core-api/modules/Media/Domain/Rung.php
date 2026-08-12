<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use InvalidArgumentException;

/** One video rung of an ABR ladder. */
final readonly class Rung
{
    public function __construct(
        public string $label,
        public int $width,
        public int $height,
        public int $videoBitrateKbps,
        public int $maxBitrateKbps,
        public string $codec,
        public string $encoder,
        public string $profile,
        public int $level,
        public FrameRate $frameRate,
        public ?string $codecString = null,
    ) {
        if ($label === '' || preg_match('/^[a-z0-9][a-z0-9_-]*$/', $label) !== 1) {
            throw new InvalidArgumentException("Rung label '{$label}' must be lowercase alphanumeric with hyphens or underscores; it becomes a URL path segment.");
        }

        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException("Rung '{$label}' must have a positive resolution.");
        }

        // Encoders reject odd dimensions for 4:2:0 chroma subsampling, which is
        // every rung this platform will produce.
        if ($width % 2 !== 0 || $height % 2 !== 0) {
            throw new InvalidArgumentException("Rung '{$label}' resolution {$width}x{$height} must have even dimensions for 4:2:0 chroma subsampling.");
        }

        if ($videoBitrateKbps <= 0) {
            throw new InvalidArgumentException("Rung '{$label}' must have a positive bitrate.");
        }

        if ($maxBitrateKbps < $videoBitrateKbps) {
            throw new InvalidArgumentException("Rung '{$label}' peak bitrate {$maxBitrateKbps} is below its average {$videoBitrateKbps}.");
        }

        if (! VideoCodec::isKnown($codec)) {
            throw new InvalidArgumentException("Rung '{$label}' uses unknown codec '{$codec}'.");
        }

        if ($codecString === null && ! VideoCodec::derivesCodecString($codec)) {
            throw new InvalidArgumentException(
                "Rung '{$label}' uses '{$codec}', whose RFC 6381 codec string this platform does not derive. "
                .'Supply it explicitly from the encoder output rather than guessing it.',
            );
        }
    }

    /** The RFC 6381 string a manifest advertises for this rung. */
    public function codecString(): string
    {
        return $this->codecString ?? VideoCodec::avcCodecString($this->profile, $this->level);
    }

    /** HLS `BANDWIDTH` is peak, in bits per second, and includes audio. */
    public function peakBitsPerSecond(int $audioBitrateKbps = 0): int
    {
        return ($this->maxBitrateKbps + $audioBitrateKbps) * 1000;
    }

    public function averageBitsPerSecond(int $audioBitrateKbps = 0): int
    {
        return ($this->videoBitrateKbps + $audioBitrateKbps) * 1000;
    }

    /** Aspect ratio as a float, used only to compare rungs against each other. */
    public function aspectRatio(): float
    {
        return $this->width / $this->height;
    }
}
