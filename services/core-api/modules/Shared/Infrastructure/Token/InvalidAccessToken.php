<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Token;

use Modules\Shared\Http\ErrorCode;
use RuntimeException;

/**
 * Three distinct failures, because the client's correct response differs:
 * expired means refresh silently, revoked and malformed mean sign out.
 */
final class InvalidAccessToken extends RuntimeException
{
    private function __construct(public readonly ErrorCode $errorCode)
    {
        parent::__construct($errorCode->title());
    }

    public static function expired(): self
    {
        return new self(ErrorCode::AuthTokenExpired);
    }

    public static function revoked(): self
    {
        return new self(ErrorCode::AuthTokenRevoked);
    }

    public static function malformed(): self
    {
        return new self(ErrorCode::AuthTokenInvalid);
    }
}
