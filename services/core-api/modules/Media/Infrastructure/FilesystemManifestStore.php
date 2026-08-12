<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure;

use Illuminate\Contracts\Filesystem\Filesystem;
use Modules\Media\Contracts\ManifestStore;
use RuntimeException;

/**
 * Manifests on a filesystem — a local volume in development, object storage in
 * production, chosen by the configured disk.
 *
 * Writes are staged and moved rather than written in place. A player fetching a
 * manifest while it is half-written does not get a parse error it retries; it
 * gets a playlist missing renditions, or an MPD missing a closing tag, and the
 * failure surfaces as an unexplained stall minutes later.
 */
final readonly class FilesystemManifestStore implements ManifestStore
{
    public function __construct(private Filesystem $disk) {}

    public function put(string $path, string $contents): void
    {
        $key = $this->key($path);
        $staging = $key.'.'.bin2hex(random_bytes(8)).'.tmp';

        if (! $this->disk->put($staging, $contents)) {
            throw new RuntimeException("Could not stage manifest at {$staging}.");
        }

        /*
         * Object stores have no rename, so this is a copy-then-delete there and
         * a real rename on a local filesystem. Both are atomic from a reader's
         * point of view for a single object, which is what matters; neither
         * gives atomicity *across* the manifest set, so a player can briefly see
         * a new HLS manifest alongside an old MPD. That is harmless — a player
         * reads one format — and the alternative, a two-phase publish, buys
         * nothing a player can observe.
         */
        if ($this->disk->exists($key)) {
            $this->disk->delete($key);
        }

        if (! $this->disk->move($staging, $key)) {
            $this->disk->delete($staging);

            throw new RuntimeException("Could not publish manifest to {$key}.");
        }
    }

    public function get(string $path): ?string
    {
        $key = $this->key($path);

        return $this->disk->exists($key) ? (string) $this->disk->get($key) : null;
    }

    public function delete(string $prefix): void
    {
        $this->disk->deleteDirectory($this->key($prefix));
    }

    /**
     * Origin paths are absolute; storage keys are relative.
     *
     * Traversal is rejected rather than normalised: a path that escapes the disk
     * root can only arrive from a defect, and quietly correcting it would hide
     * the defect while still writing somewhere unintended.
     */
    private function key(string $path): string
    {
        if (str_contains($path, '..')) {
            throw new RuntimeException("Refusing a manifest path containing a traversal segment: {$path}");
        }

        return ltrim($path, '/');
    }
}
