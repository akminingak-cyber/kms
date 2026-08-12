<?php

declare(strict_types=1);

namespace Tests\Feature\Contract;

use Illuminate\Support\Facades\Route;
use Modules\Shared\Http\ErrorCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Conformance between the OpenAPI contract and the implementation, in both
 * directions (ADR-0010).
 *
 * Each direction catches a different failure:
 *
 *  - spec → implementation catches "documented but not built", and drift after
 *    a refactor;
 *  - implementation → spec catches the more dangerous case — an endpoint that
 *    exists in production but was never reviewed as part of the contract.
 *
 * Without the second direction the spec quietly becomes a partial description
 * of a larger, undocumented API.
 */
final class OpenApiConformanceTest extends TestCase
{
    private const CONTRACTS = __DIR__.'/../../../../../packages/api-contracts/';

    /**
     * Both surfaces, checked identically.
     *
     * The admin surface was undocumented until Phase 4 while the client surface
     * was covered from Phase 1 — which is exactly how a spec quietly becomes a
     * partial description of a larger API. Parameterising the checks means a
     * surface added later cannot be the one nobody thought to verify.
     *
     * @return array<string,array{0:string,1:string}> surface => [spec path, config key]
     */
    public static function surfaces(): array
    {
        return [
            'client' => ['client/v1/openapi.yaml', 'kms.api.client_prefix'],
            'admin' => ['admin/v1/openapi.yaml', 'kms.api.admin_prefix'],
        ];
    }

    /** @return array<string,mixed> */
    private function spec(string $relativePath): array
    {
        $path = realpath(self::CONTRACTS.$relativePath);
        $this->assertIsString($path, 'API specification not found at '.self::CONTRACTS.$relativePath);

        return Yaml::parseFile($path);
    }

    /** @return list<string> "METHOD /path" for every route on the given surface */
    private function implementedOperations(string $prefixKey): array
    {
        $prefix = (string) config($prefixKey);
        $operations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, $prefix)) {
                continue;
            }

            $path = substr($uri, strlen($prefix));
            $path = $path === '' ? '/' : $path;

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $operations[] = strtoupper($method).' '.$path;
            }
        }

        sort($operations);

        return array_values(array_unique($operations));
    }

    /** @return list<string> */
    private function documentedOperations(string $relativePath): array
    {
        $operations = [];

        foreach ($this->spec($relativePath)['paths'] ?? [] as $path => $item) {
            foreach ($item as $method => $definition) {
                if (! in_array(strtolower((string) $method), ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }

                $operations[] = strtoupper((string) $method).' '.$path;
            }
        }

        sort($operations);

        return $operations;
    }

    #[Test]
    #[DataProvider('surfaces')]
    public function every_implemented_endpoint_is_documented(string $spec, string $prefixKey): void
    {
        $undocumented = array_diff($this->implementedOperations($prefixKey), $this->documentedOperations($spec));

        $this->assertSame([], array_values($undocumented),
            'These endpoints exist but are not in the contract. An endpoint that ships without '
            ."being reviewed as part of the contract is how a spec becomes fiction:\n  "
            .implode("\n  ", $undocumented));
    }

    #[Test]
    #[DataProvider('surfaces')]
    public function every_documented_endpoint_is_implemented(string $spec, string $prefixKey): void
    {
        $missing = array_diff($this->documentedOperations($spec), $this->implementedOperations($prefixKey));

        $this->assertSame([], array_values($missing),
            'These endpoints are documented but not implemented. An unbuilt feature must be absent '
            ."from the spec or return 501 — never documented as if it works:\n  "
            .implode("\n  ", $missing));
    }

    /**
     * Every operation on the admin surface says which roles may reach it.
     *
     * Authentication alone grants nothing here, so an operation whose
     * description does not name its roles is an operation whose access rules
     * were never reviewed — and the route it documents is very likely open to
     * every signed-in staff member.
     */
    #[Test]
    public function every_admin_operation_documents_the_roles_that_may_reach_it(): void
    {
        $undocumented = [];

        foreach ($this->spec('admin/v1/openapi.yaml')['paths'] ?? [] as $path => $item) {
            foreach ($item as $method => $definition) {
                if (! in_array(strtolower((string) $method), ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }

                // Sign-in is the one operation with no role: it is how a role
                // is obtained.
                if ($path === '/auth/sign-in') {
                    continue;
                }

                if (! str_contains((string) ($definition['description'] ?? ''), 'Roles:')) {
                    $undocumented[] = strtoupper((string) $method).' '.$path;
                }
            }
        }

        $this->assertSame([], $undocumented,
            "These admin operations do not state which staff roles may reach them:\n  "
            .implode("\n  ", $undocumented));
    }

    #[Test]
    public function every_error_code_the_application_can_return_is_a_known_code(): void
    {
        // The enum is the registry. This asserts the registry stays coherent:
        // codes are unique, and every one maps to a plausible status.
        $codes = ErrorCode::cases();

        $values = array_map(static fn ($c): string => $c->value, $codes);
        $this->assertSame($values, array_unique($values), 'Duplicate error code values.');

        foreach ($codes as $code) {
            $this->assertGreaterThanOrEqual(400, $code->status(), $code->value.' is not an error status');
            $this->assertLessThan(600, $code->status(), $code->value.' is not a valid HTTP status');
            $this->assertNotSame('', $code->title(), $code->value.' has no title');
            $this->assertStringStartsWith('http', $code->type(), $code->value.' has no type URI');
        }
    }

    #[Test]
    #[DataProvider('surfaces')]
    public function the_specification_declares_problem_details_as_the_error_shape(string $specPath): void
    {
        $spec = $this->spec($specPath);

        $this->assertArrayHasKey(
            'application/problem+json',
            $spec['components']['responses']['Problem']['content'],
            'Errors must be RFC 9457 problem documents.',
        );

        $properties = $spec['components']['schemas']['Problem']['properties'];

        foreach (['type', 'title', 'status', 'code', 'correlation_id'] as $required) {
            $this->assertArrayHasKey($required, $properties, "Problem documents must carry `{$required}`.");
        }
    }
}
