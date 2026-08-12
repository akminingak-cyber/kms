<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

/**
 * What another context is allowed to know about a publication.
 *
 * Deliberately not the ladder. Delivery needs to address the origin and Playback
 * needs to know a stream exists; neither has any business knowing which rungs
 * were encoded, and publishing that here would make the ladder impossible to
 * change without a cross-context review.
 */
final readonly class PublicationView
{
    /**
     * @param  list<string>  $policies  delivery-policy slugs with a generated manifest
     * @param  list<string>  $formats  manifest formats generated, e.g. `hls`, `dash`
     */
    public function __construct(
        public string $uuid,
        public string $subjectType,
        public string $subjectRef,
        public string $originPrefix,
        public string $container,
        public array $policies,
        public array $formats,
        public int $revision,
    ) {}

    public function offersPolicy(string $slug): bool
    {
        return in_array($slug, $this->policies, true);
    }

    public function offersFormat(string $format): bool
    {
        return in_array($format, $this->formats, true);
    }
}
