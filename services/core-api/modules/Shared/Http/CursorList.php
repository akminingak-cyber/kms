<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * One list shape for the whole admin read surface.
 *
 * Cursor pagination everywhere, including on sets that look small today.
 * Channels, packages and rights all grow with the business, and the failure
 * mode of offset pagination is not slowness — it is that rows shift between
 * pages as data is inserted, so an operator paging through a list silently
 * misses entries. Discovering that on the audit trail or the decision log is
 * how an investigation reaches the wrong conclusion.
 *
 * The uniformity is also for the client: one response shape means the panel
 * implements paging once rather than per screen, and no screen can quietly
 * be the one that loads everything.
 */
final class CursorList
{
    /** Bounded so a client cannot ask for a page that costs more than the request is worth. */
    private const MAX_LIMIT = 100;

    private const DEFAULT_LIMIT = 50;

    /**
     * @param  Builder<Model>  $query  already ordered — ordering is the caller's decision
     * @param  callable(Model): array<string,mixed>  $present
     * @return array<string,mixed>
     */
    public static function respond(Request $request, Builder $query, callable $present): array
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
            'cursor' => ['sometimes', 'string', 'max:255'],
        ]);

        $page = $query->cursorPaginate(
            (int) ($validated['limit'] ?? self::DEFAULT_LIMIT),
            ['*'],
            'cursor',
            $validated['cursor'] ?? null,
        );

        return [
            'data' => array_map($present, $page->items()),
            'page' => [
                // Null means "this is the end". No total: counting a growing
                // table on every page load is a cost with no reader.
                'next_cursor' => $page->nextCursor()?->encode(),
            ],
        ];
    }
}
