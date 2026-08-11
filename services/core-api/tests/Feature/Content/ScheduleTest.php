<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ScheduleTest extends TestCase
{
    private function ingest(string $staff, string $channelId, array $programmes): TestResponse
    {
        return $this->postJson('/api/admin/v1/schedule/ingest', [
            'source' => 'test-provider',
            'programmes' => $programmes,
        ], $this->bearer($staff));
    }

    private function programme(string $channelId, array $overrides = []): array
    {
        return array_merge([
            'channel_id' => $channelId,
            'source_ref' => 'p1',
            'title' => 'Evening News',
            'starts_at' => '2026-08-11T18:00:00Z',
            'ends_at' => '2026-08-11T18:30:00Z',
        ], $overrides);
    }

    #[Test]
    public function it_ingests_a_schedule_and_serves_it_over_a_time_window(): void
    {
        $world = $this->playableWorld();

        $this->ingest($world['staff_token'], $world['channel_id'], [
            $this->programme($world['channel_id']),
            $this->programme($world['channel_id'], [
                'source_ref' => 'p2', 'title' => 'Film', 'age_rating' => '15',
                'starts_at' => '2026-08-11T18:30:00Z', 'ends_at' => '2026-08-11T20:00:00Z',
            ]),
        ])->assertStatus(202)->assertJsonPath('data.applied', 2);

        $response = $this->getJson('/api/client/v1/epg?from=2026-08-11T17:00:00Z&to=2026-08-11T21:00:00Z')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertSame('Evening News', $response->json('data.0.title'));
    }

    #[Test]
    public function a_programme_already_in_progress_is_returned(): void
    {
        $world = $this->playableWorld();

        $this->ingest($world['staff_token'], $world['channel_id'], [
            $this->programme($world['channel_id'], [
                'starts_at' => '2026-08-11T17:00:00Z', 'ends_at' => '2026-08-11T19:00:00Z',
            ]),
        ])->assertStatus(202);

        // Overlap, not containment: the programme a viewer is watching when the
        // window opens is the one they care about most.
        $response = $this->getJson('/api/client/v1/epg?from=2026-08-11T18:00:00Z&to=2026-08-11T18:30:00Z');

        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function a_correction_updates_in_place_and_bumps_the_revision(): void
    {
        $world = $this->playableWorld();

        $this->ingest($world['staff_token'], $world['channel_id'], [$this->programme($world['channel_id'])])
            ->assertStatus(202);

        // Providers issue corrections. Reconciling by (source, source_ref) is
        // what stops a corrected programme appearing twice.
        $this->ingest($world['staff_token'], $world['channel_id'], [
            $this->programme($world['channel_id'], ['title' => 'Evening News Extended']),
        ])->assertStatus(202)->assertJsonPath('data.applied', 1);

        $rows = DB::table('schedule.programmes')->where('source_ref', 'p1')->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Evening News Extended', $rows->first()->title);
        $this->assertSame(2, (int) $rows->first()->revision);
    }

    #[Test]
    public function a_malformed_row_is_rejected_without_costing_the_whole_feed(): void
    {
        $world = $this->playableWorld();

        $response = $this->ingest($world['staff_token'], $world['channel_id'], [
            $this->programme($world['channel_id']),
            // Ends before it starts.
            $this->programme($world['channel_id'], [
                'source_ref' => 'bad', 'starts_at' => '2026-08-11T20:00:00Z', 'ends_at' => '2026-08-11T19:00:00Z',
            ]),
            // Unknown channel.
            $this->programme((string) Str::uuid7(), ['source_ref' => 'orphan']),
        ])->assertStatus(202);

        $response->assertJsonPath('data.applied', 1)->assertJsonPath('data.rejected', 2);

        // The raw payload is retained, so a mis-mapping can be diagnosed and
        // the feed reprocessed.
        $this->assertNotNull(DB::table('schedule.ingest_runs')->value('payload'));
    }

    #[Test]
    public function it_refuses_an_unbounded_schedule_range(): void
    {
        // An unbounded range would let one request scan every partition.
        $this->assertProblem(
            $this->getJson('/api/client/v1/epg?from=2020-01-01T00:00:00Z&to=2030-01-01T00:00:00Z'),
            'SCHEDULE_RANGE_TOO_WIDE',
            422,
        );
    }

    #[Test]
    public function it_serves_now_and_next(): void
    {
        $world = $this->playableWorld();

        $this->ingest($world['staff_token'], $world['channel_id'], [
            $this->programme($world['channel_id'], [
                'starts_at' => '2026-08-11T11:00:00Z', 'ends_at' => '2026-08-11T13:00:00Z',
            ]),
            $this->programme($world['channel_id'], [
                'source_ref' => 'p2', 'title' => 'Next Up',
                'starts_at' => '2026-08-11T13:00:00Z', 'ends_at' => '2026-08-11T14:00:00Z',
            ]),
        ])->assertStatus(202);

        $this->getJson("/api/client/v1/channels/{$world['channel_id']}/now-next")
            ->assertOk()
            ->assertJsonPath('now.title', 'Evening News')
            ->assertJsonPath('next.title', 'Next Up');
    }

    #[Test]
    public function now_next_for_an_unknown_channel_is_not_found(): void
    {
        $this->assertProblem(
            $this->getJson('/api/client/v1/channels/'.Str::uuid7().'/now-next'),
            'CHANNEL_NOT_FOUND',
            404,
        );
    }

    #[Test]
    public function channel_metadata_is_publicly_cacheable_and_the_overlay_is_not(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        $metadata = $this->getJson('/api/client/v1/channels')->assertOk();
        // Identical for every caller in a territory, so it may be shared.
        $this->assertStringContainsString('public', (string) $metadata->headers->get('Cache-Control'));

        $overlay = $this->getJson('/api/client/v1/entitlements/channels',
            $this->bearer($world['access_token']))->assertOk();

        // Personalised, so it must never reach a shared cache: that would serve
        // one viewer's entitlements to another.
        $this->assertStringContainsString('private', (string) $overlay->headers->get('Cache-Control'));
        $this->assertTrue($overlay->json('data.0.entitled'));
    }

    #[Test]
    public function ingest_requires_a_content_role(): void
    {
        $world = $this->playableWorld();
        Cache::flush();

        // Default deny: authentication alone grants nothing on the admin surface.
        $auditor = $this->staffToken('auditor');

        $this->assertProblem(
            $this->ingest($auditor, $world['channel_id'], [$this->programme($world['channel_id'])]),
            'FORBIDDEN',
            403,
        );
    }
}
