<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

/**
 * Where generated manifests are written.
 *
 * An outbound port because the destination differs by deployment — a local
 * volume in development, object storage in production — while the thing being
 * written does not. Implementations must be atomic from a reader's point of
 * view: a player fetching a manifest mid-write must never receive half of one,
 * and "half a manifest" is not a parse error the player recovers from, it is a
 * stall.
 */
interface ManifestStore
{
    public function put(string $path, string $contents): void;

    public function get(string $path): ?string;

    public function delete(string $prefix): void;
}
