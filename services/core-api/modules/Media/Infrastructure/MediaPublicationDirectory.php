<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure;

use Modules\Media\Contracts\MediaPublications;
use Modules\Media\Contracts\PublicationView;
use Modules\Media\Infrastructure\Eloquent\Publication;

final readonly class MediaPublicationDirectory implements MediaPublications
{
    public function activeFor(string $subjectType, string $subjectRef): ?PublicationView
    {
        $publication = Publication::query()
            ->with(['packaging', 'manifests'])
            ->where('subject_type', $subjectType)
            ->where('subject_ref', $subjectRef)
            ->where('status', 'published')
            ->first();

        return $publication === null ? null : $this->view($publication);
    }

    public function byOriginPrefix(string $originPrefix): ?PublicationView
    {
        $publication = Publication::query()
            ->with(['packaging', 'manifests'])
            ->where('origin_prefix', $originPrefix)
            ->where('status', 'published')
            ->first();

        return $publication === null ? null : $this->view($publication);
    }

    private function view(Publication $publication): PublicationView
    {
        /*
         * Policies and formats come from the manifests that were actually
         * written, not from the set the platform *could* generate. Advertising
         * a delivery target whose manifest does not exist would turn a
         * publication bug into a player error with no server-side signal.
         */
        $policies = [];
        $formats = [];

        foreach ($publication->manifests as $manifest) {
            $policies[(string) $manifest->policy] = true;
            $formats[(string) $manifest->format] = true;
        }

        return new PublicationView(
            uuid: (string) $publication->uuid,
            subjectType: (string) $publication->subject_type,
            subjectRef: (string) $publication->subject_ref,
            originPrefix: (string) $publication->origin_prefix,
            container: (string) $publication->packaging->container,
            policies: array_keys($policies),
            formats: array_keys($formats),
            revision: (int) $publication->revision,
        );
    }
}
