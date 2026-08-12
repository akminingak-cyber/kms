<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The media control plane's admin surface.
 *
 * The failure paths carry most of the weight here: a ladder that cannot be
 * encoded, or an encoder nobody has licensed, must be refused at configuration
 * time rather than discovered by a pipeline at three in the morning.
 */
final class MediaControlPlaneTest extends TestCase
{
    #[Test]
    public function an_encoder_with_no_recorded_licence_is_refused(): void
    {
        // The allow-list is empty by default, and an empty list permits
        // nothing. Absence of a cleared licence is a prohibition.
        config(['kms.media.permitted_encoders' => []]);

        $staff = $this->staffToken();

        $response = $this->postJson('/api/admin/v1/media/ladders', [
            'slug' => 'unlicensed', 'name' => 'Unlicensed', 'content_class' => 'generic',
            'rungs' => $this->defaultRungs(),
            'audio' => [$this->audio()],
        ], $this->bearer($staff));

        $this->assertProblem($response, 'MEDIA_ENCODER_NOT_PERMITTED', 422);
        $this->assertSame(0, DB::table('media.encoding_ladders')->count(), 'nothing may be written');
    }

    #[Test]
    public function a_ladder_whose_bitrates_do_not_increase_is_refused_and_rolled_back(): void
    {
        $staff = $this->staffToken();

        $rungs = $this->defaultRungs();
        $rungs[1]['video_bitrate_kbps'] = 500;

        $response = $this->postJson('/api/admin/v1/media/ladders', [
            'slug' => 'flat', 'name' => 'Flat', 'content_class' => 'generic',
            'rungs' => $rungs,
            'audio' => [$this->audio()],
        ], $this->bearer($staff));

        $this->assertProblem($response, 'MEDIA_LADDER_INVALID', 422);

        // Validated against what was written, then rolled back — so an invalid
        // ladder is never left in the database for a manifest to trip over.
        $this->assertSame(0, DB::table('media.encoding_ladders')->count());
        $this->assertSame(0, DB::table('media.ladder_rungs')->count());
    }

    #[Test]
    public function a_packaging_profile_whose_segments_straddle_gops_is_refused(): void
    {
        $staff = $this->staffToken();

        $response = $this->postJson('/api/admin/v1/media/packaging-profiles', [
            'slug' => 'misaligned', 'name' => 'Misaligned', 'container' => 'cmaf',
            // 2000ms segments over 700ms GOPs: segment boundaries would not
            // land on IDR frames, so players could not switch there.
            'segment_duration_ms' => 2000, 'gop_duration_ms' => 700,
            'timescale' => 90_000, 'playlist_window_segments' => 6,
            'time_shift_buffer_seconds' => 60, 'suggested_presentation_delay_ms' => 6000,
        ], $this->bearer($staff));

        $this->assertProblem($response, 'MEDIA_SEGMENT_ALIGNMENT_INVALID', 422);
    }

    #[Test]
    public function publication_refuses_a_gop_that_is_not_whole_frames_at_the_ladders_rate(): void
    {
        $staff = $this->staffToken();

        $channelId = $this->postJson('/api/admin/v1/channels', [
            'slug' => 'ntsc', 'name' => 'NTSC', 'number' => 9, 'catchup_enabled' => false,
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $rungs = $this->defaultRungs();
        foreach ($rungs as $index => $rung) {
            $rungs[$index]['frame_rate'] = '30000/1001';
        }

        $ladderId = $this->postJson('/api/admin/v1/media/ladders', [
            'slug' => 'ntsc-ladder', 'name' => 'NTSC', 'content_class' => 'sport',
            'rungs' => $rungs, 'audio' => [$this->audio()],
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        // Valid on its own: 2000ms is two whole 1000ms GOPs.
        $packagingId = $this->postJson('/api/admin/v1/media/packaging-profiles', [
            'slug' => 'pal-packaging', 'name' => 'PAL', 'container' => 'cmaf',
            'segment_duration_ms' => 2000, 'gop_duration_ms' => 1000,
            'timescale' => 90_000, 'playlist_window_segments' => 6,
            'time_shift_buffer_seconds' => 60, 'suggested_presentation_delay_ms' => 6000,
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        $publicationId = $this->postJson('/api/admin/v1/media/publications', [
            'subject_type' => 'channel', 'subject_id' => $channelId,
            'ladder_id' => $ladderId, 'packaging_profile_id' => $packagingId,
        ], $this->bearer($staff))->assertCreated()->json('data.id');

        /*
         * The cross-object defect neither could see alone: 1000ms is 29.97
         * frames at 30000/1001, so the encoder would round — differently for a
         * rung at half rate — and IDR frames would drift apart between
         * renditions. Only the pairing is invalid.
         */
        $this->assertProblem(
            $this->postJson("/api/admin/v1/media/publications/{$publicationId}/publish", [], $this->bearer($staff)),
            'MEDIA_SEGMENT_ALIGNMENT_INVALID',
            422,
        );
    }

    #[Test]
    public function publishing_generates_one_manifest_per_quality_class_and_format(): void
    {
        $world = $this->playableWorld();

        $manifests = DB::table('media.publication_manifests')->get();

        // Five quality classes × two formats. Per class, not per viewer: that
        // is what keeps a capped manifest cacheable.
        $this->assertCount(10, $manifests);
        $this->assertSame(
            ['dash', 'hls'],
            collect($manifests)->pluck('format')->unique()->sort()->values()->all(),
        );

        foreach ($manifests as $manifest) {
            $this->assertNotEmpty($manifest->content_hash);
            $this->assertGreaterThan(0, $manifest->byte_size);
        }
    }

    #[Test]
    public function an_operator_can_read_the_exact_bytes_a_player_will_receive(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        $response = $this->get(
            "/api/admin/v1/media/publications/{$world['publication_id']}/manifests/h720/hls",
            $this->bearer($world['staff_token']),
        )->assertOk();

        $response->assertHeader('Content-Type', 'application/vnd.apple.mpegurl');
        $body = $response->getContent();

        $this->assertStringStartsWith('#EXTM3U', $body);
        // The cap is expressed by absence, which is why an operator has to be
        // able to see the bytes rather than a rendering of them.
        $this->assertStringContainsString('1280x720', $body);
        $this->assertStringNotContainsString('1920x1080', $body);
    }

    #[Test]
    public function republishing_bumps_the_revision_and_records_identical_hashes(): void
    {
        $world = $this->playableWorld();
        $before = DB::table('media.publication_manifests')->orderBy('id')->pluck('content_hash')->all();

        Cache::flush();
        $this->postJson("/api/admin/v1/media/publications/{$world['publication_id']}/publish", [],
            $this->bearer($world['staff_token']))->assertCreated();

        $after = DB::table('media.publication_manifests')->orderBy('id')->pluck('content_hash')->all();

        $this->assertSame(2, (int) DB::table('media.publications')->value('revision'));
        // The HLS hashes must be unchanged: a republication that alters what
        // players receive is a client contract change, and it has to be
        // distinguishable from one that does not.
        $this->assertSame(
            array_slice($before, 0, 1),
            array_slice($after, 0, 1),
            'regenerating the same ladder must produce the same manifest',
        );
    }

    #[Test]
    public function retiring_a_publication_removes_its_manifests_but_keeps_the_record(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        $this->postJson("/api/admin/v1/media/publications/{$world['publication_id']}/retire", [],
            $this->bearer($world['staff_token']))->assertNoContent();

        $this->assertSame('retired', DB::table('media.publications')->value('status'));

        Cache::flush();

        // Removing the manifests is enough to stop new playback finding it;
        // the media itself is not deleted, because a retired channel is
        // frequently un-retired.
        $this->assertProblem(
            $this->getJson("/api/admin/v1/media/publications/{$world['publication_id']}/manifests/full/hls",
                $this->bearer($world['staff_token'])),
            'MEDIA_MANIFEST_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function the_ffmpeg_command_is_returned_without_a_muxer_and_says_so(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        $ladderId = $this->getJson('/api/admin/v1/media/ladders', $this->bearer($world['staff_token']))
            ->json('data.0.id');
        $packagingId = DB::table('media.packaging_profiles')->value('uuid');

        Cache::flush();

        $response = $this->postJson("/api/admin/v1/media/ladders/{$ladderId}/ffmpeg-command", [
            'packaging_profile_id' => $packagingId,
            'input_url' => 'srt://ingest.internal:9000',
        ], $this->bearer($world['staff_token']))->assertOk();

        $arguments = $response->json('data.arguments');

        $this->assertContains('-filter_complex', $arguments);
        $this->assertContains('srt://ingest.internal:9000', $arguments);
        // Absent, and explicitly explained. A command that looks runnable and
        // is not would be worse than one that says what is missing.
        $this->assertNull($response->json('data.output_stage'));
        $this->assertStringContainsString('No packager has been selected', (string) $response->json('data.output_stage_note'));
    }

    #[Test]
    public function the_media_surface_is_closed_to_other_staff_roles(): void
    {
        // A ladder change alters encoding cost, storage cost and picture
        // quality at once. It is not the same authority as editing a programme
        // description.
        $contentOperator = $this->staffToken('content_operator');

        $this->postJson('/api/admin/v1/media/ladders', [
            'slug' => 'x', 'name' => 'X', 'content_class' => 'generic',
            'rungs' => $this->defaultRungs(), 'audio' => [$this->audio()],
        ], $this->bearer($contentOperator))->assertForbidden();
    }

    #[Test]
    public function the_media_surface_requires_staff_authentication(): void
    {
        $this->postJson('/api/admin/v1/media/ladders', [])->assertUnauthorized();
    }

    /** @return array<string,mixed> */
    private function audio(): array
    {
        return [
            'label' => 'audio-en', 'language' => 'en', 'role' => 'main', 'codec' => 'aac-lc',
            'bitrate_kbps' => 128, 'channels' => 2, 'sample_rate_hz' => 48_000,
            'source_stream_index' => 0, 'is_default' => true,
        ];
    }
}
