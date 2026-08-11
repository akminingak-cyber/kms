<?php

declare(strict_types=1);

namespace Modules\Schedule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Entitlement\Contracts\EntitlementQuery;
use Modules\Schedule\Infrastructure\Eloquent\Channel;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

/**
 * Channel listings.
 *
 * Split into two responses on purpose (docs/api/conventions.md §6):
 * the channel **metadata** varies only by locale and territory and is publicly
 * cacheable; the **availability overlay** varies per account and is private.
 * Merging them would either leak one viewer's entitlements to another through a
 * shared cache, or give up caching on the most frequently fetched payload in
 * the product.
 */
final class ChannelController
{
    public function __construct(private readonly EntitlementQuery $entitlements) {}

    /** Publicly cacheable: identical for every caller in a territory. */
    public function index(Request $request): JsonResponse
    {
        $channels = Channel::query()
            ->where('status', 'active')
            ->orderByRaw('number nulls last')
            ->orderBy('name')
            ->get();

        return (new JsonResponse([
            'data' => $channels->map(fn (Channel $c): array => $this->present($c))->all(),
        ]))->setPublic()->setMaxAge(300);
    }

    public function show(Request $request, string $channelId): JsonResponse
    {
        $channel = Channel::query()->where('uuid', $channelId)->where('status', 'active')->first();

        if ($channel === null) {
            throw ApiProblem::of(ErrorCode::ChannelNotFound);
        }

        return (new JsonResponse(['data' => $this->present($channel)]))->setPublic()->setMaxAge(300);
    }

    /**
     * The personalised overlay: which of these channels this account may watch.
     *
     * `private`, always. Content the account cannot watch is still listed with
     * `entitled: false` rather than hidden, because that is what makes upsell
     * and "included in a higher package" possible.
     */
    public function entitlements(Request $request): JsonResponse
    {
        $snapshot = $this->entitlements->snapshotFor((string) $request->attributes->get('kms.account_uuid'));
        $entitled = $snapshot?->channels ?? [];

        $channels = Channel::query()->where('status', 'active')->pluck('uuid');

        return (new JsonResponse([
            'data' => $channels->map(static fn (string $uuid): array => [
                'channel_id' => $uuid,
                'entitled' => in_array($uuid, $entitled, true),
            ])->all(),
            'snapshot_version' => $snapshot?->version,
        ]))->setPrivate()->setMaxAge(60);
    }

    /** @return array<string,mixed> */
    private function present(Channel $channel): array
    {
        return [
            'id' => $channel->uuid,
            'slug' => $channel->slug,
            'name' => $channel->name,
            'number' => $channel->number,
            'logo_url' => $channel->logo_url,
            'category_id' => $channel->category_uuid,
            'catchup_enabled' => (bool) $channel->catchup_enabled,
        ];
    }
}
