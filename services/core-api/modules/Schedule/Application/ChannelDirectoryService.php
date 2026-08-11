<?php

declare(strict_types=1);

namespace Modules\Schedule\Application;

use Modules\Schedule\Contracts\ChannelDirectory;
use Modules\Schedule\Contracts\ChannelView;
use Modules\Schedule\Infrastructure\Eloquent\Channel;

final class ChannelDirectoryService implements ChannelDirectory
{
    public function find(string $channelUuid): ?ChannelView
    {
        $channel = Channel::query()->where('uuid', $channelUuid)->first();

        return $channel === null ? null : $this->view($channel);
    }

    /** @param list<string> $channelUuids @return list<ChannelView> */
    public function findMany(array $channelUuids): array
    {
        if ($channelUuids === []) {
            return [];
        }

        return Channel::query()
            ->whereIn('uuid', $channelUuids)
            ->get()
            ->map(fn (Channel $c): ChannelView => $this->view($c))
            ->all();
    }

    private function view(Channel $channel): ChannelView
    {
        return new ChannelView(
            uuid: $channel->uuid,
            slug: $channel->slug,
            name: $channel->name,
            isPlayable: $channel->status === 'active',
            catchupEnabled: (bool) $channel->catchup_enabled,
        );
    }
}
