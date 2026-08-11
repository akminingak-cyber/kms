<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

/**
 * The stable, machine-readable error code registry.
 *
 * Clients switch on these values and never on prose. Codes are append-only:
 * a code's meaning never changes once shipped, because a television released
 * three years ago is still switching on it.
 *
 * Every code here must also exist in packages/api-contracts; a conformance
 * test fails the build if the two drift apart.
 */
enum ErrorCode: string
{
    // --- Authentication -----------------------------------------------------
    case AuthRequired = 'AUTH_REQUIRED';
    case AuthInvalidCredentials = 'AUTH_INVALID_CREDENTIALS';
    case AuthTokenExpired = 'AUTH_TOKEN_EXPIRED';
    case AuthTokenInvalid = 'AUTH_TOKEN_INVALID';
    case AuthTokenRevoked = 'AUTH_TOKEN_REVOKED';
    case AuthAccountLocked = 'AUTH_ACCOUNT_LOCKED';
    case AuthAccountSuspended = 'AUTH_ACCOUNT_SUSPENDED';
    case AuthEmailNotVerified = 'AUTH_EMAIL_NOT_VERIFIED';
    case AuthRefreshReuseDetected = 'AUTH_REFRESH_REUSE_DETECTED';
    case AuthMfaRequired = 'AUTH_MFA_REQUIRED';
    case AuthMfaInvalid = 'AUTH_MFA_INVALID';

    // --- Registration and account -------------------------------------------
    case AccountEmailTaken = 'ACCOUNT_EMAIL_TAKEN';
    case AccountVerificationInvalid = 'ACCOUNT_VERIFICATION_INVALID';
    case AccountVerificationExpired = 'ACCOUNT_VERIFICATION_EXPIRED';
    case AccountResetInvalid = 'ACCOUNT_RESET_INVALID';
    case AccountResetExpired = 'ACCOUNT_RESET_EXPIRED';

    // --- Profiles -----------------------------------------------------------
    case ProfileLimitReached = 'PROFILE_LIMIT_REACHED';
    case ProfileNotFound = 'PROFILE_NOT_FOUND';
    case ProfilePrimaryImmutable = 'PROFILE_PRIMARY_IMMUTABLE';
    case ProfilePinRequired = 'PROFILE_PIN_REQUIRED';
    case ProfilePinInvalid = 'PROFILE_PIN_INVALID';

    // --- Devices ------------------------------------------------------------
    case DeviceNotFound = 'DEVICE_NOT_FOUND';
    case DeviceNotRegistered = 'DEVICE_NOT_REGISTERED';
    case DeviceLimitReached = 'DEVICE_LIMIT_REACHED';
    case DeviceRemovalCooldown = 'DEVICE_REMOVAL_COOLDOWN';
    case DeviceClassUnsupported = 'DEVICE_CLASS_UNSUPPORTED';

    // --- Device activation (television pairing) ------------------------------
    case ActivationPending = 'ACTIVATION_PENDING';
    case ActivationExpired = 'ACTIVATION_EXPIRED';
    case ActivationInvalidCode = 'ACTIVATION_INVALID_CODE';
    case ActivationAlreadyUsed = 'ACTIVATION_ALREADY_USED';
    case ActivationSlowDown = 'ACTIVATION_SLOW_DOWN';

    // --- Generic ------------------------------------------------------------
    case ValidationFailed = 'VALIDATION_FAILED';
    case NotFound = 'NOT_FOUND';
    case Forbidden = 'FORBIDDEN';
    case MethodNotAllowed = 'METHOD_NOT_ALLOWED';
    case RateLimited = 'RATE_LIMITED';
    case IdempotencyKeyConflict = 'IDEMPOTENCY_KEY_CONFLICT';
    case UnsupportedMediaType = 'UNSUPPORTED_MEDIA_TYPE';
    case InternalError = 'INTERNAL_ERROR';
    case ServiceUnavailable = 'SERVICE_UNAVAILABLE';

    /**
     * The HTTP status this code is returned with.
     *
     * Kept beside the code so a status can never drift between call sites —
     * a client that has learned "403 means check your package" must not one day
     * receive the same code as a 400.
     */
    public function status(): int
    {
        return match ($this) {
            self::AuthRequired,
            self::AuthTokenExpired,
            self::AuthTokenInvalid,
            self::AuthTokenRevoked,
            self::AuthInvalidCredentials,
            self::AuthRefreshReuseDetected,
            self::AuthMfaRequired,
            self::AuthMfaInvalid => 401,

            self::AuthAccountLocked => 423,

            self::AuthAccountSuspended,
            self::AuthEmailNotVerified,
            self::Forbidden,
            self::ProfilePinRequired,
            self::ProfilePinInvalid,
            self::DeviceNotRegistered,
            self::DeviceClassUnsupported => 403,

            self::AccountEmailTaken,
            self::ProfileLimitReached,
            self::DeviceLimitReached,
            self::DeviceRemovalCooldown,
            self::ActivationAlreadyUsed,
            self::IdempotencyKeyConflict => 409,

            self::ProfileNotFound,
            self::DeviceNotFound,
            self::NotFound => 404,

            self::MethodNotAllowed => 405,
            self::UnsupportedMediaType => 415,

            self::ValidationFailed,
            self::AccountVerificationInvalid,
            self::AccountVerificationExpired,
            self::AccountResetInvalid,
            self::AccountResetExpired,
            self::ActivationInvalidCode,
            self::ActivationExpired,
            self::ProfilePrimaryImmutable => 422,

            self::ActivationPending => 428,
            self::RateLimited, self::ActivationSlowDown => 429,
            self::ServiceUnavailable => 503,
            self::InternalError => 500,
        };
    }

    /** Short, non-localised summary. Clients localise from the code, not from this. */
    public function title(): string
    {
        return match ($this) {
            self::AuthRequired => 'Authentication required',
            self::AuthInvalidCredentials => 'Invalid credentials',
            self::AuthTokenExpired => 'Access token expired',
            self::AuthTokenInvalid => 'Access token invalid',
            self::AuthTokenRevoked => 'Access token revoked',
            self::AuthAccountLocked => 'Account temporarily locked',
            self::AuthAccountSuspended => 'Account suspended',
            self::AuthEmailNotVerified => 'Email address not verified',
            self::AuthRefreshReuseDetected => 'Refresh token reuse detected',
            self::AuthMfaRequired => 'Multi-factor authentication required',
            self::AuthMfaInvalid => 'Multi-factor code invalid',
            self::AccountEmailTaken => 'Email address already registered',
            self::AccountVerificationInvalid => 'Verification token invalid',
            self::AccountVerificationExpired => 'Verification token expired',
            self::AccountResetInvalid => 'Reset token invalid',
            self::AccountResetExpired => 'Reset token expired',
            self::ProfileLimitReached => 'Profile limit reached',
            self::ProfileNotFound => 'Profile not found',
            self::ProfilePrimaryImmutable => 'Primary profile cannot be removed',
            self::ProfilePinRequired => 'Profile PIN required',
            self::ProfilePinInvalid => 'Profile PIN invalid',
            self::DeviceNotFound => 'Device not found',
            self::DeviceNotRegistered => 'Device not registered',
            self::DeviceLimitReached => 'Device limit reached',
            self::DeviceRemovalCooldown => 'Device removal cooldown in effect',
            self::DeviceClassUnsupported => 'Device class not supported',
            self::ActivationPending => 'Activation pending approval',
            self::ActivationExpired => 'Activation code expired',
            self::ActivationInvalidCode => 'Activation code invalid',
            self::ActivationAlreadyUsed => 'Activation code already used',
            self::ActivationSlowDown => 'Polling too frequently',
            self::ValidationFailed => 'Validation failed',
            self::NotFound => 'Resource not found',
            self::Forbidden => 'Not permitted',
            self::MethodNotAllowed => 'Method not allowed',
            self::RateLimited => 'Too many requests',
            self::IdempotencyKeyConflict => 'Idempotency key conflict',
            self::UnsupportedMediaType => 'Unsupported media type',
            self::InternalError => 'Internal error',
            self::ServiceUnavailable => 'Service unavailable',
        };
    }

    /** The RFC 9457 `type` URI for this code. */
    public function type(): string
    {
        $slug = strtolower(str_replace('_', '-', $this->value));

        return rtrim((string) config('kms.api.problem_base_uri'), '/').'/'.$slug;
    }
}
