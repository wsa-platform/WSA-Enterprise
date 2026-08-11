<?php

namespace App\Services\Monitoring;

use App\Contracts\AiMonitoringAnalyzerInterface;
use App\Models\MonitoringEvent;

class StubAiMonitoringAnalyzer implements AiMonitoringAnalyzerInterface
{
    public function analyze(MonitoringEvent $event): MonitoringAnalysis
    {
        $component = $event->component;

        return match ($component) {
            'database' => new MonitoringAnalysis(
                likelyCause: 'Database connectivity or credentials issue.',
                recommendedAction: 'incident.escalate',
                safeToAutoRemediate: false,
                summary: 'Database failures require operator review; automatic remediation is not safe.',
            ),
            'cache' => new MonitoringAnalysis(
                likelyCause: 'Cache store unreachable or misconfigured.',
                recommendedAction: 'cache.clear_probe_keys',
                safeToAutoRemediate: true,
                summary: 'Cache probe failure may be transient; safe probe key cleanup can be attempted.',
            ),
            'queue' => new MonitoringAnalysis(
                likelyCause: 'Queue worker or broker unavailable.',
                recommendedAction: 'health.rerun_checks',
                safeToAutoRemediate: true,
                summary: 'Re-run health checks to confirm queue recovery before escalation.',
            ),
            default => new MonitoringAnalysis(
                likelyCause: 'Component reported unhealthy during readiness probe.',
                recommendedAction: 'health.rerun_checks',
                safeToAutoRemediate: true,
                summary: 'Re-run checks and correlate repeated failures before escalation.',
            ),
        };
    }
}
