<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

/**
 * Media's inbound port.
 *
 * Delivery asks it where a channel's media lives. Playback asks it whether
 * anything has been published at all — because a viewer who is entitled,
 * licensed and in the right territory still cannot watch a channel nobody has
 * encoded, and answering that with an empty target list would push the failure
 * into the player as an unexplained error.
 */
interface MediaPublications
{
    public function activeFor(string $subjectType, string $subjectRef): ?PublicationView;

    /** Resolves a request path back to the publication that owns it, for origin authorization. */
    public function byOriginPrefix(string $originPrefix): ?PublicationView;
}
