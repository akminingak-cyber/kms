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

    // --- Content ------------------------------------------------------------
    case ChannelNotFound = 'CHANNEL_NOT_FOUND';
    case ProgrammeNotFound = 'PROGRAMME_NOT_FOUND';
    case CategoryNotFound = 'CATEGORY_NOT_FOUND';
    case ScheduleRangeTooWide = 'SCHEDULE_RANGE_TOO_WIDE';

    // --- Commerce -----------------------------------------------------------
    case PackageNotFound = 'PACKAGE_NOT_FOUND';
    case PlanNotFound = 'PLAN_NOT_FOUND';
    case PlanRetired = 'PLAN_RETIRED';
    case SubscriptionNotFound = 'SUBSCRIPTION_NOT_FOUND';
    case SubscriptionAlreadyActive = 'SUBSCRIPTION_ALREADY_ACTIVE';
    case SubscriptionNotChangeable = 'SUBSCRIPTION_NOT_CHANGEABLE';

    /*
     * --- Playback denials ---------------------------------------------------
     *
     * Every denial reason gets its own code. A single generic "not available"
     * would mean support cannot help, product cannot measure, and a rights
     * misconfiguration is indistinguishable from a billing failure.
     *
     * They are specific but never leak what the caller should not know.
     */
    case PlaybackNotEntitled = 'PLAYBACK_NOT_ENTITLED';
    case PlaybackNotAvailableInTerritory = 'PLAYBACK_NOT_AVAILABLE_IN_TERRITORY';
    case PlaybackOutsideLicenceWindow = 'PLAYBACK_OUTSIDE_LICENCE_WINDOW';
    case PlaybackBlackedOut = 'PLAYBACK_BLACKED_OUT';
    case PlaybackPlatformNotPermitted = 'PLAYBACK_PLATFORM_NOT_PERMITTED';
    case PlaybackConcurrencyExceeded = 'PLAYBACK_CONCURRENCY_EXCEEDED';
    case PlaybackParentalBlocked = 'PLAYBACK_PARENTAL_BLOCKED';
    case PlaybackDeviceNotSupported = 'PLAYBACK_DEVICE_NOT_SUPPORTED';
    case PlaybackContentUnavailable = 'PLAYBACK_CONTENT_UNAVAILABLE';
    case PlaybackTerritoryUndetermined = 'PLAYBACK_TERRITORY_UNDETERMINED';
    case PlaybackModeNotPermitted = 'PLAYBACK_MODE_NOT_PERMITTED';
    /** Fail-closed outcome when a required input cannot be evaluated. */
    case PlaybackTemporarilyUnavailable = 'PLAYBACK_TEMPORARILY_UNAVAILABLE';
    case PlaybackSessionNotFound = 'PLAYBACK_SESSION_NOT_FOUND';
    /** Authorized, but nothing has been encoded and published to play. */
    case PlaybackNoDeliveryTarget = 'PLAYBACK_NO_DELIVERY_TARGET';
    /** A session that was stopped, displaced, or revalidated into a denial. */
    case PlaybackSessionEnded = 'PLAYBACK_SESSION_ENDED';

    // --- Media control plane -------------------------------------------------
    case MediaLadderNotFound = 'MEDIA_LADDER_NOT_FOUND';
    case MediaLadderInvalid = 'MEDIA_LADDER_INVALID';
    case MediaPackagingProfileNotFound = 'MEDIA_PACKAGING_PROFILE_NOT_FOUND';
    case MediaPublicationNotFound = 'MEDIA_PUBLICATION_NOT_FOUND';
    case MediaManifestNotFound = 'MEDIA_MANIFEST_NOT_FOUND';
    /** No cleared licence has been recorded for the encoder a rung asks for. */
    case MediaEncoderNotPermitted = 'MEDIA_ENCODER_NOT_PERMITTED';
    /** Segment boundaries would not land on IDR frames in every rendition. */
    case MediaSegmentAlignmentInvalid = 'MEDIA_SEGMENT_ALIGNMENT_INVALID';

    // --- Delivery (origin/edge surface) --------------------------------------
    case DeliveryTokenInvalid = 'DELIVERY_TOKEN_INVALID';
    case DeliveryTokenExpired = 'DELIVERY_TOKEN_EXPIRED';
    case DeliveryPathNotPermitted = 'DELIVERY_PATH_NOT_PERMITTED';

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
            self::DeviceClassUnsupported,
            self::PlaybackNotEntitled,
            self::PlaybackNotAvailableInTerritory,
            self::PlaybackOutsideLicenceWindow,
            self::PlaybackBlackedOut,
            self::PlaybackPlatformNotPermitted,
            self::PlaybackParentalBlocked,
            self::PlaybackDeviceNotSupported,
            self::PlaybackModeNotPermitted,
            self::PlaybackTerritoryUndetermined,
            self::PlaybackSessionEnded,
            self::DeliveryTokenInvalid,
            self::DeliveryTokenExpired,
            self::DeliveryPathNotPermitted => 403,

            self::AccountEmailTaken,
            self::ProfileLimitReached,
            self::DeviceLimitReached,
            self::DeviceRemovalCooldown,
            self::ActivationAlreadyUsed,
            self::SubscriptionAlreadyActive,
            self::PlaybackConcurrencyExceeded,
            self::IdempotencyKeyConflict => 409,

            self::ProfileNotFound,
            self::DeviceNotFound,
            self::ChannelNotFound,
            self::ProgrammeNotFound,
            self::CategoryNotFound,
            self::PackageNotFound,
            self::PlanNotFound,
            self::SubscriptionNotFound,
            self::PlaybackSessionNotFound,
            self::MediaLadderNotFound,
            self::MediaPackagingProfileNotFound,
            self::MediaPublicationNotFound,
            self::MediaManifestNotFound,
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
            self::ProfilePrimaryImmutable,
            self::PlanRetired,
            self::SubscriptionNotChangeable,
            self::ScheduleRangeTooWide,
            self::MediaLadderInvalid,
            self::MediaEncoderNotPermitted,
            self::MediaSegmentAlignmentInvalid => 422,

            self::ActivationPending => 428,
            self::RateLimited, self::ActivationSlowDown => 429,
            self::PlaybackContentUnavailable,
            self::PlaybackTemporarilyUnavailable,
            self::PlaybackNoDeliveryTarget,
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
            self::ChannelNotFound => 'Channel not found',
            self::ProgrammeNotFound => 'Programme not found',
            self::CategoryNotFound => 'Category not found',
            self::ScheduleRangeTooWide => 'Requested schedule range is too wide',
            self::PackageNotFound => 'Package not found',
            self::PlanNotFound => 'Plan not found',
            self::PlanRetired => 'Plan is no longer available',
            self::SubscriptionNotFound => 'Subscription not found',
            self::SubscriptionAlreadyActive => 'A live subscription already exists',
            self::SubscriptionNotChangeable => 'Subscription cannot be changed in its current state',
            self::PlaybackNotEntitled => 'Not included in your package',
            self::PlaybackNotAvailableInTerritory => 'Not available in this territory',
            self::PlaybackOutsideLicenceWindow => 'Not available at this time',
            self::PlaybackBlackedOut => 'Temporarily blacked out',
            self::PlaybackPlatformNotPermitted => 'Not permitted on this kind of device',
            self::PlaybackConcurrencyExceeded => 'Too many simultaneous streams',
            self::PlaybackParentalBlocked => 'Blocked by parental settings',
            self::PlaybackDeviceNotSupported => 'Device cannot meet the required protection',
            self::PlaybackContentUnavailable => 'Content is not ready to play',
            self::PlaybackTerritoryUndetermined => 'Territory could not be determined',
            self::PlaybackModeNotPermitted => 'This playback mode is not licensed',
            self::PlaybackTemporarilyUnavailable => 'Playback temporarily unavailable',
            self::PlaybackSessionNotFound => 'Playback session not found',
            self::PlaybackNoDeliveryTarget => 'Nothing is published for this content yet',
            self::PlaybackSessionEnded => 'Playback session has ended',
            self::MediaLadderNotFound => 'Encoding ladder not found',
            self::MediaLadderInvalid => 'Encoding ladder is not producible',
            self::MediaPackagingProfileNotFound => 'Packaging profile not found',
            self::MediaPublicationNotFound => 'Publication not found',
            self::MediaManifestNotFound => 'Manifest has not been generated',
            self::MediaEncoderNotPermitted => 'Encoder has no recorded licence clearance',
            self::MediaSegmentAlignmentInvalid => 'Segment and GOP durations do not align',
            self::DeliveryTokenInvalid => 'Delivery token invalid',
            self::DeliveryTokenExpired => 'Delivery token expired',
            self::DeliveryPathNotPermitted => 'Delivery path not permitted',
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
