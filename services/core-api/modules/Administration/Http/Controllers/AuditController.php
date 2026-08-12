<?php

declare(strict_types=1);

namespace Modules\Administration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Administration\Application\StaffAuthService;
use Modules\Administration\Infrastructure\Eloquent\AuditEntry;
use Modules\Administration\Infrastructure\Eloquent\StaffUser;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\CursorList;
use Modules\Shared\Http\ErrorCode;

final class AuditController
{
    public function __construct(private readonly StaffAuthService $staffAuth) {}

    public function signIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:320'],
            'password' => ['required', 'string', 'max:4096'],
            // Mandatory, unconditionally. There is no branch that issues a
            // staff token without it.
            'totp_code' => ['required', 'string', 'digits:6'],
        ]);

        $result = $this->staffAuth->authenticate(
            $validated['email'],
            $validated['password'],
            $validated['totp_code'],
        );

        return new JsonResponse([
            'access_token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_in' => $result['expires_in'],
            'staff' => [
                'id' => $result['staff']->uuid,
                'name' => $result['staff']->name,
                'role' => $result['staff']->role,
            ],
        ]);
    }

    /**
     * The signed-in staff member and what they may do.
     *
     * The panel needs this before it can render anything: navigation is built
     * from the role, so a content operator is not offered a rights screen they
     * will be refused at.
     *
     * That is a **usability** measure and nothing more. Every route names the
     * roles that may reach it, and hiding a link has never stopped anyone
     * typing a URL — the client is not a security boundary here any more than
     * it is anywhere else (`CLAUDE.md` §7).
     */
    public function me(Request $request): JsonResponse
    {
        $staff = StaffUser::query()
            ->where('uuid', (string) $request->attributes->get('kms.staff_uuid'))
            ->first();

        if ($staff === null) {
            // The token verified but the account is gone — revoked mid-session.
            throw ApiProblem::of(ErrorCode::AuthRequired);
        }

        return new JsonResponse(['data' => [
            'id' => $staff->uuid,
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->role,
            'status' => $staff->status,
            'last_login_at' => $staff->last_login_at?->format(DATE_RFC3339),
        ]]);
    }

    /**
     * Who has access.
     *
     * An access review is impossible without a list, and "who can change our
     * rights data?" is a question an auditor will ask. Secrets are absent by
     * construction: the password hash and the TOTP secret are never selected.
     */
    public function staff(Request $request): JsonResponse
    {
        $query = StaffUser::query()
            ->select(['id', 'uuid', 'email', 'name', 'role', 'status', 'mfa_enrolled_at', 'last_login_at'])
            ->orderBy('role')
            ->orderBy('id');

        return new JsonResponse(CursorList::respond($request, $query, static fn (StaffUser $staff): array => [
            'id' => $staff->uuid,
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->role,
            'status' => $staff->status,
            // Staff MFA is mandatory, so an unenrolled row is an anomaly worth
            // seeing rather than a field worth hiding.
            'mfa_enrolled' => $staff->mfa_enrolled_at !== null,
            'last_login_at' => $staff->last_login_at?->format(DATE_RFC3339),
        ]));
    }

    /**
     * Cursor pagination, because the audit log only grows.
     *
     * Offset pagination on a table with continuous inserts shifts rows between
     * pages, which for an audit trail means an investigator can miss an entry
     * entirely while paging through it.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['sometimes', 'string', 'max:80'],
            'actor_id' => ['sometimes', 'uuid'],
            'subject_id' => ['sometimes', 'uuid'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string', 'max:255'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        $query = AuditEntry::query()->orderByDesc('occurred_at')->orderByDesc('id');

        foreach (['action', 'actor_id', 'subject_id'] as $filter) {
            if (isset($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        $page = $query->cursorPaginate($limit, ['*'], 'cursor', $validated['cursor'] ?? null);

        return new JsonResponse([
            'data' => array_map(static fn (AuditEntry $e): array => [
                'id' => $e->uuid,
                'actor_type' => $e->actor_type,
                'actor_id' => $e->actor_id,
                'action' => $e->action,
                'subject_type' => $e->subject_type,
                'subject_id' => $e->subject_id,
                'correlation_id' => $e->correlation_id,
                'context' => $e->context,
                'occurred_at' => $e->occurred_at->format(DATE_RFC3339),
            ], $page->items()),
            'page' => [
                'next_cursor' => $page->nextCursor()?->encode(),
                'has_more' => $page->hasMorePages(),
            ],
        ]);
    }
}
