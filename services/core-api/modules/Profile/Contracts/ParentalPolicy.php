<?php

declare(strict_types=1);

namespace Modules\Profile\Contracts;

/**
 * Profile's inbound port for parental control.
 *
 * Enforced server-side at playback; the client showing or hiding a title is
 * presentation only. The decision is recorded, because disputes about what a
 * child was able to watch are answered from records.
 */
interface ParentalPolicy
{
    /**
     * @param  string|null  $ageRating  null means the content carries no rating, which
     *                                  is treated as unrated rather than as unrestricted
     */
    public function permits(string $accountUuid, string $profileUuid, ?string $ageRating): bool;
}
