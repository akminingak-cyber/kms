<?php

declare(strict_types=1);

namespace Modules\Delivery\Domain;

/**
 * Why a delivery token was accepted or refused.
 *
 * Expiry and forgery are distinguished for **our** telemetry, not for the
 * caller: an expiry spike means clients are failing to renew on heartbeat, and
 * a forgery spike means an attack or a client bug. Conflating them hides both.
 * What reaches the requester is a refusal either way.
 */
final readonly class DeliveryTokenVerdict
{
    public const VALID = 'valid';

    public const EXPIRED = 'expired';

    public const INVALID = 'invalid';

    private function __construct(
        public string $outcome,
        public ?string $sessionUuid = null,
        public ?int $expiresAt = null,
    ) {}

    public static function valid(string $sessionUuid, int $expiresAt): self
    {
        return new self(self::VALID, $sessionUuid, $expiresAt);
    }

    public static function expired(string $sessionUuid): self
    {
        return new self(self::EXPIRED, $sessionUuid);
    }

    public static function invalid(): self
    {
        return new self(self::INVALID);
    }

    public function isValid(): bool
    {
        return $this->outcome === self::VALID;
    }
}
