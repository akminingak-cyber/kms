<?php

declare(strict_types=1);

namespace Modules\Rights\Contracts;

/**
 * Rights' inbound port.
 *
 * Rights is upstream of everything that shows or plays content: if Rights says
 * no, no other context may say yes. This is the only way anything asks.
 */
interface AvailabilityQuery
{
    /**
     * Resolve whether a subject may be exploited in a given way, right now.
     *
     * Never throws for "not available" — that is an outcome, not an error, and
     * the caller needs to know *which* condition failed in order to return a
     * specific reason code.
     */
    public function resolve(AvailabilityRequest $request): AvailabilityVerdict;

    /**
     * Subjects available for a platform and territory, used to filter listings.
     *
     * @param  list<string>  $subjectRefs
     * @return list<string> the subset that may be shown
     */
    public function filterVisible(array $subjectRefs, string $exploitation, string $territory, string $platform): array;
}
