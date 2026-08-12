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
        // Called by our own origin, never by a viewer. Restricted at the
        // network layer rather than authenticated with a bearer token.
        'internal_prefix' => 'internal/v1',
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
        /*
         * How long a delivery token stays valid, and therefore the upper bound
         * on how long a stopped, displaced or newly blacked-out session can
         * keep playing. The origin verifies tokens without a database read
         * (that is what makes it viable at segment rate), so this number *is*
         * the revocation latency. Shorter costs more renewals; longer widens
         * the window in which a revoked viewer still gets segments.
         */
        'delivery_token_ttl_seconds' => (int) env('KMS_DELIVERY_TOKEN_TTL', 300),
        /*
         * How often a live session is re-checked against rights and entitlement.
         *
         * The full decision is far too expensive to run at heartbeat rate — it
         * would multiply the platform's most costly check by the heartbeat
         * frequency — but never re-running it means a blackout starting
         * mid-event only stops *new* sessions. This is the compromise, and the
         * number is the maximum enforcement lag for a mid-session change.
         */
        'revalidation_interval_seconds' => (int) env('KMS_REVALIDATION_INTERVAL', 300),
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

        /*
         * Signs delivery tokens, which the origin verifies. A key of its own
         * rather than the application key: it is shared with the origin — and
         * eventually with a CDN — so it has a different blast radius and a
         * different rotation schedule from the key that signs everything else.
         *
         * No default. A missing key must fail loudly when a token is first
         * issued rather than silently sign with something predictable.
         */
        'token_key' => env('KMS_DELIVERY_TOKEN_KEY'),

        /*
         * Delivery edges, in priority order. The playback response carries an
         * ordered list from the first release even while this has one entry,
         * so clients implement fallback before multi-CDN needs it (ADR-0008).
         *
         * Only `origin` resolves to an adapter. A CDN vendor is not selected
         * (OQ-8) and no P3 verification checklist is complete, so naming one
         * here is a boot-time failure rather than a silently skipped edge.
         */
        'edges' => [
            [
                'provider' => 'origin',
                'base_url' => env('KMS_ORIGIN_BASE_URL', 'https://origin.kmstv.invalid'),
            ],
        ],

        /*
         * Which networks may reach the internal delivery-authorization
         * endpoint. Empty denies everything: a deployment that has not said
         * where its origin runs does not get an open verification oracle.
         */
        'origin_networks' => array_values(array_filter(
            explode(',', (string) env('KMS_ORIGIN_NETWORKS', '')),
        )),

        // What a device class is offered when the device-profile table has no
        // row for it. This narrows nothing; gating belongs in rights.
        'default_container' => env('KMS_DEFAULT_CONTAINER', 'cmaf'),
        'default_formats' => ['hls', 'dash'],
    ],

    'media' => [
        // Where generated manifests are written. A local volume in development,
        // object storage in production.
        'manifest_disk' => env('KMS_MANIFEST_DISK', 'manifests'),

        /*
         * Encoders this deployment is licensed to run — **empty by default,
         * and an empty list permits nothing**, so no ladder can be created
         * until a licence decision has actually been recorded.
         *
         * That is deliberate. FFmpeg is LGPL-2.1-or-later by default and
         * GPL-2.0-or-later once built with `--enable-gpl`; the encoders that
         * matter most for AVC and HEVC are themselves GPL-or-commercial, so
         * enabling one changes the licence of the binary. Codec *patent*
         * licensing is a separate obligation again, and neither question can
         * be answered from this repository — vendor and registry documentation
         * is unreachable from the initialisation environment, and a licence
         * recalled from memory is not a licence that was read (CLAUDE.md §1.4).
         *
         * Each entry records what was cleared and by what terms:
         *
         *   ['encoder' => 'libx264', 'codec' => 'avc',
         *    'licence' => '<as cleared>', 'requires_gpl_build' => true],
         */
        'permitted_encoders' => [],
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
