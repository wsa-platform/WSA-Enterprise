<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringEvent;

class SafeRemediationExecutor
{
    public function __construct(
        private HealthCheckService $healthChecks,
    ) {}

    public function execute(string $action, MonitoringEvent $event): RemediationResult
    {
        return match ($action) {
            'cache.clear_probe_keys' => $this->clearCacheProbeKeys(),
            'health.rerun_checks' => $this->rerunChecks(),
            'incident.mark_analyzed' => $this->markAnalyzed($event),
            'incident.escalate' => $this->escalate($event),
            'incident.resolve' => $this->resolve($event),
            default => new RemediationResult(
                success: false,
                message: 'Unknown remediation action.',
                allowed: false,
            ),
        };
    }

    private function clearCacheProbeKeys(): RemediationResult
    {
        $cleared = app(HealthCheckService::class)->clearCacheProbeKey();

        return new RemediationResult(
            success: $cleared,
            message: $cleared
                ? 'Health probe cache key cleared.'
                : 'Health probe cache key could not be cleared.',
            allowed: true,
        );
    }

    private function rerunChecks(): RemediationResult
    {
        $checks = $this->healthChecks->runAllChecks();

        return new RemediationResult(
            success: true,
            message: 'Health checks re-run successfully.',
            allowed: true,
            payload: ['checks' => $checks],
        );
    }

    private function markAnalyzed(MonitoringEvent $event): RemediationResult
    {
        $event->fill([
            'lifecycle_stage' => MonitoringEvent::STAGE_ANALYZED,
        ])->save();

        return new RemediationResult(
            success: true,
            message: 'Incident marked analyzed.',
            allowed: true,
        );
    }

    private function escalate(MonitoringEvent $event): RemediationResult
    {
        $event->fill([
            'lifecycle_stage' => MonitoringEvent::STAGE_ESCALATED,
            'remediation_status' => 'escalated',
        ])->save();

        return new RemediationResult(
            success: true,
            message: 'Incident escalated for human review.',
            allowed: true,
        );
    }

    private function resolve(MonitoringEvent $event): RemediationResult
    {
        $event->fill([
            'status' => MonitoringEvent::STATUS_RESOLVED,
            'lifecycle_stage' => MonitoringEvent::STAGE_RESOLVED,
            'resolved_at' => now(),
            'remediation_status' => 'succeeded',
        ])->save();

        return new RemediationResult(
            success: true,
            message: 'Incident marked resolved.',
            allowed: true,
        );
    }
}
