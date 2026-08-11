<?php

namespace Tests\Feature;

use App\Models\MonitoringEvent;
use App\Support\HealthCheckMessages;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\MonitoringEventService;
use App\Services\Monitoring\RemediationService;
use App\Services\Monitoring\SafeRemediationExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Phase13M131ObservabilityOpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_messages_are_sanitized_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        $message = HealthCheckMessages::forFailure(
            'database',
            new \RuntimeException('secret connection string'),
        );

        $this->assertSame('Database connection failed.', $message);
        $this->assertStringNotContainsString('secret', $message);
    }

    public function test_cache_probe_remediation_clears_configured_probe_key(): void
    {
        Cache::put('healthcheck:probe:write', 'stale', 60);

        $event = MonitoringEvent::create([
            'component' => 'cache',
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => 'warning',
            'lifecycle_stage' => MonitoringEvent::STAGE_DETECTED,
            'detected_at' => now(),
        ]);

        $result = app(RemediationService::class)->execute('cache.clear_probe_keys', $event);

        $this->assertTrue($result->allowed);
        $this->assertTrue($result->success);
        $this->assertNull(Cache::get('healthcheck:probe:write'));
    }

    public function test_open_incidents_are_deduplicated_for_same_component(): void
    {
        config(['monitoring.deduplicate_open_incidents' => true]);

        MonitoringEvent::create([
            'component' => 'cache',
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => 'warning',
            'lifecycle_stage' => MonitoringEvent::STAGE_DETECTED,
            'detected_at' => now()->subMinute(),
        ]);

        $events = app(MonitoringEventService::class)->recordReadinessFailures([
            'cache' => ['healthy' => false, 'message' => 'Cache unavailable'],
        ]);

        $this->assertCount(1, $events);
        $this->assertSame(1, MonitoringEvent::query()->where('component', 'cache')->count());
    }

    public function test_scheduler_heartbeat_schedule_is_registered(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('monitoring-scheduler-heartbeat');
    }

    public function test_safe_remediation_executor_is_not_publicly_resolvable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resolve RemediationService');

        app(SafeRemediationExecutor::class);
    }

    public function test_certbot_deploy_hook_script_exists(): void
    {
        $path = $this->repoRoot().DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'certbot-deploy-hook.sh';

        $this->assertFileExists($path);
        $this->assertNotSame('', trim((string) file_get_contents($path)));
    }

    private function repoRoot(): string
    {
        foreach ([dirname(base_path()), '/var/www/repo'] as $root) {
            if (is_file($root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'certbot-deploy-hook.sh')) {
                return $root;
            }
        }

        $this->markTestSkipped('Certbot deploy hook script not available in this environment.');
    }
}
