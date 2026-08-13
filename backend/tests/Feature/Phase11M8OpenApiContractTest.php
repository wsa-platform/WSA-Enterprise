<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11M8OpenApiContractTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $requiredPaths = [
        '/health',
        '/analytics/overview',
        '/notifications',
        '/ai/usage',
        '/ai/requests/{id}/cancel',
        '/billing/subscription/plan',
        '/billing/subscription/cancel',
        '/api-clients',
        '/api-clients/{apiClient}/revoke',
        '/teams',
        '/teams/{team}/members',
    ];

    public function test_openapi_spec_contains_phase11_paths(): void
    {
        $specPath = $this->openApiSpecPath();
        $this->assertFileExists($specPath, 'OpenAPI spec missing at docs/openapi.yaml');

        $content = file_get_contents($specPath);
        $this->assertStringContainsString('openapi: 3.1.0', $content);

        foreach ($this->requiredPaths as $requiredPath) {
            $this->assertStringContainsString(
                $requiredPath.':',
                $content,
                "OpenAPI spec missing path: {$requiredPath}"
            );
        }
    }

    public function test_analytics_route_is_registered(): void
    {
        $this->assertTrue(
            collect(app('router')->getRoutes())->contains(
                fn ($route) => $route->uri() === 'api/v1/analytics/overview'
                    && in_array('GET', $route->methods(), true)
            )
        );
    }
}
