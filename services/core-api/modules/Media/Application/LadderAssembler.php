<?php

declare(strict_types=1);

namespace Modules\Media\Application;

use Modules\Media\Domain\AudioRendition;
use Modules\Media\Domain\FrameRate;
use Modules\Media\Domain\Ladder;
use Modules\Media\Domain\PackagingProfile;
use Modules\Media\Domain\Rung;
use Modules\Media\Infrastructure\Eloquent\EncodingLadder;
use Modules\Media\Infrastructure\Eloquent\PackagingProfileRow;

/**
 * Rows to domain objects.
 *
 * The invariants live in the domain constructors, so this is the point at which
 * stored data is re-validated rather than trusted. That matters: a ladder that
 * was valid when it was written can be made invalid by a later migration or by
 * a direct database edit, and discovering that while generating a manifest is
 * very much better than discovering it from a player.
 */
final readonly class LadderAssembler
{
    public function ladder(EncodingLadder $row): Ladder
    {
        $rungs = [];

        foreach ($row->rungs as $rung) {
            $rungs[] = new Rung(
                label: (string) $rung->label,
                width: (int) $rung->width,
                height: (int) $rung->height,
                videoBitrateKbps: (int) $rung->video_bitrate_kbps,
                maxBitrateKbps: (int) $rung->max_bitrate_kbps,
                codec: (string) $rung->codec,
                encoder: (string) $rung->encoder,
                profile: (string) $rung->profile,
                level: (int) $rung->level,
                frameRate: new FrameRate((int) $rung->frame_rate_numerator, (int) $rung->frame_rate_denominator),
                codecString: $rung->codec_string === null ? null : (string) $rung->codec_string,
            );
        }

        $audio = [];

        foreach ($row->audio as $rendition) {
            $audio[] = new AudioRendition(
                label: (string) $rendition->label,
                language: (string) $rendition->language,
                role: (string) $rendition->role,
                codec: (string) $rendition->codec,
                bitrateKbps: (int) $rendition->bitrate_kbps,
                channels: (int) $rendition->channels,
                sampleRateHz: (int) $rendition->sample_rate_hz,
                sourceStreamIndex: (int) $rendition->source_stream_index,
                isDefault: (bool) $rendition->is_default,
                codecString: $rendition->codec_string === null ? null : (string) $rendition->codec_string,
            );
        }

        return new Ladder(
            slug: (string) $row->slug,
            contentClass: (string) $row->content_class,
            rungs: $rungs,
            audio: $audio,
        );
    }

    public function packaging(PackagingProfileRow $row): PackagingProfile
    {
        return new PackagingProfile(
            slug: (string) $row->slug,
            container: (string) $row->container,
            segmentDurationMs: (int) $row->segment_duration_ms,
            gopDurationMs: (int) $row->gop_duration_ms,
            timescale: (int) $row->timescale,
            playlistWindowSegments: (int) $row->playlist_window_segments,
            timeShiftBufferSeconds: (int) $row->time_shift_buffer_seconds,
            suggestedPresentationDelayMs: (int) $row->suggested_presentation_delay_ms,
            encryptionScheme: $row->encryption_scheme === null ? null : (string) $row->encryption_scheme,
        );
    }
}
