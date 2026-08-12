/**
 * GENERATED FILE — DO NOT EDIT.
 *
 * Source: packages/api-contracts/admin/v1/openapi.yaml
 * Regenerate: pnpm contracts:generate
 *
 * The contract is the source of truth (ADR-0010). Editing this file makes the
 * client disagree with the server in a way no test can see, because both sides
 * would still compile.
 */

export interface paths {
    "/auth/sign-in": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * Sign in with password and TOTP
         * @description **The TOTP code is mandatory and unconditional.** There is no branch of
         *     this operation that issues a staff token without one — no "remembered
         *     device", no grace period, no first-login exemption. Staff credentials
         *     reach rights, pricing and the personal data of every subscriber, and a
         *     conditional second factor is a second factor that will eventually not be
         *     applied.
         *
         *     Failures are deliberately indistinguishable: a wrong password, a wrong
         *     code and an unknown account all return `AUTH_INVALID_CREDENTIALS`.
         */
        post: operations["staffSignIn"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/me": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * The signed-in staff member
         * @description Roles: every authenticated staff member. This is the one operation with
         *     no role restriction beyond holding a token, because the panel cannot
         *     render its navigation without knowing the caller's role, and the
         *     response discloses nothing the caller does not already hold a token for.
         *
         *     Hiding a link the caller may not use is a **usability** measure only.
         *     Every route enforces its own roles, and hiding a link has never stopped
         *     anyone typing a URL.
         */
        get: operations["getCurrentStaff"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/staff": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * Who has access
         * @description Roles: `auditor`, `platform_engineer`. Support agents are excluded —
         *     knowing who else has access is not part of answering a viewer's
         *     question.
         *
         *     Password hashes and TOTP secrets are never selected, so they cannot be
         *     returned by accident.
         */
        get: operations["listStaff"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/audit": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * The audit trail
         * @description Roles: `auditor`, `platform_engineer`, `support_agent`.
         *
         *     Append-only and cursor-paginated. For an audit trail, offset pagination
         *     does not merely reorder results — it lets an investigator page past an
         *     entry without ever seeing it.
         */
        get: operations["listAuditEntries"];
        put?: never;
        post?: never;
        delete?: never;
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
        /** @description Roles: `content_operator`, `platform_engineer`, `support_agent`, `rights_manager`, `auditor`. */
        get: operations["listAdminCategories"];
        put?: never;
        /** @description Roles: `content_operator`, `platform_engineer`. */
        post: operations["createCategory"];
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
         * Every channel, including those a viewer cannot see
         * @description Roles: `content_operator`, `platform_engineer`, `support_agent`,
         *     `rights_manager`, `commercial_operator`, `auditor`.
         *
         *     Deliberately not the client endpoint with a staff token on it. The
         *     client list is filtered to what is playable, and an operator asking
         *     "why is this channel missing from the app?" needs to see precisely the
         *     rows that filter removes.
         */
        get: operations["listAdminChannels"];
        put?: never;
        /**
         * @description Roles: `content_operator`, `platform_engineer`.
         *
         *     `catchup_enabled` defaults to **false**: a channel may not be recorded
         *     until somebody says the recording right exists.
         */
        post: operations["createChannel"];
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
        /**
         * @description Roles: as for the channel list.
         *
         *     Includes a sample of recently ingested programmes, because "the EPG
         *     looks wrong" is answered by looking at rows rather than at a count of
         *     them.
         */
        get: operations["getAdminChannel"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/schedule/ingest": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * Ingest a schedule feed
         * @description Roles: `content_operator`, `platform_engineer`.
         *
         *     Reconciled by `(source, source_ref)` and revision-aware rather than
         *     last-write-wins: providers correct history, and a schedule model that
         *     overwrites silently makes catch-up recordings point at the wrong
         *     content.
         *
         *     A malformed row is rejected individually with a reason. One bad entry
         *     must not discard a whole feed.
         */
        post: operations["ingestSchedule"];
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
        /** @description Roles: `commercial_operator`, `platform_engineer`, `support_agent`, `auditor`. */
        get: operations["listAdminPackages"];
        put?: never;
        /** @description Roles: `commercial_operator`, `platform_engineer`. */
        post: operations["createPackage"];
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
        /**
         * @description Roles: `commercial_operator`, `platform_engineer`, `support_agent`,
         *     `auditor`.
         *
         *     Carries the price that applies **now**, not every price ever set.
         *     Historical prices matter to finance and to a dispute; they are not what
         *     an operator is looking at when they ask what a plan costs.
         */
        get: operations["listAdminPlans"];
        put?: never;
        /** @description Roles: `commercial_operator`, `platform_engineer`. */
        post: operations["createPlan"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/rights/agreements": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /** @description Roles: `rights_manager`, `platform_engineer`. */
        get: operations["listAgreements"];
        put?: never;
        /** @description Roles: `rights_manager`, `platform_engineer`. Audited. */
        post: operations["createAgreement"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/rights/rights": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * Rights, with their bitmasks decoded
         * @description Roles: `rights_manager`, `platform_engineer`.
         *
         *     Superseded rights are included by default and marked as such. Rights are
         *     superseded rather than edited precisely so that "why was this denied on
         *     the 3rd?" stays answerable — filtering history out by default would
         *     defeat that.
         */
        get: operations["listRights"];
        put?: never;
        /**
         * @description Roles: `rights_manager`, `platform_engineer`. Audited.
         *
         *     Absence is a prohibition: a right that names no territories permits
         *     none, and an unrecognised platform contributes nothing rather than
         *     everything.
         */
        post: operations["createRight"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/rights/blackouts": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /** @description Roles: `rights_manager`, `platform_engineer`. */
        get: operations["listBlackouts"];
        put?: never;
        /**
         * @description Roles: `rights_manager`, `platform_engineer`. Audited.
         *
         *     Blackouts are never projected — they are read live at decision time, so
         *     one takes effect immediately for new sessions and within the
         *     revalidation interval for sessions already playing.
         */
        post: operations["createBlackout"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/rights/availability/preview": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * Resolve availability without recording a decision
         * @description Roles: `rights_manager`, `platform_engineer`.
         *
         *     Preview before commit, because a rights mistake is a contract breach.
         *     This runs the same resolver playback uses and returns the same verdict
         *     and reason — a separate "simulation" path that could drift from the real
         *     one would be worse than having none.
         */
        post: operations["previewAvailability"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/ladders": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /** @description Roles: `platform_engineer`. */
        get: operations["listLadders"];
        put?: never;
        /**
         * Define an encoding ladder
         * @description Roles: `platform_engineer`. A ladder change alters encoding cost,
         *     storage cost and picture quality simultaneously; it is not the same
         *     authority as editing a programme description.
         *
         *     **Refused unless the encoder has a recorded licence clearance.** The
         *     permitted-encoder list is empty by default and an empty list permits
         *     nothing, so a fresh deployment cannot create a ladder at all until
         *     somebody records a decision. FFmpeg's licence follows its build flags
         *     and the usual AVC/HEVC encoders are GPL-or-commercial.
         *
         *     Ladder invariants are enforced here rather than discovered from a
         *     player: bitrates must increase, aspect ratios must match, and frame
         *     rates must be integer divisors of the ladder's highest. Each describes a
         *     defect that appears only at rendition switches — invisible on a fast
         *     connection, broken for viewers on variable networks.
         */
        post: operations["createLadder"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/ladders/{ladderId}/ffmpeg-command": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                ladderId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * The exact encode arguments for a ladder
         * @description Roles: `platform_engineer`.
         *
         *     Answers "what will actually run for this channel?" from the same code
         *     path the pipeline uses, so the answer cannot drift from the behaviour.
         *
         *     `output_stage` is **null**, and that is not an omission. Packager
         *     selection is an open decision with a licence gate on it, so these
         *     arguments are the encode stage only and are not runnable without a
         *     muxer. The response says so rather than leaving an operator to discover
         *     it.
         */
        post: operations["getFfmpegCommand"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/packaging-profiles": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /** @description Roles: `platform_engineer`. */
        get: operations["listPackagingProfiles"];
        put?: never;
        /**
         * @description Roles: `platform_engineer`.
         *
         *     The segment duration must be a whole number of GOPs, or segment
         *     boundaries would not land on IDR frames in every rendition. That is a
         *     correctness requirement rather than a tuning knob, so it is refused here
         *     rather than tolerated.
         */
        post: operations["createPackagingProfile"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/publications": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * @description Roles: `platform_engineer`.
         *
         *     Carries the content hash of every manifest actually written, so "did
         *     that republish change anything?" is answered from a hash rather than by
         *     diffing two manifests by eye.
         */
        get: operations["listPublications"];
        put?: never;
        /** @description Roles: `platform_engineer`. Binds a channel to a ladder and a packaging profile. */
        post: operations["createPublication"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/publications/{publicationId}/publish": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                publicationId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * Generate manifests and publish
         * @description Roles: `platform_engineer`.
         *
         *     Generates one manifest per quality class per format (ADR-0012), so a
         *     licensor's resolution cap is enforced by serving a manifest that does not
         *     mention the rungs a viewer may not have — while remaining cacheable
         *     across every viewer sharing that cap.
         *
         *     Refuses with `MEDIA_SEGMENT_ALIGNMENT_INVALID` if the GOP is not a whole
         *     number of frames at the ladder's frame rate. That defect is invisible
         *     until players start switching rendition.
         */
        post: operations["publishPublication"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/publications/{publicationId}/retire": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                publicationId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        get?: never;
        put?: never;
        /**
         * @description Roles: `platform_engineer`.
         *
         *     Removes the manifests; the media itself is kept. The origin is the
         *     system of record for media and a retired channel is frequently
         *     un-retired, so deleting segments on a status change would make that
         *     unrecoverable.
         */
        post: operations["retirePublication"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/publications/{publicationId}/manifests/{policy}/{format}": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                publicationId: components["schemas"]["Identifier"];
                policy: "h576" | "h720" | "h1080" | "h2160" | "full";
                format: "hls" | "dash";
            };
            cookie?: never;
        };
        /**
         * The exact bytes a player will receive
         * @description Roles: `platform_engineer`.
         *
         *     Returned verbatim rather than as a rendering of it. A manifest is a
         *     client contract and is reviewed as one, and a resolution cap is expressed
         *     by **absence** — which can only be checked by reading what is actually
         *     there.
         */
        get: operations["getManifest"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/media/device-profiles/{deviceClass}": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                deviceClass: string;
            };
            cookie?: never;
        };
        get?: never;
        /**
         * @description Roles: `platform_engineer`.
         *
         *     Data rather than code, because it is corrected as device-lab results
         *     arrive. This table **narrows** what a device class is offered; it does
         *     not gate access. Gating is a rights decision and belongs where it can be
         *     audited against an agreement.
         */
        put: operations["upsertDeviceProfile"];
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/playback/decisions": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        /**
         * Why a viewer was allowed or refused
         * @description Roles: `support_agent`, `platform_engineer`, `auditor`, `rights_manager`.
         *
         *     The most common support question an OTT platform receives is "why could
         *     this person not watch?", and without a record it is answered by
         *     guesswork — an engineer cannot reproduce a viewer's territory, device,
         *     subscription and moment. Every decision was recorded with the inputs
         *     that produced it, so the answer is a row.
         *
         *     **No personal data.** Accounts, profiles and devices appear as
         *     identifiers only. Searching this log by email address would turn a
         *     diagnostic tool into a viewing-history search engine over the whole
         *     subscriber base.
         *
         *     Read-only by construction: nothing on this surface can change a
         *     decision or grant access.
         */
        get: operations["listDecisions"];
        put?: never;
        post?: never;
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
         * Playback sessions, live by default
         * @description Roles: `support_agent`, `platform_engineer`, `auditor`, `rights_manager`.
         *
         *     Answers the other half of a concurrency complaint: a viewer told they
         *     have too many streams needs someone to be able to see what those streams
         *     actually are.
         */
        get: operations["listAdminSessions"];
        put?: never;
        post?: never;
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
        /**
         * Format: uuid
         * @description UUIDv7. Sequential integers are never exposed.
         */
        Identifier: string;
        Page: {
            /**
             * @description Null means this is the last page. No total is returned: counting a
             *     growing table on every page load is a cost with no reader.
             */
            next_cursor?: string | null;
        };
        /** @enum {string} */
        StaffRole: "platform_engineer" | "content_operator" | "commercial_operator" | "rights_manager" | "support_agent" | "auditor";
        StaffMember: {
            id?: components["schemas"]["Identifier"];
            name?: string;
            /** Format: email */
            email?: string;
            role?: components["schemas"]["StaffRole"];
            /** @enum {string} */
            status?: "active" | "suspended";
            /** @description Staff MFA is mandatory, so a false here is an anomaly worth seeing. */
            mfa_enrolled?: boolean;
            /** Format: date-time */
            last_login_at?: string | null;
        };
        AuditEntry: {
            id?: components["schemas"]["Identifier"];
            /** @enum {string} */
            actor_type?: "staff" | "account" | "system";
            /** Format: uuid */
            actor_id?: string | null;
            action?: string;
            subject_type?: string | null;
            subject_id?: string | null;
            /**
             * @description The facts that changed. Never a secret, a token, a password or a
             *     hash — the audit trail is read by support staff, so anything in it
             *     is effectively disclosed to them.
             */
            context?: {
                [key: string]: unknown;
            };
            /** Format: date-time */
            occurred_at?: string;
        };
        Category: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            parent_id?: number | null;
            sort_order?: number;
        };
        AdminChannel: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            number?: number | null;
            status?: string;
            /** Format: uuid */
            category_id?: string | null;
            /** @description False until somebody says the recording right exists. */
            catchup_enabled?: boolean;
            /** Format: date-time */
            created_at?: string | null;
        };
        ProgrammeSummary: {
            id?: components["schemas"]["Identifier"];
            title?: string;
            /** Format: date-time */
            starts_at?: string | null;
            /** Format: date-time */
            ends_at?: string | null;
            /** @description Providers correct history; the schedule model is revision-aware rather than last-write-wins. */
            revision?: number;
            source?: string | null;
        };
        AdminPackage: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            status?: string;
            channel_ids?: components["schemas"]["Identifier"][];
        };
        /** @description Money is always an amount **and** a currency. An integer alone is not money. */
        MoneyInput: {
            /** @description Minor units. Never a float. */
            amount_minor: number;
            currency: string;
        };
        AdminPlan: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            status?: string;
            billing_period?: string;
            trial_days?: number;
            device_limit?: number;
            concurrency_limit?: number;
            /**
             * @description Null means the plan places no cap of its own. A licensor's cap still
             *     applies on top of it at playback, and the stricter of the two wins.
             */
            max_resolution?: string | null;
            package?: {
                id?: components["schemas"]["Identifier"];
                name?: string;
            } | null;
            price?: {
                amount_minor?: number;
                currency?: string;
                territory?: string | null;
            } | null;
        };
        Agreement: {
            id?: components["schemas"]["Identifier"];
            counterparty?: string;
            reference?: string;
            /** Format: date-time */
            term_start?: string | null;
            /** Format: date-time */
            term_end?: string | null;
            reporting_obligation?: string | null;
        };
        /**
         * @description Ordered, and later rules win, so a correction appended to an agreement
         *     takes effect without rewriting what came before. Default deny: a subject
         *     with no include rule is permitted nowhere.
         */
        TerritoryRule: {
            /** @enum {string} */
            effect: "include" | "exclude";
            territories: string[];
        };
        UsageRules: {
            max_resolution?: string | null;
            hdcp?: string | null;
            security_level?: string | null;
            /**
             * @description Contractual rather than commercial. Unlike a plan limit it may never
             *     fail open — exceeding it is a breach, exactly like ignoring a
             *     territory.
             */
            concurrency_cap?: number | null;
        };
        Right: {
            id?: components["schemas"]["Identifier"];
            agreement?: {
                id?: components["schemas"]["Identifier"];
                counterparty?: string;
                reference?: string;
            } | null;
            subject_type?: string;
            subject_id?: components["schemas"]["Identifier"];
            exploitation?: string;
            /** Format: date-time */
            window_start?: string | null;
            /**
             * Format: date-time
             * @description Null is an open-ended window, not a missing value.
             */
            window_end?: string | null;
            /** @description Decoded from the stored bitmask. A bit with no name is dropped rather than rendered as a number. */
            platforms?: string[];
            monetization?: string[];
            territory_rules?: components["schemas"]["TerritoryRule"][] | null;
            usage_rules?: components["schemas"]["UsageRules"] | null;
            /**
             * Format: date-time
             * @description Rights are superseded rather than edited, so a past decision stays explainable.
             */
            superseded_at?: string | null;
        };
        Blackout: {
            id?: components["schemas"]["Identifier"];
            subject_type?: string;
            subject_id?: components["schemas"]["Identifier"];
            /** Format: date-time */
            starts_at?: string | null;
            /** Format: date-time */
            ends_at?: string | null;
            reason?: string | null;
            /** Format: date-time */
            lifted_at?: string | null;
        };
        LadderInput: {
            slug: string;
            name: string;
            /** @enum {string} */
            content_class: "news" | "sport" | "film" | "series" | "generic";
            rungs: {
                label: string;
                width: number;
                height: number;
                video_bitrate_kbps: number;
                max_bitrate_kbps: number;
                /** @enum {string} */
                codec: "avc" | "hevc" | "av1" | "vp9";
                /** @description Must appear in the deployment's recorded licence clearances. */
                encoder: string;
                profile: string;
                /** @description Level × 10, so 4.0 is 40. */
                level: number;
                /**
                 * @description An integer or an exact rational such as `30000/1001`. Broadcast
                 *     rates are not decimals, and a GOP computed from 29.97 drifts
                 *     against one computed from 30000/1001.
                 */
                frame_rate: string;
                /**
                 * @description Required for codecs whose RFC 6381 string this platform does
                 *     not derive. HEVC, AV1 and VP9 encode profile, tier, level and
                 *     constraint bytes whose values depend on the encoder's output;
                 *     guessing them would be inventing a capability.
                 */
                codec_string?: string | null;
            }[];
            audio: {
                label: string;
                /** @description BCP 47. */
                language: string;
                /** @enum {string} */
                role: "main" | "description" | "commentary" | "dub";
                codec: string;
                bitrate_kbps: number;
                channels: number;
                /** @enum {integer} */
                sample_rate_hz: 44100 | 48000;
                /**
                 * @description Which audio stream of the contribution feed carries this
                 *     rendition. Declared rather than inferred: a wrong guess
                 *     silently produces a channel whose "English" track is the
                 *     Spanish commentary.
                 */
                source_stream_index: number;
                /**
                 * @description Exactly one rendition in the ladder may be default — an HLS
                 *     rendition group permits one, and a playlist with two is
                 *     malformed.
                 */
                is_default: boolean;
                codec_string?: string | null;
            }[];
        };
        Ladder: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            content_class?: string;
            status?: string;
            rungs?: {
                label?: string;
                width?: number;
                height?: number;
                video_bitrate_kbps?: number;
                max_bitrate_kbps?: number;
                codec?: string;
                encoder?: string;
                frame_rate?: string;
            }[];
            audio?: {
                label?: string;
                language?: string;
                role?: string;
                bitrate_kbps?: number;
                channels?: number;
                is_default?: boolean;
            }[];
        };
        PackagingProfileInput: {
            slug: string;
            name: string;
            /** @enum {string} */
            container: "cmaf" | "ts";
            /** @description Must be a whole number of GOPs, or segment boundaries miss IDR frames. */
            segment_duration_ms: number;
            gop_duration_ms: number;
            timescale: number;
            playlist_window_segments: number;
            /** @description Must cover at least the advertised window, or players request segments already removed. */
            time_shift_buffer_seconds: number;
            suggested_presentation_delay_ms: number;
        };
        PackagingProfile: {
            id?: components["schemas"]["Identifier"];
            slug?: string;
            name?: string;
            container?: string;
            segment_duration_ms?: number;
            gop_duration_ms?: number;
            /** @description Null means unencrypted — absent rather than a plausible placeholder. DRM is a later phase. */
            encryption_scheme?: string | null;
        };
        ManifestRecord: {
            /** @enum {string} */
            policy?: "h576" | "h720" | "h1080" | "h2160" | "full";
            /** @enum {string} */
            format?: "hls" | "dash";
            path?: string;
            content_hash?: string;
            byte_size?: number;
            /** Format: date-time */
            generated_at?: string | null;
        };
        Publication: {
            id?: components["schemas"]["Identifier"];
            subject_type?: string;
            subject_id?: components["schemas"]["Identifier"];
            /** @description Vendor-neutral and derived from identifiers, so a CDN can be pointed at the same origin without re-deriving URLs. */
            origin_prefix?: string;
            status?: string;
            revision?: number;
            /** Format: date-time */
            available_from?: string | null;
            /** Format: date-time */
            published_at?: string | null;
            ladder?: {
                id?: components["schemas"]["Identifier"];
                slug?: string;
            } | null;
            packaging_profile?: {
                id?: components["schemas"]["Identifier"];
                slug?: string;
                container?: string;
            } | null;
            manifests?: components["schemas"]["ManifestRecord"][];
        };
        DeviceProfile: {
            device_class?: string;
            container?: string;
            formats?: string[];
            max_height?: number | null;
        };
        Decision: {
            id?: components["schemas"]["Identifier"];
            /** @enum {string} */
            outcome?: "allow" | "deny";
            /**
             * @description The specific reason, never a generic "not available". A generic
             *     denial makes support impossible and hides bugs.
             */
            reason_code?: string | null;
            /** Format: uuid */
            account_id?: string | null;
            /** Format: uuid */
            profile_id?: string | null;
            /** Format: uuid */
            device_id?: string | null;
            device_class?: string | null;
            /** Format: uuid */
            session_id?: string | null;
            /** Format: uuid */
            content_id?: string | null;
            mode?: string | null;
            availability_version?: number | null;
            rights_rule_ids?: string[] | null;
            entitlement_grant_ids?: string[] | null;
            territory?: string | null;
            /**
             * @description How the territory was concluded. A licensor asking how we decided a
             *     viewer was in a given country must be answered from a record.
             */
            territory_method?: string | null;
            applied_restrictions?: {
                [key: string]: unknown;
            } | null;
            /** @enum {string|null} */
            concurrency_source?: "plan" | "licensor" | null;
            correlation_id?: string | null;
            client?: string | null;
            client_version?: string | null;
            /** Format: date-time */
            decided_at?: string | null;
        };
        AdminSession: {
            id?: components["schemas"]["Identifier"];
            account_id?: components["schemas"]["Identifier"];
            profile_id?: components["schemas"]["Identifier"];
            device_id?: components["schemas"]["Identifier"];
            device_class?: string;
            content_id?: components["schemas"]["Identifier"];
            mode?: string;
            /** Format: date-time */
            started_at?: string;
            /** Format: date-time */
            last_heartbeat_at?: string;
            /** Format: date-time */
            ended_at?: string | null;
            end_reason?: string | null;
            /**
             * @description A session still marked live whose heartbeat lapsed is what a leaked
             *     concurrency slot looks like — invisible if only a timestamp is shown.
             */
            heartbeat_lapsed?: boolean;
        };
        Problem: {
            /** Format: uri */
            type: string;
            title: string;
            status: number;
            /** @description Stable machine-readable code. Clients switch on this, never on prose. */
            code: string;
            detail?: string | null;
            instance?: string | null;
            correlation_id: string;
            errors?: {
                [key: string]: unknown;
            } | null;
        };
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
    parameters: {
        /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
        Limit: number;
        /** @description Opaque cursor from a previous response's `page.next_cursor`. */
        Cursor: string;
    };
    requestBodies: never;
    headers: never;
    pathItems: never;
}
export type $defs = Record<string, never>;
export interface operations {
    staffSignIn: {
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
                    password: string;
                    totp_code: string;
                };
            };
        };
        responses: {
            /** @description Signed in */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        access_token?: string;
                        /** @enum {string} */
                        token_type?: "Bearer";
                        expires_in?: number;
                        staff?: {
                            id?: components["schemas"]["Identifier"];
                            name?: string;
                            role?: components["schemas"]["StaffRole"];
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
            429: components["responses"]["Problem"];
        };
    };
    getCurrentStaff: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description The caller */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["StaffMember"];
                    };
                };
            };
            401: components["responses"]["Problem"];
        };
    };
    listStaff: {
        parameters: {
            query?: {
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Staff members */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["StaffMember"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    listAuditEntries: {
        parameters: {
            query?: {
                action?: string;
                actor_id?: components["schemas"]["Identifier"];
                subject_id?: components["schemas"]["Identifier"];
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Audit entries */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["AuditEntry"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    listAdminCategories: {
        parameters: {
            query?: {
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
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
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createCategory: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    slug: string;
                    name: string;
                    parent_id?: number | null;
                    sort_order?: number;
                };
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
                        data?: components["schemas"]["Category"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listAdminChannels: {
        parameters: {
            query?: {
                status?: string;
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
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
                        data?: components["schemas"]["AdminChannel"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createChannel: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    slug: string;
                    name: string;
                    number?: number | null;
                    category_id?: components["schemas"]["Identifier"] | null;
                    /** @default false */
                    catchup_enabled?: boolean;
                };
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
                        data?: {
                            id?: components["schemas"]["Identifier"];
                            slug?: string;
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    getAdminChannel: {
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
                        data?: components["schemas"]["AdminChannel"] & {
                            recent_programmes?: components["schemas"]["ProgrammeSummary"][];
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
        };
    };
    ingestSchedule: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    source: string;
                    programmes: Record<string, never>[];
                };
            };
        };
        responses: {
            /** @description Ingest result, including per-row rejections */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: {
                            accepted?: number;
                            rejected?: number;
                            rejections?: {
                                source_ref?: string | null;
                                reason?: string;
                            }[];
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listAdminPackages: {
        parameters: {
            query?: {
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
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
                        data?: components["schemas"]["AdminPackage"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createPackage: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    slug: string;
                    name: string;
                    description?: string | null;
                    channel_ids?: components["schemas"]["Identifier"][];
                };
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
                        data?: components["schemas"]["AdminPackage"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listAdminPlans: {
        parameters: {
            query?: {
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Plans */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["AdminPlan"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createPlan: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    slug: string;
                    name: string;
                    package_id: components["schemas"]["Identifier"];
                    /** @enum {string} */
                    billing_period: "monthly" | "annual";
                    trial_days?: number;
                    device_limit?: number;
                    concurrency_limit?: number;
                    max_resolution?: string | null;
                    price?: components["schemas"]["MoneyInput"];
                };
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
                        data?: components["schemas"]["AdminPlan"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listAgreements: {
        parameters: {
            query?: {
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Agreements */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Agreement"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createAgreement: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    counterparty: string;
                    reference: string;
                    /** Format: date-time */
                    term_start?: string | null;
                    /** Format: date-time */
                    term_end?: string | null;
                    reporting_obligation?: string | null;
                };
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
                        data?: components["schemas"]["Agreement"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listRights: {
        parameters: {
            query?: {
                subject_id?: components["schemas"]["Identifier"];
                agreement_id?: components["schemas"]["Identifier"];
                exploitation?: string;
                active_only?: boolean;
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Rights */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Right"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createRight: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    agreement_id: components["schemas"]["Identifier"];
                    /** @enum {string} */
                    subject_type: "channel";
                    subject_id: components["schemas"]["Identifier"];
                    /** @enum {string} */
                    exploitation: "live" | "restart" | "catchup" | "vod";
                    /** Format: date-time */
                    window_start: string;
                    /** Format: date-time */
                    window_end?: string | null;
                    platforms?: string[];
                    monetization?: string[];
                    territory_rules?: components["schemas"]["TerritoryRule"][];
                    usage?: components["schemas"]["UsageRules"];
                };
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
                        data?: components["schemas"]["Right"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listBlackouts: {
        parameters: {
            query?: {
                subject_id?: components["schemas"]["Identifier"];
                active_only?: boolean;
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Blackouts */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Blackout"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createBlackout: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    /** @enum {string} */
                    subject_type: "channel";
                    subject_id: components["schemas"]["Identifier"];
                    /** Format: date-time */
                    starts_at: string;
                    /** Format: date-time */
                    ends_at: string;
                    reason: string;
                    territory_rules?: components["schemas"]["TerritoryRule"][];
                };
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
                        data?: components["schemas"]["Blackout"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    previewAvailability: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    /** @enum {string} */
                    subject_type: "channel";
                    subject_id: components["schemas"]["Identifier"];
                    /** @enum {string} */
                    exploitation: "live" | "restart" | "catchup" | "vod";
                    territory: string;
                    platform: string;
                    monetization?: string;
                    /** Format: date-time */
                    at?: string | null;
                };
            };
        };
        responses: {
            /** @description Verdict */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: {
                            permitted?: boolean;
                            reason?: string | null;
                            availability_version?: number | null;
                            source_rule_ids?: components["schemas"]["Identifier"][];
                            usage_rules?: components["schemas"]["UsageRules"] | null;
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listLadders: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Encoding ladders */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Ladder"][];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createLadder: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["LadderInput"];
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
                        data?: components["schemas"]["Ladder"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            /**
             * @description Includes `MEDIA_ENCODER_NOT_PERMITTED` (no recorded licence
             *     clearance) and `MEDIA_LADDER_INVALID` (the ladder could not be
             *     encoded as described).
             */
            422: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/problem+json": components["schemas"]["Problem"];
                };
            };
        };
    };
    getFfmpegCommand: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                ladderId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    packaging_profile_id: components["schemas"]["Identifier"];
                    input_url: string;
                };
            };
        };
        responses: {
            /** @description Encode arguments */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: {
                            arguments?: string[];
                            output_stage?: null;
                            output_stage_note?: string;
                            requires_gpl_build?: boolean;
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listPackagingProfiles: {
        parameters: {
            query?: {
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Packaging profiles */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["PackagingProfile"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createPackagingProfile: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["PackagingProfileInput"];
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
                        data?: components["schemas"]["PackagingProfile"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listPublications: {
        parameters: {
            query?: {
                subject_id?: components["schemas"]["Identifier"];
                status?: string;
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Publications */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Publication"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    createPublication: {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    /** @enum {string} */
                    subject_type: "channel";
                    subject_id: components["schemas"]["Identifier"];
                    ladder_id: components["schemas"]["Identifier"];
                    packaging_profile_id: components["schemas"]["Identifier"];
                };
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
                        data?: components["schemas"]["Publication"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    publishPublication: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                publicationId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Published */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Publication"] & {
                            manifests?: components["schemas"]["ManifestRecord"][];
                        };
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    retirePublication: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                publicationId: components["schemas"]["Identifier"];
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Retired */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
        };
    };
    getManifest: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                publicationId: components["schemas"]["Identifier"];
                policy: "h576" | "h720" | "h1080" | "h2160" | "full";
                format: "hls" | "dash";
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description The manifest */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/vnd.apple.mpegurl": string;
                    "application/dash+xml": string;
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            404: components["responses"]["Problem"];
        };
    };
    upsertDeviceProfile: {
        parameters: {
            query?: never;
            header?: never;
            path: {
                deviceClass: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": {
                    /** @enum {string} */
                    container: "cmaf" | "ts";
                    /** @description Ordered by preference — an empirical result from the device lab. */
                    formats: ("hls" | "dash")[];
                    max_height?: number | null;
                    note?: string | null;
                };
            };
        };
        responses: {
            /** @description Stored */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["DeviceProfile"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
            422: components["responses"]["Problem"];
        };
    };
    listDecisions: {
        parameters: {
            query?: {
                account_id?: components["schemas"]["Identifier"];
                content_id?: components["schemas"]["Identifier"];
                session_id?: components["schemas"]["Identifier"];
                outcome?: "allow" | "deny";
                reason_code?: string;
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Decisions */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["Decision"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
    listAdminSessions: {
        parameters: {
            query?: {
                account_id?: components["schemas"]["Identifier"];
                content_id?: components["schemas"]["Identifier"];
                include_ended?: boolean;
                /** @description Page size, 1–100. Bounded so a client cannot ask for a page costing more than the request is worth. */
                limit?: components["parameters"]["Limit"];
                /** @description Opaque cursor from a previous response's `page.next_cursor`. */
                cursor?: components["parameters"]["Cursor"];
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Sessions */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data?: components["schemas"]["AdminSession"][];
                        page?: components["schemas"]["Page"];
                    };
                };
            };
            401: components["responses"]["Problem"];
            403: components["responses"]["Problem"];
        };
    };
}
