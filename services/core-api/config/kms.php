<?php

declare(strict_types=1);

/*
 * KMS TV platform configuration.
 *
 * Every value that differs between environments comes from the environment.
 * Nothing here is a secret; secrets are read from the environment only and are
 * never given a default that would work in production.
 */

return [

    'api' => [
        'client_prefix' => 'api/client/v1',
        'admin_prefix' => 'api/admin/v1',
        // Base URI for RFC 9457 `type` values. Documentation, not a live endpoint.
        'problem_base_uri' => env('KMS_PROBLEM_BASE_URI', 'https://problems.kmstv.invalid'),
    ],

    'tokens' => [
        // Short-lived, asymmetrically signed, validated without a database read.
        'access_ttl_seconds' => (int) env('KMS_ACCESS_TOKEN_TTL', 900),
        // Long-lived, opaque, rotating, stored only as a hash.
        'refresh_ttl_seconds' => (int) env('KMS_REFRESH_TOKEN_TTL', 2_592_000),
        'issuer' => env('KMS_TOKEN_ISSUER', 'https://api.kmstv.invalid'),
        'audience' => env('KMS_TOKEN_AUDIENCE', 'kms-client'),
        /*
         * Ed25519 secret key, base64-encoded, from the environment only.
         * Generate with: php artisan kms:generate-token-key
         * There is deliberately no default: a missing key must fail loudly at
         * boot rather than silently sign with something predictable.
         */
        'signing_key' => env('KMS_TOKEN_SIGNING_KEY'),
        'signing_key_id' => env('KMS_TOKEN_SIGNING_KEY_ID', 'k1'),
        // Tolerated clock skew when validating. TV clocks drift.
        'leeway_seconds' => (int) env('KMS_TOKEN_LEEWAY', 60),
        // How long a revoked token id stays in the revocation set. Must be >= access_ttl.
        'revocation_ttl_seconds' => (int) env('KMS_TOKEN_REVOCATION_TTL', 900),
    ],

    'accounts' => [
        'password_min_length' => 12,
        'email_verification_ttl_seconds' => (int) env('KMS_EMAIL_VERIFICATION_TTL', 86_400),
        'password_reset_ttl_seconds' => (int) env('KMS_PASSWORD_RESET_TTL', 3_600),
        // Consecutive failures before an account is temporarily locked.
        'lockout_threshold' => (int) env('KMS_LOCKOUT_THRESHOLD', 10),
        'lockout_seconds' => (int) env('KMS_LOCKOUT_SECONDS', 900),
    ],

    'profiles' => [
        'max_per_account' => (int) env('KMS_MAX_PROFILES', 6),
        'pin_length' => 4,
    ],

    'devices' => [
        // Phase 3 replaces this with a plan-derived limit. Until plans exist,
        // one configured value applies to every account.
        'default_limit' => (int) env('KMS_DEVICE_LIMIT', 5),
        // Stops the device limit being trivially cycled.
        'removal_cooldown_hours' => (int) env('KMS_DEVICE_REMOVAL_COOLDOWN_HOURS', 24),
    ],

    'activation' => [
        // RFC 8628-style device authorization for televisions.
        'user_code_ttl_seconds' => (int) env('KMS_ACTIVATION_TTL', 600),
        'poll_interval_seconds' => (int) env('KMS_ACTIVATION_POLL_INTERVAL', 5),
        'user_code_length' => 8,
        // Unambiguous on a TV screen: no 0/O, 1/I/L, 5/S, 2/Z, U/V.
        'user_code_alphabet' => 'ACDEFGHJKMNPQRTWXY34679',
    ],

    'schedule' => [
        // A bounded window rather than a cursor: EPG queries are
        // channel × time range over a time-partitioned table, and an unbounded
        // range would let one request scan the whole partition set.
        'max_range_hours' => (int) env('KMS_EPG_MAX_RANGE_HOURS', 48),
        'default_range_hours' => 6,
    ],

    'playback' => [
        // Heartbeat frequency is a cost parameter, not an implementation
        // detail: it is the largest control-plane write stream at scale.
        'heartbeat_interval_seconds' => (int) env('KMS_HEARTBEAT_INTERVAL', 30),
        // A session with no heartbeat past this is closed and its slot
        // released, which is what stops a crashed client permanently consuming
        // a viewer's concurrency allowance.
        'session_ttl_seconds' => (int) env('KMS_SESSION_TTL', 120),
        'delivery_token_ttl_seconds' => (int) env('KMS_DELIVERY_TOKEN_TTL', 300),
        /*
         * Territory when no other signal is available. Null means undetermined,
         * which denies territory-restricted content rather than guessing.
         * A real IP geolocation adapter is P11 and awaits vendor selection.
         */
        'default_territory' => env('KMS_DEFAULT_TERRITORY'),
    ],

    'delivery' => [
        // Our own origin. A CDN is a cache in front of this (ADR-0008) and
        // arrives as a sibling adapter, not a rewrite.
        'origin_base_url' => env('KMS_ORIGIN_BASE_URL', 'https://origin.kmstv.invalid'),
    ],

    'rate_limits' => [
        'auth_per_ip' => env('KMS_RL_AUTH_IP', '20,1'),
        'auth_per_identity' => env('KMS_RL_AUTH_IDENTITY', '5,1'),
        'registration_per_ip' => env('KMS_RL_REGISTER_IP', '5,60'),
        'password_reset_per_ip' => env('KMS_RL_RESET_IP', '5,60'),
        'activation_poll' => env('KMS_RL_ACTIVATION_POLL', '30,1'),
        'read_per_account' => env('KMS_RL_READ', '120,1'),
        'write_per_account' => env('KMS_RL_WRITE', '30,1'),
        'admin' => env('KMS_RL_ADMIN', '60,1'),
        // Deliberately high but not unlimited: set from the measured legitimate
        // maximum, since a television retries hard on a flaky connection.
        'playback' => env('KMS_RL_PLAYBACK', '60,1'),
    ],

    'clients' => [
        'platforms' => ['web', 'android', 'androidtv', 'ios', 'tizen', 'webos'],
        // Per-platform minimum supported version, served by /config.
        // Empty means no minimum is enforced yet.
        'minimum_versions' => [],
    ],
];
