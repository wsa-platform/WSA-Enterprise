<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MonitoringEvent;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\MonitoringEventService;
use App\Services\Monitoring\RemediationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12M124HealthMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_health_endpoint_returns_ok_without_dependency_checks(): void
    {
        $this->getJson('/api/v1/health/live')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'service']);
    }

    public function test_ready_health_endpoint_returns_ok_when_dependencies_are_healthy(): void
    {
        $this->getJson('/api/v1/health/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'database',
                    'cache',
                    'queue',
                    'storage',
                    'authentication',
                    'application',
                    'api',
                ],
            ]);
    }

    public function test_legacy_health_endpoint_remains_backward_compatible_when_healthy(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_ready_health_returns_degraded_when_dependency_fails(): void
    {
        $this->mock(HealthCheckService::class, function ($mock): void {
            $mock->shouldReceive('live')->andReturn(['status' => 'ok', 'service' => 'WSA']);
            $mock->shouldReceive('ready')->andReturn([
                'status' => 'degraded',
                'checks' => [
                    'database' => ['healthy' => true],
                    'cache' => ['healthy' => false, 'message' => 'Cache unavailable'],
                ],
            ]);
        });

        config(['monitoring.record_incidents_on_ready_failure' => false]);

        $this->getJson('/api/v1/health/ready')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.cache.healthy', false);
    }

    public function test_readiness_failure_creates_monitoring_incident(): void
    {
        $this->mock(HealthCheckService::class, function ($mock): void {
            $mock->shouldReceive('live')->andReturn(['status' => 'ok', 'service' => 'WSA']);
            $mock->shouldReceive('ready')->andReturn([
                'status' => 'degraded',
                'checks' => [
                    'cache' => ['healthy' => false, 'message' => 'Cache unavailable'],
                ],
            ]);
        });

        $this->getJson('/api/v1/health/ready', [
            'X-Request-Id' => 'health-incident-req',
        ])->assertStatus(503);

        $this->assertDatabaseHas('monitoring_events', [
            'component' => 'cache',
            'status' => MonitoringEvent::STATUS_OPEN,
            'lifecycle_stage' => MonitoringEvent::STAGE_ANALYZED,
            'severity' => 'warning',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'monitoring.incident.detected',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'monitoring.incident.analyzed',
        ]);
    }

    public function test_monitoring_incident_can_be_resolved(): void
    {
        $event = app(MonitoringEventService::class)->detect(
            component: 'queue',
            details: ['message' => 'Worker unavailable'],
            severity: 'warning',
        );

        app(MonitoringEventService::class)->resolve($event, note: 'Worker restarted manually.');

        $event->refresh();

        $this->assertSame(MonitoringEvent::STATUS_RESOLVED, $event->status);
        $this->assertSame(MonitoringEvent::STAGE_RESOLVED, $event->lifecycle_stage);
        $this->assertNotNull($event->resolved_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'monitoring.incident.resolved',
            'auditable_type' => MonitoringEvent::class,
            'auditable_id' => $event->id,
        ]);
    }

    public function test_remediation_allowlist_permits_safe_actions(): void
    {
        $event = MonitoringEvent::create([
            'component' => 'cache',
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => 'warning',
            'lifecycle_stage' => MonitoringEvent::STAGE_ANALYZED,
            'detected_at' => now(),
        ]);

        $result = app(RemediationService::class)->execute('health.rerun_checks', $event);

        $this->assertTrue($result->allowed);
        $this->assertTrue($result->success);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'monitoring.remediation.succeeded',
            'auditable_id' => $event->id,
        ]);
    }

    public function test_unauthorized_remediation_action_is_rejected(): void
    {
        $event = MonitoringEvent::create([
            'component' => 'database',
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => 'critical',
            'lifecycle_stage' => MonitoringEvent::STAGE_ANALYZED,
            'detected_at' => now(),
        ]);

        $result = app(RemediationService::class)->execute('shell.restart_service', $event);

        $this->assertFalse($result->allowed);
        $this->assertFalse($result->success);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'monitoring.remediation.rejected',
            'auditable_id' => $event->id,
        ]);

        $this->assertSame(0, AuditLog::query()->where('action', 'monitoring.remediation.succeeded')->count());
    }

    public function test_high_risk_automatic_remediation_requires_human_approval(): void
    {
        config(['monitoring.auto_remediation' => true]);

        $event = MonitoringEvent::create([
            'component' => 'database',
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => 'critical',
            'lifecycle_stage' => MonitoringEvent::STAGE_ANALYZED,
            'detected_at' => now(),
        ]);

        $result = app(RemediationService::class)->execute('incident.escalate', $event, automatic: true);

        $this->assertTrue($result->allowed);
        $this->assertFalse($result->success);
        $this->assertSame('High-risk remediation requires human approval.', $result->message);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'monitoring.remediation.rejected',
            'auditable_id' => $event->id,
        ]);
    }
}
