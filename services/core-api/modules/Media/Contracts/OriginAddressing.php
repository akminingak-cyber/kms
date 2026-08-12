<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use InvalidArgumentException;

/**
 * Where media lives on the origin.
 *
 * The scheme is **vendor-neutral and derived from identifiers**, so a CDN can be
 * pointed at the same origin without re-deriving URLs, and a second CDN needs no
 * change here at all (ADR-0008).
 *
 * Two properties are load-bearing and are asserted by tests:
 *
 *  - **Nothing in a path varies per viewer.** Everything that does — the
 *    session, the expiry, the signature — travels in one opaque query
 *    parameter, so an edge has exactly one thing to exclude from its cache key.
 *    Getting this wrong gives every viewer a private copy of every segment and
 *    collapses cache offload to zero.
 *  - **The delivery-policy class is part of the path**, not the token, because
 *    a capped viewer must receive a genuinely different manifest and that
 *    manifest must still be cacheable across every viewer with the same cap.
 */
final readonly class OriginAddressing
{
    private function __construct(public string $prefix) {}

    /**
     * The path prefix for one publication: `/live/{channel-uuid}` or
     * `/vod/{asset-uuid}`.
     *
     * A delivery token is scoped to a prefix rather than to a single file,
     * because a player fetches one manifest and then thousands of segments and
     * cannot obtain a token for each.
     */
    public static function forPublication(string $subjectType, string $subjectRef): self
    {
        $space = match ($subjectType) {
            'channel' => 'live',
            'asset' => 'vod',
            default => throw new InvalidArgumentException("Unknown publication subject type '{$subjectType}'."),
        };

        if (preg_match('/^[0-9a-f-]{36}$/i', $subjectRef) !== 1) {
            throw new InvalidArgumentException('A publication path is derived from a UUID.');
        }

        return new self('/'.$space.'/'.strtolower($subjectRef));
    }

    public static function fromPrefix(string $prefix): self
    {
        if (preg_match('#^/(live|vod)/[0-9a-f-]{36}$#', $prefix) !== 1) {
            throw new InvalidArgumentException("Origin prefix '{$prefix}' is not a well-formed publication prefix.");
        }

        return new self($prefix);
    }

    public function manifestPath(DeliveryPolicy $policy, string $format): string
    {
        $file = match ($format) {
            'hls' => 'manifest.m3u8',
            'dash' => 'manifest.mpd',
            default => throw new InvalidArgumentException("Unknown manifest format '{$format}'."),
        };

        return $this->prefix.'/'.$policy->slug.'/'.$file;
    }

    /** The per-rendition HLS media playlist, written by the packager. */
    public function mediaPlaylistPath(string $renditionLabel): string
    {
        return $this->prefix.'/'.$renditionLabel.'/playlist.m3u8';
    }

    /**
     * Relative, because a manifest referring to itself by absolute URL breaks
     * behind any proxy and pins the manifest to one hostname — which is exactly
     * what a second CDN needs it not to do.
     *
     * Manifests sit one level deeper than renditions, under the policy class,
     * so every reference climbs one segment. Renditions are shared across
     * policy classes: the cap decides which rungs a manifest *mentions*, never
     * where their segments live, so capped and uncapped viewers still hit the
     * same cached segments.
     */
    public function mediaPlaylistRelative(string $renditionLabel): string
    {
        return '../'.$renditionLabel.'/playlist.m3u8';
    }

    /**
     * DASH addresses every representation through one template, so the
     * rendition label is substituted by the player rather than by us.
     */
    public function initialisationTemplateRelative(): string
    {
        return '../$RepresentationID$/init.mp4';
    }

    public function segmentTemplateRelative(string $suffix): string
    {
        return '../$RepresentationID$/seg-$Number$.'.$suffix;
    }

    /**
     * Whether a request path belongs to this publication.
     *
     * Used by the origin to check a token against the path it was issued for.
     * The trailing-slash test matters: without it, a token for
     * `/live/{a}` would validate a request for `/live/{a}-other`.
     */
    public function covers(string $path): bool
    {
        return $path === $this->prefix || str_starts_with($path, $this->prefix.'/');
    }
}
