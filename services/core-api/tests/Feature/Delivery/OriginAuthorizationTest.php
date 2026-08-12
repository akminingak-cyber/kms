<?php

declare(strict_types=1);

namespace Tests\Feature\Delivery;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Delivery\Domain\DeliveryToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The origin's `auth_request` sub-request.
 *
 * This runs once per manifest and once per segment, which at any real
 * concurrency is the highest request rate in the platform. It therefore does no
 * database work at all, and these tests assert that as a property rather than
 * trusting the comment: a single query here would put the control-plane
 * database on the media path.
 */
final class OriginAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The endpoint is restricted at the network layer, not by a bearer
        // token, so a test has to say where it is calling from.
        config(['kms.delivery.origin_networks' => ['127.0.0.1/32']]);
    }

    #[Test]
    public function it_admits_a_request_carrying_a_valid_token(): void
    {
        $world = $this->playableWorld();
        $url = $this->startPlayback($world)->assertCreated()->json('delivery.targets.0.url');

        $uri = $this->originUri($url);

        $response = $this->get('/internal/v1/delivery/authorize', ['X-Original-URI' => $uri]);

        // 204 is what nginx `auth_request` requires to serve the object.
        $response->assertNoContent();
        // Echoed so the origin's access log joins to the decision log by
        // session, which is what makes one viewer's complaint traceable from
        // the play button to the segment.
        $this->assertNotEmpty($response->headers->get('X-KMS-Session'));
    }

    #[Test]
    public function the_same_token_admits_every_object_under_the_publication(): void
    {
        $world = $this->playableWorld();
        $url = $this->startPlayback($world)->assertCreated()->json('delivery.targets.0.url');

        parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);
        $token = (string) $query['t'];
        $prefix = '/live/'.$world['channel_id'];

        // A player fetches one manifest and then thousands of segments, and
        // cannot obtain a token for each. The token is scoped to the prefix.
        foreach (['/v720p/init.mp4', '/v720p/seg-1234.m4s', '/h720/manifest.m3u8'] as $object) {
            $this->get('/internal/v1/delivery/authorize', [
                'X-Original-URI' => $prefix.$object.'?t='.$token,
            ])->assertNoContent();
        }
    }

    #[Test]
    public function it_does_no_database_work(): void
    {
        $world = $this->playableWorld();
        $url = $this->startPlayback($world)->assertCreated()->json('delivery.targets.0.url');
        $uri = $this->originUri($url);

        Cache::flush();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->get('/internal/v1/delivery/authorize', ['X-Original-URI' => $uri])->assertNoContent();

        /*
         * The property that makes this endpoint deployable at segment rate. It
         * also fixes the revocation model: because nothing is read, ending a
         * session takes effect within the token lifetime rather than instantly,
         * and that bound is configuration rather than an accident.
         */
        $this->assertSame(0, $queries, 'the origin authorizer must not touch the database');
    }

    #[Test]
    public function a_token_for_one_channel_does_not_open_another(): void
    {
        $world = $this->playableWorld();
        $url = $this->startPlayback($world)->assertCreated()->json('delivery.targets.0.url');

        parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

        $response = $this->getJson('/internal/v1/delivery/authorize', [
            'X-Original-URI' => '/live/019ff0f4-0000-7000-8000-0000000000ff/full/manifest.m3u8?t='.$query['t'],
        ]);

        $this->assertProblem($response, 'DELIVERY_TOKEN_INVALID', 403);
    }

    #[Test]
    public function an_expired_token_is_refused(): void
    {
        $world = $this->playableWorld();
        $url = $this->startPlayback($world)->assertCreated()->json('delivery.targets.0.url');
        $uri = $this->originUri($url);

        // Past the delivery token's lifetime. This is the revocation mechanism:
        // a client that stops heartbeating stops being able to renew.
        $this->freezeTimeAt('2026-08-11T13:00:00+00:00');

        $this->assertProblem(
            $this->getJson('/internal/v1/delivery/authorize', ['X-Original-URI' => $uri]),
            'DELIVERY_TOKEN_EXPIRED',
            403,
        );
    }

    #[Test]
    public function a_request_with_no_token_is_refused(): void
    {
        $this->assertProblem(
            $this->getJson('/internal/v1/delivery/authorize', [
                'X-Original-URI' => '/live/019ff0f4-0000-7000-8000-000000000001/full/manifest.m3u8',
            ]),
            'DELIVERY_PATH_NOT_PERMITTED',
            403,
        );
    }

    #[Test]
    public function a_path_that_is_not_a_publication_prefix_is_refused(): void
    {
        $token = (new DeliveryToken('test-delivery-key-not-a-secret'))
            ->issue('/live/019ff0f4-0000-7000-8000-000000000001', 'session', 2_000_000_000);

        // Anything that does not match a publication prefix exactly must be
        // refused rather than coerced into one — the prefix is what the
        // signature is checked against.
        foreach (['/etc/passwd', '/live/../secrets', '/live/not-a-uuid/manifest.m3u8', '/'] as $path) {
            $this->assertProblem(
                $this->getJson('/internal/v1/delivery/authorize', ['X-Original-URI' => $path.'?t='.$token]),
                'DELIVERY_PATH_NOT_PERMITTED',
                403,
            );
        }
    }

    #[Test]
    public function the_endpoint_is_closed_to_networks_that_were_not_configured(): void
    {
        // Empty by default, and an empty list denies everything: a deployment
        // that has not said where its origin runs does not get an open
        // token-verification oracle.
        config(['kms.delivery.origin_networks' => []]);

        $this->assertProblem(
            $this->getJson('/internal/v1/delivery/authorize', ['X-Original-URI' => '/live/x']),
            'FORBIDDEN',
            403,
        );
    }

    #[Test]
    public function a_configured_network_range_admits_addresses_inside_it(): void
    {
        config(['kms.delivery.origin_networks' => ['10.0.0.0/8', '127.0.0.0/24']]);

        $this->getJson('/internal/v1/delivery/authorize', ['X-Original-URI' => '/live/x'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'DELIVERY_PATH_NOT_PERMITTED');
    }

    private function originUri(?string $url): string
    {
        return (string) parse_url((string) $url, PHP_URL_PATH).'?'.parse_url((string) $url, PHP_URL_QUERY);
    }
}
