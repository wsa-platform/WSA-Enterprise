<?php

namespace App\Services\Monitoring;

use App\Contracts\AiMonitoringAnalyzerInterface;
use App\Models\MonitoringEvent;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonitoringEventService
{
    public function __construct(
        private AuditService $auditService,
        private AiMonitoringAnalyzerInterface $analyzer,
    ) {}

    /**
     * @param  array<string, array{healthy: bool, message?: string}>  $checks
     * @return list<MonitoringEvent>
     */
    public function recordReadinessFailures(array $checks, ?Request $request = null): array
    {
        if (! config('monitoring.enabled', true)) {
            return [];
        }

        $events = [];

        foreach ($checks as $component => $check) {
            if (($check['healthy'] ?? false) === true) {
                continue;
            }

            $events[] = $this->detect(
                component: (string) $component,
                details: [
                    'message' => $check['message'] ?? 'Health check failed.',
                    'source' => 'readiness_probe',
                ],
                severity: $this->severityForComponent((string) $component),
                request: $request,
            );
        }

        return $events;
    }

    /** @param  array<string, mixed>  $details */
    public function detect(
        string $component,
        array $details = [],
        string $severity = 'warning',
        ?Request $request = null,
        ?string $correlationId = null,
    ): MonitoringEvent {
        $requestId = $request?->attributes->get('request_id');
        $correlationId ??= (string) Str::uuid();

        $event = MonitoringEvent::create([
            'component' => $component,
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => $severity,
            'lifecycle_stage' => MonitoringEvent::STAGE_DETECTED,
            'detected_at' => now(),
            'details' => $details,
            'request_id' => is_string($requestId) ? $requestId : null,
            'correlation_id' => $correlationId,
        ]);

        $this->auditService->record(
            action: 'monitoring.incident.detected',
            auditable: $event,
            newValues: [
                'component' => $component,
                'severity' => $severity,
                'lifecycle_stage' => MonitoringEvent::STAGE_DETECTED,
                'correlation_id' => $correlationId,
                'details' => $details,
            ],
            request: $request,
        );

        return $this->analyze($event, $request);
    }

    public function analyze(MonitoringEvent $event, ?Request $request = null): MonitoringEvent
    {
        $analysis = $this->analyzer->analyze($event);

        $event->fill([
            'lifecycle_stage' => MonitoringEvent::STAGE_ANALYZED,
            'analysis_summary' => $analysis->summary,
            'remediation_action' => $analysis->recommendedAction,
            'remediation_status' => $analysis->safeToAutoRemediate ? 'pending' : 'escalated',
        ])->save();

        $this->auditService->record(
            action: 'monitoring.incident.analyzed',
            auditable: $event,
            newValues: [
                'likely_cause' => $analysis->likelyCause,
                'recommended_action' => $analysis->recommendedAction,
                'safe_to_auto_remediate' => $analysis->safeToAutoRemediate,
                'summary' => $analysis->summary,
            ],
            request: $request,
        );

        return $event->refresh();
    }

    public function resolve(MonitoringEvent $event, ?Request $request = null, ?string $note = null): MonitoringEvent
    {
        $event->fill([
            'status' => MonitoringEvent::STATUS_RESOLVED,
            'lifecycle_stage' => MonitoringEvent::STAGE_RESOLVED,
            'resolved_at' => now(),
            'remediation_status' => 'succeeded',
        ])->save();

        $this->auditService->record(
            action: 'monitoring.incident.resolved',
            auditable: $event,
            newValues: [
                'lifecycle_stage' => MonitoringEvent::STAGE_RESOLVED,
                'note' => $note,
            ],
            request: $request,
        );

        return $event->refresh();
    }

    public function escalate(MonitoringEvent $event, ?Request $request = null, ?string $reason = null): MonitoringEvent
    {
        $event->fill([
            'lifecycle_stage' => MonitoringEvent::STAGE_ESCALATED,
            'remediation_status' => 'escalated',
        ])->save();

        $this->auditService->record(
            action: 'monitoring.incident.escalated',
            auditable: $event,
            newValues: [
                'lifecycle_stage' => MonitoringEvent::STAGE_ESCALATED,
                'reason' => $reason,
            ],
            request: $request,
        );

        return $event->refresh();
    }

    private function severityForComponent(string $component): string
    {
        return match ($component) {
            'database', 'authentication' => 'critical',
            'cache', 'queue', 'storage' => 'warning',
            default => 'info',
        };
    }
}
