<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class Phase11M9OpenApiRouteParityTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $httpMethods = ['get', 'post', 'put', 'patch', 'delete'];

    public function test_documented_openapi_paths_match_registered_routes(): void
    {
        $specPath = env('OPENAPI_SPEC_PATH', dirname(base_path()).'/docs/openapi.yaml');
        $this->assertFileExists($specPath);

        $content = file_get_contents($specPath);
        $documented = $this->parseOpenApiPaths($content);

        $this->assertNotEmpty($documented, 'No OpenAPI paths parsed');

        $routes = collect(RouteFacade::getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), 'api/v1/'))
            ->map(fn (Route $route) => [
                'path' => '/'.substr($route->uri(), strlen('api/v1/')),
                'methods' => collect($route->methods())
                    ->map(fn (string $method) => strtolower($method))
                    ->reject(fn (string $method) => $method === 'head')
                    ->values()
                    ->all(),
            ])
            ->values();

        foreach ($documented as $openApiPath => $methods) {
            $matching = $routes->filter(
                fn (array $route) => $this->pathsMatch($openApiPath, $route['path'])
            );

            $this->assertNotEmpty(
                $matching,
                "OpenAPI path {$openApiPath} has no matching Laravel route"
            );

            foreach ($methods as $method) {
                $this->assertTrue(
                    $matching->contains(fn (array $route) => in_array($method, $route['methods'], true)),
                    "OpenAPI documents {$method} {$openApiPath} but route methods do not include {$method}"
                );
            }
        }
    }

    public function test_phase11_enterprise_routes_are_documented_in_openapi(): void
    {
        $specPath = env('OPENAPI_SPEC_PATH', dirname(base_path()).'/docs/openapi.yaml');
        $content = file_get_contents($specPath);

        $required = [
            '/analytics/overview',
            '/api-clients',
            '/api-clients/{apiClient}/revoke',
            '/billing/subscription/plan',
            '/billing/subscription/cancel',
            '/billing/subscription/reactivate',
            '/notifications',
            '/notifications/{notification}/read',
            '/ai/usage',
            '/ai/requests/{id}/cancel',
            '/platform/workflow-summary',
            '/audit-logs',
        ];

        foreach ($required as $path) {
            $this->assertStringContainsString($path.':', $content, "Missing OpenAPI path {$path}");
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function parseOpenApiPaths(string $content): array
    {
        $paths = [];
        $lines = explode("\n", $content);
        $currentPath = null;
        $indent = null;

        foreach ($lines as $line) {
            if (preg_match('/^  (\/[^:]+):\s*$/', $line, $matches)) {
                $currentPath = $matches[1];
                $paths[$currentPath] = [];
                $indent = null;

                continue;
            }

            if ($currentPath === null) {
                continue;
            }

            if (preg_match('/^    ([a-z]+):\s*$/', $line, $matches)
                && in_array($matches[1], $this->httpMethods, true)) {
                $paths[$currentPath][] = $matches[1];
            }
        }

        return array_filter($paths, fn (array $methods) => $methods !== []);
    }

    private function pathsMatch(string $openApiPath, string $routePath): bool
    {
        $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $openApiPath);

        return (bool) preg_match('#^'.$pattern.'$#', $routePath);
    }
}
