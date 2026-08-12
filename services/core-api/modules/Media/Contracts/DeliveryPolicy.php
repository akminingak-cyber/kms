<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

/**
 * The quality class a manifest is generated for.
 *
 * A licensor's `max_resolution` usage rule and a plan's resolution cap both
 * have to reach the player, and the only place they can be enforced is the
 * manifest: a client that filters its own renditions is a client-side security
 * boundary, and the client is never a security boundary (`CLAUDE.md` §7).
 *
 * But a manifest generated *per viewer* is a cache entry with one consumer, and
 * cache offload is the dominant cost lever in delivery
 * (`docs/streaming/packaging-and-delivery.md` §5). So the cap is quantised into
 * this **small closed set**. Every viewer with the same cap shares one manifest,
 * the class appears in the path, and the number of manifest variants per
 * publication stays equal to the number of classes rather than the number of
 * subscribers.
 *
 * Quantisation always rounds **down**. A 900-line cap becomes the 720 class,
 * not the 1080 class: serving one rung less than permitted costs a little
 * quality, and serving one rung more than permitted is a contract breach.
 */
final readonly class DeliveryPolicy
{
    /**
     * Ordered ascending. Append-only: a stored manifest path must keep meaning
     * what it meant, exactly like an error code or a bitmask position.
     *
     * @var array<string,int|null>
     */
    private const CLASSES = [
        'h576' => 576,
        'h720' => 720,
        'h1080' => 1080,
        'h2160' => 2160,
        'full' => null,
    ];

    /** Resolution labels as they appear in a licensor's usage rules. */
    private const LABEL_HEIGHTS = [
        '576p' => 576,
        '720p' => 720,
        '1080p' => 1080,
        '2160p' => 2160,
    ];

    private function __construct(
        public string $slug,
        public ?int $maxHeight,
    ) {}

    public static function unrestricted(): self
    {
        return new self('full', null);
    }

    /** @return list<self> */
    public static function all(): array
    {
        return array_map(
            static fn (string $slug): self => new self($slug, self::CLASSES[$slug]),
            array_keys(self::CLASSES),
        );
    }

    public static function isKnownSlug(string $slug): bool
    {
        return array_key_exists($slug, self::CLASSES);
    }

    public static function fromSlug(string $slug): ?self
    {
        return self::isKnownSlug($slug) ? new self($slug, self::CLASSES[$slug]) : null;
    }

    /**
     * The strictest class that satisfies every cap given.
     *
     * Nulls are absent caps, not permissive ones. An **unrecognised** label is
     * treated as the strictest class rather than ignored: a usage rule we
     * cannot interpret is not a usage rule we may disregard.
     *
     * @param  list<string|null>  $resolutionLabels
     */
    public static function forCaps(array $resolutionLabels): self
    {
        $heights = [];

        foreach ($resolutionLabels as $label) {
            if ($label === null) {
                continue;
            }

            $heights[] = self::LABEL_HEIGHTS[$label] ?? self::lowestClassHeight();
        }

        if ($heights === []) {
            return self::unrestricted();
        }

        return self::quantise(min($heights));
    }

    /**
     * Narrows to also satisfy a height cap from another source.
     *
     * Device capability and licensor policy are independent constraints that
     * arrive from different places; combining them here keeps "strictest wins"
     * in one method rather than at each call site.
     */
    public function narrowTo(?int $maxHeight): self
    {
        if ($maxHeight === null) {
            return $this;
        }

        $narrowed = self::quantise($maxHeight);

        if ($this->maxHeight === null) {
            return $narrowed;
        }

        return $narrowed->maxHeight !== null && $narrowed->maxHeight < $this->maxHeight ? $narrowed : $this;
    }

    /** Rounds a height cap down to the nearest class. */
    public static function quantise(int $maxHeight): self
    {
        $chosen = null;

        foreach (self::CLASSES as $slug => $height) {
            if ($height !== null && $height <= $maxHeight) {
                $chosen = new self($slug, $height);
            }
        }

        // Below the lowest class, the lowest class still applies: the ladder
        // itself guarantees at least one playable rung.
        return $chosen ?? new self(array_key_first(self::CLASSES), self::lowestClassHeight());
    }

    private static function lowestClassHeight(): int
    {
        return (int) self::CLASSES[array_key_first(self::CLASSES)];
    }
}
