<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use InvalidArgumentException;

/**
 * Codec identification, and the RFC 6381 string a manifest must carry.
 *
 * A wrong `CODECS` attribute is one of the nastier manifest defects: players
 * that trust it refuse a stream they could have played, or — worse — accept one
 * they cannot, and fail after the first segment. It is also invisible in any
 * test that does not actually parse the manifest.
 *
 * Only AVC and AAC-LC derive their codec string here, because those two
 * derivations are unambiguous. **HEVC, AV1 and VP9 must carry an explicit
 * codec string on the rung**: their strings encode profile, tier, level and
 * constraint bytes whose correct values depend on the encoder's output, and
 * guessing at them would be inventing a capability
 * (`CLAUDE.md` §1.3). The ladder validator enforces that.
 */
final class VideoCodec
{
    public const AVC = 'avc';

    public const HEVC = 'hevc';

    public const AV1 = 'av1';

    public const VP9 = 'vp9';

    /** @var list<string> */
    public const ALL = [self::AVC, self::HEVC, self::AV1, self::VP9];

    /** Codecs whose RFC 6381 string this platform derives rather than requires. */
    public const DERIVABLE = [self::AVC];

    /**
     * AVC profile_idc values, by the name an operator uses.
     *
     * @var array<string,int>
     */
    private const AVC_PROFILES = [
        'baseline' => 66,
        'main' => 77,
        'high' => 100,
        'high10' => 110,
    ];

    /**
     * constraint_set flags byte, per profile.
     *
     * Baseline streams produced by the common encoders set constraint_set0;
     * Main sets constraint_set1. High sets none. These are the values that
     * appear in the SPS, and the codec string reproduces the SPS bytes.
     *
     * @var array<string,int>
     */
    private const AVC_CONSTRAINTS = [
        'baseline' => 0x40,
        'main' => 0x40,
        'high' => 0x00,
        'high10' => 0x00,
    ];

    public static function isKnown(string $codec): bool
    {
        return in_array($codec, self::ALL, true);
    }

    public static function derivesCodecString(string $codec): bool
    {
        return in_array($codec, self::DERIVABLE, true);
    }

    /** @return list<string> */
    public static function avcProfiles(): array
    {
        return array_keys(self::AVC_PROFILES);
    }

    /**
     * `avc1.PPCCLL` — profile_idc, constraint flags, level_idc, each two hex digits.
     *
     * @param  string  $profile  one of `avcProfiles()`
     * @param  int  $level  level × 10, so 4.0 is 40 and 3.1 is 31
     */
    public static function avcCodecString(string $profile, int $level): string
    {
        if (! isset(self::AVC_PROFILES[$profile])) {
            throw new InvalidArgumentException("Unknown AVC profile '{$profile}'.");
        }

        if ($level < 10 || $level > 62) {
            throw new InvalidArgumentException("AVC level '{$level}' is outside the defined range (10–62, expressed as level × 10).");
        }

        return sprintf(
            'avc1.%02x%02x%02x',
            self::AVC_PROFILES[$profile],
            self::AVC_CONSTRAINTS[$profile],
            $level,
        );
    }

    /**
     * AAC-LC is object type 2 under MPEG-4 audio, which is `mp4a.40.2`.
     *
     * HE-AAC (`mp4a.40.5`) is deliberately not offered: it needs explicit SBR
     * signalling decisions that differ per packager, and an unverified guess
     * here would produce manifests that fail on exactly the older televisions
     * HE-AAC exists to serve.
     */
    public static function aacLcCodecString(): string
    {
        return 'mp4a.40.2';
    }
}
