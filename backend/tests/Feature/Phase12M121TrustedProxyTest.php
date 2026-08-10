<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class Phase12M121TrustedProxyTest extends TestCase
{
    public function test_health_endpoint_accepts_forwarded_proxy_headers(): void
    {
        $this->getJson('/api/v1/health', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.50',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_production_https_app_url_forces_https_scheme(): void
    {
        config(['app.url' => 'https://app.example.com']);
        $this->app->instance('env', 'production');

        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertSame('https', parse_url(URL::to('/api/v1/health'), PHP_URL_SCHEME));
    }
}
