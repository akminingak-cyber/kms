<?php

declare(strict_types=1);

namespace Modules\Rights\Contracts;

/**
 * The technical constraints a licensor requires.
 *
 * These drive the DRM licence policy from Phase 6, and the concurrency cap here
 * is contractual rather than commercial — unlike a plan limit it may never fail
 * open (docs/security/playback-authorization.md §6).
 */
final readonly class UsageRules
{
    public function __construct(
        public ?string $maxResolution = null,
        public ?string $hdcp = null,
        public ?string $securityLevel = null,
        public ?int $concurrencyCap = null,
    ) {}

    /**
     * Combine restrictively.
     *
     * If any applicable right caps resolution at 720p, the cap is 720p. Taking
     * the maximum would breach the stricter agreement, which is why this is
     * never written as a "best of" merge.
     */
    public function mergeRestrictive(self $other): self
    {
        return new self(
            maxResolution: self::lowerResolution($this->maxResolution, $other->maxResolution),
            hdcp: self::higher($this->hdcp, $other->hdcp),
            securityLevel: self::higher($this->securityLevel, $other->securityLevel),
            concurrencyCap: self::lowerInt($this->concurrencyCap, $other->concurrencyCap),
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'max_resolution' => $this->maxResolution,
            'hdcp' => $this->hdcp,
            'security_level' => $this->securityLevel,
            'concurrency_cap' => $this->concurrencyCap,
        ], static fn ($v): bool => $v !== null);
    }

    private const RESOLUTION_ORDER = ['576p' => 1, '720p' => 2, '1080p' => 3, '2160p' => 4];

    private static function lowerResolution(?string $a, ?string $b): ?string
    {
        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        // An unrecognised value sorts last, so it can never relax a known cap.
        $rankA = self::RESOLUTION_ORDER[$a] ?? PHP_INT_MAX;
        $rankB = self::RESOLUTION_ORDER[$b] ?? PHP_INT_MAX;

        return $rankA <= $rankB ? $a : $b;
    }

    private static function higher(?string $a, ?string $b): ?string
    {
        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        return strcmp($a, $b) >= 0 ? $a : $b;
    }

    private static function lowerInt(?int $a, ?int $b): ?int
    {
        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        return min($a, $b);
    }
}
