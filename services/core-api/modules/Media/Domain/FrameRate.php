<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use InvalidArgumentException;

/**
 * A frame rate as an exact rational.
 *
 * Broadcast rates are not decimals. 29.97 is 30000/1001 and 59.94 is 60000/1001,
 * and the difference is not cosmetic: a GOP length computed from 29.97 drifts
 * against one computed from 30000/1001 by a frame roughly every thirty
 * seconds, which is exactly the kind of drift that puts IDR frames in different
 * places in different renditions and breaks ABR switching.
 */
final readonly class FrameRate
{
    public function __construct(public int $numerator, public int $denominator)
    {
        if ($numerator <= 0 || $denominator <= 0) {
            throw new InvalidArgumentException('Frame rate must be positive.');
        }
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^(\d+)\/(\d+)$/', $value, $m) === 1) {
            return new self((int) $m[1], (int) $m[2]);
        }

        if (preg_match('/^\d+$/', $value) === 1) {
            return new self((int) $value, 1);
        }

        throw new InvalidArgumentException("Frame rate '{$value}' must be an integer or a rational such as 30000/1001.");
    }

    public function toString(): string
    {
        return $this->denominator === 1
            ? (string) $this->numerator
            : $this->numerator.'/'.$this->denominator;
    }

    /** Rendered for HLS `FRAME-RATE`, which takes a decimal. */
    public function toDecimalString(): string
    {
        return number_format($this->numerator / $this->denominator, 3, '.', '');
    }

    /**
     * Whether a duration in milliseconds is a whole number of frames.
     *
     * A GOP that is not a whole number of frames cannot be produced by any
     * encoder, so a ladder configured that way would silently be rounded — and
     * rounded differently in different renditions.
     */
    public function containsWholeFramesIn(int $milliseconds): bool
    {
        return ($milliseconds * $this->numerator) % ($this->denominator * 1000) === 0;
    }

    public function framesIn(int $milliseconds): int
    {
        return intdiv($milliseconds * $this->numerator, $this->denominator * 1000);
    }

    public function equals(self $other): bool
    {
        return $this->numerator * $other->denominator === $other->numerator * $this->denominator;
    }
}
