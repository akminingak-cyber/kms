<?php

declare(strict_types=1);

namespace Modules\Playback\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Playback\Infrastructure\Eloquent\Decision;
use Modules\Playback\Infrastructure\Eloquent\PlaybackSession;
use Modules\Shared\Domain\Clock;
use Modules\Shared\Http\CursorList;

/**
 * The support surface over playback.
 *
 * This is the highest-value screen in the admin panel, and it exists because
 * the decision log exists. "Why could this person not watch?" is the most
 * common support question an OTT platform receives, and without a record it is
 * answered by guesswork — or by an engineer reproducing the viewer's
 * circumstances, which they cannot do, because they are not in that territory
 * on that device with that subscription at that moment.
 *
 * Every denial was recorded with the inputs that produced it, so the answer is
 * a row rather than an investigation.
 *
 * **No personal data is exposed here.** Accounts, profiles and devices appear as
 * identifiers, never as names or email addresses. A support agent who needs to
 * find a viewer starts from the account they are already talking to; letting
 * them search the decision log by email would turn a diagnostic tool into a
 * viewing-history search engine over the whole subscriber base
 * (`docs/security/privacy-and-compliance.md`).
 */
final class AdminPlaybackController
{
    public function __construct(private readonly Clock $clock) {}

    public function decisions(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => ['sometimes', 'uuid'],
            'content_id' => ['sometimes', 'uuid'],
            'session_id' => ['sometimes', 'uuid'],
            'outcome' => ['sometimes', 'string', 'in:allow,deny'],
            'reason_code' => ['sometimes', 'string', 'max:60'],
        ]);

        /*
         * Ordered by identifier rather than by `decided_at`.
         *
         * The decision log is UUIDv7-keyed and time-partitioned, so the
         * identifier is already time-ordered — and ordering by it uses the
         * primary key instead of forcing a sort across partitions on the
         * platform's highest-volume table.
         */
        $query = Decision::query()->orderByDesc('id');

        foreach ([
            'account_id' => 'account_uuid',
            'content_id' => 'content_ref',
            'session_id' => 'session_uuid',
            'outcome' => 'outcome',
            'reason_code' => 'reason_code',
        ] as $parameter => $column) {
            if ($request->filled($parameter)) {
                $query->where($column, $request->string($parameter)->toString());
            }
        }

        return new JsonResponse(CursorList::respond($request, $query, static fn (Decision $decision): array => [
            'id' => $decision->uuid,
            'outcome' => $decision->outcome,
            // The whole point of the screen: the specific reason, not "denied".
            'reason_code' => $decision->reason_code,
            'account_id' => $decision->account_uuid,
            'profile_id' => $decision->profile_uuid,
            'device_id' => $decision->device_uuid,
            'device_class' => $decision->device_class,
            'session_id' => $decision->session_uuid,
            'content_id' => $decision->content_ref,
            'mode' => $decision->mode,
            // The inputs that produced the verdict. Without these the record
            // says what was decided but not why, which is the half a licensor
            // actually asks about.
            'availability_version' => $decision->availability_version === null
                ? null
                : (int) $decision->availability_version,
            'rights_rule_ids' => $decision->rights_rule_ids,
            'entitlement_grant_ids' => $decision->entitlement_grant_ids,
            'territory' => $decision->territory,
            'territory_method' => $decision->territory_method,
            'applied_restrictions' => $decision->applied_restrictions,
            'concurrency_source' => $decision->concurrency_source,
            'correlation_id' => $decision->correlation_id,
            'client' => $decision->client,
            'client_version' => $decision->client_version,
            'decided_at' => $decision->decided_at?->format(DATE_RFC3339),
        ]));
    }

    /**
     * Sessions, live by default.
     *
     * Answers the other half of a concurrency complaint: a viewer told they
     * have too many streams needs someone to be able to see what those streams
     * actually are.
     */
    public function sessions(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => ['sometimes', 'uuid'],
            'content_id' => ['sometimes', 'uuid'],
            'include_ended' => ['sometimes', 'boolean'],
        ]);

        $query = PlaybackSession::query()->orderByDesc('started_at')->orderByDesc('id');

        if ($request->filled('account_id')) {
            $query->where('account_uuid', $request->string('account_id')->toString());
        }

        if ($request->filled('content_id')) {
            $query->where('content_ref', $request->string('content_id')->toString());
        }

        if (! $request->boolean('include_ended')) {
            $query->whereNull('ended_at');
        }

        $ttl = (int) config('kms.playback.session_ttl_seconds');
        $now = $this->clock->now();

        return new JsonResponse(CursorList::respond($request, $query, static function (PlaybackSession $session) use ($now, $ttl): array {
            $lastBeat = $session->last_heartbeat_at->toDateTimeImmutable();

            return [
                'id' => $session->uuid,
                'account_id' => $session->account_uuid,
                'profile_id' => $session->profile_uuid,
                'device_id' => $session->device_uuid,
                'device_class' => $session->device_class,
                'content_id' => $session->content_ref,
                'mode' => $session->mode,
                'started_at' => $session->started_at->format(DATE_RFC3339),
                'last_heartbeat_at' => $lastBeat->format(DATE_RFC3339),
                'ended_at' => $session->ended_at?->format(DATE_RFC3339),
                'end_reason' => $session->end_reason,
                /*
                 * Surfaced rather than left for the reader to work out. A
                 * session still marked live whose heartbeat lapsed is what a
                 * leaked concurrency slot looks like, and it is invisible if
                 * the panel only shows a timestamp.
                 */
                'heartbeat_lapsed' => $session->ended_at === null
                    && $now->getTimestamp() - $lastBeat->getTimestamp() > $ttl,
            ];
        }));
    }
}
