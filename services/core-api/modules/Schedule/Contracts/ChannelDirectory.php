<?php

declare(strict_types=1);

namespace Modules\Schedule\Contracts;

/** Schedule's inbound port. Playback asks it whether content exists and is playable. */
interface ChannelDirectory
{
    public function find(string $channelUuid): ?ChannelView;

    /** @param list<string> $channelUuids @return list<ChannelView> */
    public function findMany(array $channelUuids): array;
}
