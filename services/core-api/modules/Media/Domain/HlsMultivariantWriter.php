<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

use Modules\Media\Contracts\DeliveryPolicy;
use Modules\Media\Contracts\OriginAddressing;

/**
 * The HLS multivariant playlist (RFC 8216 §4.3.4).
 *
 * **What this writes and what it does not.** The multivariant playlist is
 * derived entirely from the ladder, the packaging profile and the delivery
 * policy — all control-plane data — so it is written here, where the policy
 * lives and where it can be tested. The per-rendition *media* playlists are
 * not: their contents change with every segment the packager emits, and only
 * the packager knows what it has actually published. This writer references
 * them at their agreed origin paths.
 *
 * That split is the reason the resolution cap can be enforced at all. A
 * multivariant playlist that never mentions the 1080p rung is the only place a
 * `max_resolution` usage rule becomes real for a player.
 *
 * Manifests are a **client contract** and behave like one: a change that looks
 * harmless breaks a television that will never be updated. Hence the golden-file
 * tests, and hence byte-for-byte determinism here — no map ordering, no
 * locale-dependent number formatting.
 */
final readonly class HlsMultivariantWriter
{
    private const AUDIO_GROUP = 'audio';

    public function write(Ladder $ladder, PackagingProfile $profile, DeliveryPolicy $policy, OriginAddressing $origin): string
    {
        $lines = [
            '#EXTM3U',
            '#EXT-X-VERSION:'.$profile->hlsVersion(),
            // Every segment can be decoded without the one before it, which is
            // what makes rendition switching at a segment boundary work at all.
            '#EXT-X-INDEPENDENT-SEGMENTS',
            '',
        ];

        foreach ($ladder->audio as $rendition) {
            $lines[] = $this->audioLine($rendition, $profile, $origin);
        }

        $lines[] = '';

        $defaultAudio = $ladder->defaultAudio();

        foreach ($ladder->rungsUpTo($policy->maxHeight) as $rung) {
            $lines[] = $this->streamInfLine($rung, $defaultAudio);
            $lines[] = $origin->mediaPlaylistRelative($rung->label);
        }

        return implode("\n", $lines)."\n";
    }

    private function audioLine(AudioRendition $rendition, PackagingProfile $profile, OriginAddressing $origin): string
    {
        $attributes = [
            'TYPE=AUDIO',
            'GROUP-ID="'.self::AUDIO_GROUP.'"',
            'NAME="'.$this->escape($this->displayName($rendition)).'"',
            'LANGUAGE="'.$rendition->language.'"',
            'DEFAULT='.($rendition->isDefault ? 'YES' : 'NO'),
            // A non-default track is still offered when the player's language
            // preference matches it; only description tracks stay opt-in.
            'AUTOSELECT='.($rendition->role === 'description' ? 'NO' : 'YES'),
        ];

        if ($rendition->role === 'description') {
            $attributes[] = 'CHARACTERISTICS="public.accessibility.describes-video"';
        }

        // CHANNELS was added in HLS 7; emitting it below that makes a strict
        // v4 parser reject the whole playlist.
        if ($profile->hlsVersion() >= 7) {
            $attributes[] = 'CHANNELS="'.$rendition->channels.'"';
        }

        $attributes[] = 'URI="'.$origin->mediaPlaylistRelative($rendition->label).'"';

        return '#EXT-X-MEDIA:'.implode(',', $attributes);
    }

    private function streamInfLine(Rung $rung, AudioRendition $audio): string
    {
        $attributes = [
            // Peak, not average: a player that plans against the average
            // underestimates and rebuffers on the hardest scenes.
            'BANDWIDTH='.$rung->peakBitsPerSecond($audio->bitrateKbps),
            'AVERAGE-BANDWIDTH='.$rung->averageBitsPerSecond($audio->bitrateKbps),
            'CODECS="'.$rung->codecString().','.$audio->codecString().'"',
            'RESOLUTION='.$rung->width.'x'.$rung->height,
            'FRAME-RATE='.$rung->frameRate->toDecimalString(),
            'AUDIO="'.self::AUDIO_GROUP.'"',
        ];

        return '#EXT-X-STREAM-INF:'.implode(',', $attributes);
    }

    private function displayName(AudioRendition $rendition): string
    {
        $suffix = match ($rendition->role) {
            'description' => ' (Audio Description)',
            'commentary' => ' (Commentary)',
            default => '',
        };

        return strtoupper($rendition->language).$suffix;
    }

    /** Quoted-string attribute values may not contain a quote or a line break. */
    private function escape(string $value): string
    {
        return str_replace(['"', "\n", "\r"], '', $value);
    }
}
