<?php

declare(strict_types=1);

namespace Modules\Playback\Contracts;

/**
 * Everything the decision needs.
 *
 * Account, profile, device and device class come from the token, never from the
 * request body: a client-asserted device identifier would let a caller inherit
 * another device's class, which is an input to the rights decision.
 */
final readonly class PlaybackRequest
{
    /** @param array<string,mixed> $capabilities */
    public function __construct(
        public string $accountUuid,
        public string $profileUuid,
        public string $deviceUuid,
        public string $deviceClass,
        public string $contentRef,
        public string $mode,
        public ?string $territory,
        public ?string $territoryMethod = null,
        public array $capabilities = [],
        public ?string $correlationId = null,
        public ?string $client = null,
        public ?string $clientVersion = null,
    ) {}
}
