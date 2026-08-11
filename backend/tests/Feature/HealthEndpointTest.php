<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_live_endpoint_returns_ok(): void
    {
        $this->getJson('/api/v1/health/live')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'service' => 'wsa-enterprise',
            ]);
    }

    public function test_legacy_health_endpoint_remains_live_check(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_ready_endpoint_reports_dependency_checks(): void
    {
        $response = $this->getJson('/api/v1/health/ready');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.cache', true);
    }
}
