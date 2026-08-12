/**
 * GENERATED FILE — DO NOT EDIT.
 *
 * Source: packages/api-contracts/client/v1/openapi.yaml
 * Regenerate: pnpm contracts:generate
 *
 * The contract is the source of truth (ADR-0010). Editing this file makes the
 * client disagree with the server in a way no test can see, because both sides
 * would still compile.
 */

export interface paths {
    "/config": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * Client configuration, fetched at application start-up
         * @description How a client learns its minimum supported version and the limits in
         *     force. Must stay reachable when the rest of the API is degraded: it is
         *     the main lever the platform retains over clients it cannot otherwise
         *     change.
         */
        get: operations["getClientConfig"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/register": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /** Create an account and its primary profile */
        post: operations["register"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/verify-email": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["verifyEmail"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/login": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * Sign in with a password and bind the session to a device
         * @description Television device classes are rejected here: a TV has no usable keyboard
         *     and must use the activation flow, and accepting a password login from
         *     one would create a second, weaker path to the same session.
         */
        post: operations["login"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/refresh": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * Rotate a refresh token
         * @description Refresh tokens rotate on every use and are tracked as a family.
         *     Presenting an already-rotated token means either theft or a client bug;
         *     both revoke the whole family and return `AUTH_REFRESH_REUSE_DETECTED`.
         *     A client receiving that code must clear its credentials rather than retry.
         */
        post: operations["refreshSession"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/logout": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /** Revoke the session */
        post: operations["logout"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/password/forgot": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * @description Always returns 202, whether or not the address is registered.
         *     Distinguishing the two would make this unauthenticated endpoint an
         *     account-enumeration oracle.
         */
        post: operations["requestPasswordReset"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/password/reset": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /** @description Resetting a password revokes every existing session for the account. */
        post: operations["resetPassword"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/device/authorize": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * Begin television pairing
         * @description Returns two secrets. `user_code` is short and is displayed on the
         *     television for a person to type elsewhere; `device_code` is
         *     high-entropy and is what the television polls with. Polling with the
         *     short code is rejected — it is low-entropy by necessity.
         */
        post: operations["startDeviceActivation"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/device/token": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * @description Polled by the television until approved. Returns `428` with
         *     `ACTIVATION_PENDING` while waiting, so a client can tell "keep waiting"
         *     apart from "give up". Honour the `interval` from the authorize response.
         */
        post: operations["pollDeviceActivation"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/auth/device/approve": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /** Approve a television from an authenticated session */
        post: operations["approveDeviceActivation"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/profiles": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["listProfiles"];
        put?: never;
        post: operations["createProfile"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/profiles/{profileId}": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                profileId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get: operations["getProfile"];
        put?: never;
        post?: never;
        /** @description The primary profile cannot be deleted — an account must always have a viewing subject. */
        delete: operations["deleteProfile"];
        options?: never;
        head?: never;
        patch: operations["updateProfile"];
        trace?: never;
    };
    "/devices": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["listDevices"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/devices/{deviceId}": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                deviceId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        /**
         * @description Removes the device and revokes its sessions in the same operation. A
         *     removal cooldown applies, so the device limit cannot be trivially cycled.
         */
        delete: operations["removeDevice"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/categories": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /** The editorial vocabulary */
        get: operations["listCategories"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/channels": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * Channel metadata
         * @description Publicly cacheable: this varies by locale and territory only. Whether a
         *     given viewer may watch a channel is a **separate**, private response at
         *     `/entitlements/channels` — merging the two would either leak one
         *     viewer's entitlements to another through a shared cache, or give up
         *     caching on the most frequently fetched payload in the product.
         */
        get: operations["listChannels"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/channels/{channelId}": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                channelId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get: operations["getChannel"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/channels/{channelId}/now-next": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                channelId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        /** What is on now and next */
        get: operations["getNowNext"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/epg": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * Schedule over a bounded time window
         * @description Time-range rather than cursor pagination, deliberately: a schedule query
         *     is `channel × window` over a time-partitioned table, not an open-ended
         *     scroll. The range is bounded — an unbounded one would let a single
         *     request scan every partition — and exceeding it returns
         *     `SCHEDULE_RANGE_TOO_WIDE`.
         *
         *     A programme already in progress when the window opens **is** returned:
         *     overlap, not containment, because that is the one the viewer is watching.
         */
        get: operations["getSchedule"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/entitlements/channels": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * Which channels this account may watch
         * @description The personalised overlay, always `private`. A channel the account cannot
         *     watch is listed with `entitled: false` rather than hidden, because that
         *     is what makes upsell possible. Content that must not be disclosed at all
         *     is absent from `/channels` instead.
         */
        get: operations["getChannelEntitlements"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/packages": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["listPackages"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/plans": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["listPlans"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/subscription": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /** The account's live subscription, if any */
        get: operations["getSubscription"];
        put?: never;
        /**
         * Start a subscription
         * @description **No payment is taken.** No payment provider has been selected, and none
         *     is integrated in this phase — the subscription and the entitlement it
         *     produces are real, the money movement is not yet built.
         */
        post: operations["subscribe"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/subscription/{subscriptionId}/cancel": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                subscriptionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * @description Cancels at the end of the paid period. Cancellation is a decision, not
         *     an immediate end: the subscriber keeps access until the period they
         *     already paid for runs out.
         */
        post: operations["cancelSubscription"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/subscription/{subscriptionId}/resume": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                subscriptionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["resumeSubscription"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/playback/sessions": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * The viewer's own live sessions
         * @description "Too many simultaneous streams" is unactionable without being able to
         *     see which devices are using them, so this is what turns a concurrency
         *     denial into something a viewer can resolve rather than only be blocked
         *     by.
         *
         *     Scoped to the authenticated account. Sessions that have ended are not
         *     listed.
         */
        get: operations["listPlaybackSessions"];
        put?: never;
        /**
         * Authorize playback and start a session
         * @description The critical path.
         *
         *         CAN PLAY = Rights ∧ Entitlement ∧ Parental ∧ Device/Concurrency ∧ Territory
         *
         *     Checks run cheapest-first and the **first failure wins**, each with its
         *     own reason code. Rights is evaluated before entitlement deliberately: if
         *     content is not licensed for a territory that is true regardless of what
         *     the customer bought, and telling them to upgrade would be wrong.
         *
         *     Account, profile owner, device and device class all come from the
         *     device-bound token. `capabilities` is client-asserted and may only ever
         *     **narrow** what is offered — a client claiming a capability that would
         *     raise its entitlement is ignored.
         *
         *     `protection` is `null` until DRM ships in a later phase. It is absent
         *     rather than stubbed.
         */
        post: operations["startPlayback"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/playback/sessions/{sessionId}/heartbeat": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                sessionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * @description Renews the concurrency slot and reissues the delivery targets, and
         *     periodically re-runs the decision.
         *
         *     **The response carries fresh delivery targets, and clients must use
         *     them.** A heartbeat that returned only an expiry would leave a client
         *     watching a stream whose token it cannot renew.
         *
         *     A session with no heartbeat past its grace period is closed and its slot
         *     released, which is what stops a crashed client permanently consuming a
         *     viewer's allowance.
         *
         *     The decision is re-run at most once per configured interval rather than
         *     on every beat — the full check is the most expensive in the platform.
         *     A `403` here means playback must stop **now**: the reason code is the
         *     specific one, so a blackout that began mid-event is reported as a
         *     blackout and not as a generic failure.
         */
        post: operations["heartbeatPlayback"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/playback/sessions/{sessionId}/stop": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                sessionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["stopPlayback"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
}
export type webhooks = Record<string, never>;
export interface components {
    schemas: {
        Category: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            parent_id?: components["schemas"]["Identifier"] | null;
            sort_order?: number;
        };
        Channel: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            number?: number | null;
            logo_url?: string | null;
            category_id?: components["schemas"]["Identifier"] | null;
            /**
             * @description Whether the channel may be recorded at all. Catch-up is a separately
             *     licensed exploitation, not a feature toggle.
             */
            catchup_enabled?: boolean;
        };
        Programme: {
            id?: components["schemas"]["Identifier"];
            channel_id?: components["schemas"]["Identifier"];
            title?: string;
            subtitle?: string | null;
            description?: string | null;
            category_id?: components["schemas"]["Identifier"] | null;
            age_rating?: string | null;
            season_number?: number | null;
            episode_number?: number | null;
            /** Format: date-time */
            starts_at?: string;
            /** Format: date-time */
            ends_at?: string;
        };
        /** @description Minor units plus an explicit currency. Never a bare number. */
        Money: {
            amount_minor: number;
            currency: string;
            territory?: string | null;
        };
        Package: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            description?: string | null;
            channel_ids?: components["schemas"]["Identifier"][];
        };
        Plan: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            package_id?: components["schemas"]["Identifier"] | null;
            /** @enum {string} */
            billing_period?: "monthly" | "annual";
            trial_days?: number;
            device_limit?: number;
            /**
             * @description The plan's commercial allowance. A licensor's contractual cap is
             *     separate and the lower of the two wins at playback.
             */
            concurrency_limit?: number;
            max_resolution?: string | null;
            prices?: components["schemas"]["Money"][];
        };
        Subscription: {
            id?: components["schemas"]["Identifier"];
            plan_id?: components["schemas"]["Identifier"];
            /** @enum {string} */
            status?: "trialing" | "active" | "past_due" | "cancelled" | "expired";
            /** Format: date-time */
            started_at?: string | null;
            /** Format: date-time */
            current_period_end?: string | null;
            /** Format: date-time */
            trial_ends_at?: string | null;
            cancel_at_period_end?: boolean;
        };
        DeliveryTarget: {
            /** @enum {string} */
            format?: "hls" | "dash";
            /**
             * @description Independent of `format`. A device may need HLS with MPEG-TS rather
             *     than HLS with CMAF fMP4, which is a different problem from needing a
             *     different encryption scheme (ADR-0005).
             * @enum {string}
             */
            container?: "cmaf" | "ts";
            /**
             * Format: uri
             * @description Everything that varies per viewer — the session, the expiry and the
             *     signature — is packed into a **single opaque query parameter**, so
             *     the path is byte-identical for every viewer of the same content at
             *     the same quality class. An edge therefore has exactly one parameter
             *     to exclude from its cache key; excluding none, or missing one of
             *     several, gives every viewer a private copy of every segment and
             *     collapses cache offload to zero.
             *
             *     Clients must treat the URL as opaque and must not reconstruct it.
             */
            url?: string;
            priority?: number;
            /**
             * @description Which delivery edge this target addresses. Reported in player
             *     telemetry so a quality problem can be attributed to an edge without
             *     parsing hostnames out of URLs.
             */
            edge?: string;
            /**
             * @description The manifest variant this target points at. A licensor's
             *     `max_resolution` rule and a plan's cap are enforced by serving a
             *     manifest that does not mention the rungs the viewer may not have —
             *     never by asking the client to filter, because the client is never a
             *     security boundary.
             *
             *     Surfaced so that a viewer who reports "it looks soft" is answered
             *     from the response rather than from an investigation.
             * @enum {string}
             */
            quality_class?: "h576" | "h720" | "h1080" | "h2160" | "full";
            /**
             * Format: date-time
             * @description When this target's token stops working. Renew via the heartbeat
             *     before this instant; a client that does not simply stops receiving
             *     segments.
             */
            expires_at?: string;
        };
        PlaybackSession: {
            session_id?: components["schemas"]["Identifier"];
            decision_id?: components["schemas"]["Identifier"];
            delivery?: {
                /**
                 * @description An ordered list from day one, even when it holds a single entry.
                 *     Clients that cannot handle a list would still be in the field
                 *     years later, blocking multi-CDN adoption (ADR-0008).
                 */
                targets?: components["schemas"]["DeliveryTarget"][];
                /** Format: date-time */
                expires_at?: string;
            };
            /** @description DRM arrives in a later phase. Absent rather than stubbed. */
            protection?: null;
            /** @description Rights-derived, never hardcoded. */
            restrictions?: {
                [key: string]: unknown;
            };
            heartbeat_interval_seconds?: number;
        };
        /**
         * Format: uuid
         * @description UUIDv7. Sequential integers are never exposed.
         * @example 0199c1a4-7e3b-7c21-9f4d-2b6a8e05d913
         */
        Identifier: string;
        Problem: {
            /** Format: uri */
            type: string;
            /** @description Non-localised summary. Clients localise from the code, never from this. */
            title: string;
            status: number;
            /**
             * @description Stable machine-readable code. **Clients switch on this and never on
             *     prose.** A code's meaning never changes once shipped; the registry is
             *     append-only.
             */
            code: string;
            detail?: string;
            instance?: string;
            correlation_id: string;
            meta?: {
                [key: string]: unknown;
            };
        };
        ClientConfig: {
            api?: {
                client_version?: string;
                features?: string[];
            };
            minimum_supported_version?: string | null;
            upgrade_required?: boolean;
            /** @description A message key, never a message. The client localises. */
            upgrade_message_key?: string | null;
            activation?: {
                poll_interval_seconds?: number;
            };
            limits?: {
                profiles?: number;
                devices?: number;
            };
        };
        RegisterRequest: {
            /** Format: email */
            email: string;
            /** @description Length over composition rules. */
            password: string;
            profile_name: string;
            locale?: string;
            accepts_terms: boolean;
        };
        RegisterResponse: {
            account?: {
                id?: components["schemas"]["Identifier"];
                email?: string;
                email_verified?: boolean;
            };
            primary_profile_id?: components["schemas"]["Identifier"];
        };
        LoginRequest: {
            /** Format: email */
            email: string;
            password: string;
            device_class: components["schemas"]["PasswordDeviceClass"];
            device_name: string;
            /** @description Stable per-installation value. Lets a known device sign in again without consuming another device slot. */
            device_fingerprint?: string | null;
        };
        Session: {
            access_token: string;
            refresh_token: string;
            /** @constant */
            token_type: "Bearer";
            expires_in: number;
            /** Format: date-time */
            refresh_expires_at?: string;
            device_id?: components["schemas"]["Identifier"];
        };
        ActivationStart: {
            /** @description Displayed on the television. */
            user_code?: string;
            /** @description The polling secret. As sensitive as a refresh token. */
            device_code?: string;
            /** Format: uri */
            verification_uri?: string;
            /** Format: date-time */
            expires_at?: string;
            /** @description Minimum seconds between polls. */
            interval?: number;
        };
        Profile: {
            id?: components["schemas"]["Identifier"];
            name?: string;
            is_primary?: boolean;
            locale?: string;
            max_rating?: string | null;
            /** @description Whether a PIN is set. The PIN itself is never returned. */
            has_pin?: boolean;
            audio_language?: string | null;
            subtitle_language?: string | null;
        };
        ProfileWrite: {
            name?: string;
            locale?: string;
            max_rating?: string | null;
            pin?: string | null;
            audio_language?: string | null;
            subtitle_language?: string | null;
        };
        Device: {
            id?: components["schemas"]["Identifier"];
            name?: string;
            device_class?: components["schemas"]["DeviceClass"];
            /** Format: date-time */
            first_seen_at?: string;
            /** Format: date-time */
            last_seen_at?: string;
            is_current?: boolean;
        };
        /**
         * @description Authoritative platform data, not a display label: from Phase 4 it is an
         *     input to the rights and DRM decision. Treat as extensible — a client
         *     must skip a value it does not recognise rather than fail.
         * @enum {string}
         */
        DeviceClass: "web" | "mobile" | "tablet" | "androidtv" | "tizen" | "webos" | "stb";
        /** @enum {string} */
        PasswordDeviceClass: "web" | "mobile" | "tablet";
        /** @enum {string} */
        PairingDeviceClass: "androidtv" | "tizen" | "webos" | "stb";
    };
    responses: {
        /** @description RFC 9457 problem document */
        Problem: {
            headers: {
                [name: string]: unknown;
            };
            content: {
                "application/problem+json": components["schemas"]["Problem"];
            };
        };
    };
    parameters: never;
    requestBodies: never;
    headers: never;
    pathItems: never;
}
export type $defs = Record<string, never>;
export interface operations {
    getClientConfig: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Configuration */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": components["schemas"]["ClientConfig"];
                };
            };
        };
    };
    register: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["RegisterRequest"];
            };
        };
        responses: {
            /** @description Account created; a verification email has been sent */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": components["schemas"]["RegisterResponse"];
                };
            };
            409: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    verifyEmail: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    token: string;
                };
            };
        };
        responses: {
            /** @description Verified */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        account?: {
                            id?: components["schemas"]["Identifier"];
                            email_verified?: boolean;
                        };
                    };
                };
            };
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    login: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["LoginRequest"];
            };
        };
        responses: {
            /** @description Session issued */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": components["schemas"]["Session"];
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            409: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            423: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    refreshSession: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    refresh_token: string;
                };
            };
        };
        responses: {
            /** @description New token pair */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": components["schemas"]["Session"];
                };
            };
            401: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    logout: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: {
            content: {
                "application/json": {
                    refresh_token?: string;
                };
            };
        };
        responses: {
            /** @description Signed out */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            401: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    requestPasswordReset: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    /** Format: email */
                    email: string;
                };
            };
        };
        responses: {
            /** @description Accepted */
            202: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            429: components["responses"]["Problem"];
        };
    };
    resetPassword: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    token: string;
                    password: string;
                };
            };
        };
        responses: {
            /** @description Password changed; all sessions revoked */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    startDeviceActivation: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    device_class: components["schemas"]["PairingDeviceClass"];
                    device_name: string;
                };
            };
        };
        responses: {
            /** @description Activation started */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": components["schemas"]["ActivationStart"];
                };
            };
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    pollDeviceActivation: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    device_code: string;
                };
            };
        };
        responses: {
            /** @description Session issued */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": components["schemas"]["Session"];
                };
            };
            409: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            428: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    approveDeviceActivation: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    user_code: string;
                };
            };
        };
        responses: {
            /** @description Approved; the response names the device being authorised */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        device?: components["schemas"]["Device"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            409: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    listProfiles: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Profiles for the authenticated account */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Profile"][];
                        limit?: number;
                    };
                };
            };
            401: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    createProfile: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["ProfileWrite"];
            };
        };
        responses: {
            /** @description Created */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Profile"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            409: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    getProfile: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                profileId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Profile */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Profile"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    deleteProfile: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                profileId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Deleted */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    updateProfile: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                profileId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["ProfileWrite"];
            };
        };
        responses: {
            /** @description Updated */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Profile"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    listDevices: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Registered devices */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Device"][];
                        limit?: number;
                    };
                };
            };
            401: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    removeDevice: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                deviceId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Removed */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            409: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    listCategories: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Categories */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Category"][];
                    };
                };
            };
            429: components["responses"]["Problem"];
        };
    };
    listChannels: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Channels */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Channel"][];
                    };
                };
            };
            429: components["responses"]["Problem"];
        };
    };
    getChannel: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                channelId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Channel */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Channel"];
                    };
                };
            };
            404: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    getNowNext: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                channelId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Now and next */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        now?: components["schemas"]["Programme"] | null;
                        next?: components["schemas"]["Programme"] | null;
                    };
                };
            };
            404: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    getSchedule: {
        parameters: {
            query?: {
                channel_ids?: components["schemas"]["Identifier"][];
                from?: string;
                to?: string;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Programmes in the window */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Programme"][];
                        window?: {
                            /** Format: date-time */
                            from?: string;
                            /** Format: date-time */
                            to?: string;
                        };
                    };
                };
            };
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    getChannelEntitlements: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Entitlement overlay */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: {
                            channel_id?: components["schemas"]["Identifier"];
                            entitled?: boolean;
                        }[];
                        snapshot_version?: number | null;
                    };
                };
            };
            401: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    listPackages: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Packages */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Package"][];
                    };
                };
            };
            429: components["responses"]["Problem"];
        };
    };
    listPlans: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Plans and their prices */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Plan"][];
                    };
                };
            };
            429: components["responses"]["Problem"];
        };
    };
    getSubscription: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Subscription or null */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Subscription"] | null;
                    };
                };
            };
            401: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    subscribe: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    plan_id: components["schemas"]["Identifier"];
                };
            };
        };
        responses: {
            /** @description Subscribed */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Subscription"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            409: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    cancelSubscription: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                subscriptionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Cancellation scheduled */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Subscription"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    resumeSubscription: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                subscriptionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Cancellation revoked */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Subscription"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    listPlaybackSessions: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Live sessions */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: {
                            session_id?: components["schemas"]["Identifier"];
                            content_id?: components["schemas"]["Identifier"];
                            /** @enum {string} */
                            mode?: "live" | "restart" | "catchup";
                            device_id?: components["schemas"]["Identifier"];
                            device_class?: string;
                            /** Format: date-time */
                            started_at?: string;
                            /** Format: date-time */
                            last_heartbeat_at?: string;
                        }[];
                    };
                };
            };
            401: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    startPlayback: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    content_id: components["schemas"]["Identifier"];
                    profile_id: components["schemas"]["Identifier"];
                    /** @enum {string} */
                    mode: "live" | "restart" | "catchup";
                    capabilities?: {
                        formats?: ("hls" | "dash")[];
                    };
                };
            };
        };
        responses: {
            /** @description Authorized */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": components["schemas"]["PlaybackSession"];
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            409: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
            503: components["responses"]["Problem"];
        };
    };
    heartbeatPlayback: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                sessionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Renewed */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        session_id?: components["schemas"]["Identifier"];
                        heartbeat_interval_seconds?: number;
                        delivery?: {
                            targets?: components["schemas"]["DeliveryTarget"][];
                            /** Format: date-time */
                            expires_at?: string;
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            /**
             * @description The session must end. Carries the specific reason — for example
             *     `PLAYBACK_BLACKED_OUT`, `PLAYBACK_NOT_ENTITLED` or
             *     `PLAYBACK_SESSION_ENDED`.
             */
            403: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/problem+json": components["schemas"]["Problem"];
                };
            };
            404: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
            503: components["responses"]["Problem"];
        };
    };
    stopPlayback: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                sessionId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Session ended and slot released */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            401: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
}
