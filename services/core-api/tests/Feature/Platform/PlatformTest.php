<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PlatformTest extends TestCase
{
    #[Test]
    public function liveness_checks_no_dependencies(): void
    {
        // A liveness probe that checked the database would restart every
        // instance when the database hiccups, turning a recoverable dependency
        // failure into a total outage.
        $this->getJson('/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    #[Test]
    public function readiness_checks_the_database_and_redis(): void
    {
        $this->getJson('/health/ready')
            ->assertOk()
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.redis', true);
    }

    #[Test]
    public function readiness_never_discloses_why_a_dependency_failed(): void
    {
        $body = $this->getJson('/health/ready')->getContent() ?: '';

        // A readiness endpoint that reports connection strings or driver errors
        // is reconnaissance.
        foreach (['password', 'pgsql', '127.0.0.1', 'SQLSTATE'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, $body);
        }
    }

    #[Test]
    public function client_config_is_publicly_cacheable_and_carries_the_limits(): void
    {
        $response = $this->getJson('/api/client/v1/config')->assertOk();

        $response->assertJsonPath('limits.profiles', (int) config('kms.profiles.max_per_account'))
            ->assertJsonPath('limits.devices', (int) config('kms.devices.default_limit'));

        // Nothing here varies per viewer, so it may be shared. Anything that did
        // would have to become `private`.
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function an_unknown_route_returns_a_problem_document_not_an_html_page(): void
    {
        $this->assertProblem($this->getJson('/api/client/v1/does-not-exist'), 'NOT_FOUND', 404);
    }

    #[Test]
    public function a_wrong_method_returns_a_problem_document(): void
    {
        $this->assertProblem($this->getJson('/api/client/v1/auth/login'), 'METHOD_NOT_ALLOWED', 405);
    }

    #[Test]
    public function every_response_carries_a_correlation_id(): void
    {
        $response = $this->getJson('/api/client/v1/config')->assertOk();

        $this->assertNotEmpty($response->headers->get('X-Correlation-Id'));
    }

    #[Test]
    public function a_client_supplied_correlation_id_is_echoed_when_it_is_well_formed(): void
    {
        $this->getJson('/api/client/v1/config', ['X-Correlation-Id' => 'trace-abc-123'])
            ->assertHeader('X-Correlation-Id', 'trace-abc-123');
    }

    #[Test]
    public function a_hostile_correlation_id_is_replaced_rather_than_echoed(): void
    {
        // An unbounded client-supplied header would end up in log lines and
        // audit rows.
        $response = $this->getJson('/api/client/v1/config', ['X-Correlation-Id' => str_repeat('A', 500)]);

        $this->assertNotSame(str_repeat('A', 500), $response->headers->get('X-Correlation-Id'));
    }

    #[Test]
    public function authentication_endpoints_are_rate_limited(): void
    {
        $seen429 = false;

        for ($i = 0; $i < 40; $i++) {
            $status = $this->postJson('/api/client/v1/auth/login', [
                'email' => 'nobody@example.test', 'password' => 'whatever-it-is',
                'device_class' => 'web', 'device_name' => 'Bot',
            ])->getStatusCode();

            if ($status === 429) {
                $seen429 = true;
                break;
            }
        }

        $this->assertTrue($seen429, 'Credential stuffing must be bounded by a rate limit.');
    }

    #[Test]
    public function registration_is_rate_limited_separately(): void
    {
        $seen429 = false;

        for ($i = 0; $i < 20; $i++) {
            $status = $this->postJson('/api/client/v1/auth/register', [
                'email' => "bulk{$i}@example.test", 'password' => 'correct-horse-battery-staple',
                'profile_name' => 'P', 'accepts_terms' => true,
            ])->getStatusCode();

            if ($status === 429) {
                $seen429 = true;
                break;
            }
        }

        $this->assertTrue($seen429, 'Bulk account creation must be bounded.');
    }
}
