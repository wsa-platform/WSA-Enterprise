<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringEvent;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class RemediationService
{
    public function __construct(
        private SafeRemediationExecutor $executor,
        private AuditService $auditService,
    ) {}

    public function isAllowed(string $action): bool
    {
        return in_array($action, config('monitoring.allowed_remediation_actions', []), true);
    }

    public function isHighRisk(string $action): bool
    {
        return in_array($action, config('monitoring.high_risk_actions', []), true);
    }

    public function execute(
        string $action,
        MonitoringEvent $event,
        ?Request $request = null,
        bool $automatic = false,
    ): RemediationResult {
        if (! $this->isAllowed($action)) {
            $result = new RemediationResult(
                success: false,
                message: 'Remediation action is not on the safe allowlist.',
                allowed: false,
            );

            $this->auditService->record(
                action: 'monitoring.remediation.rejected',
                auditable: $event,
                newValues: [
                    'action' => $action,
                    'reason' => 'not_allowlisted',
                    'automatic' => $automatic,
                ],
                request: $request,
            );

            return $result;
        }

        if ($automatic && ! config('monitoring.auto_remediation', false)) {
            $result = new RemediationResult(
                success: false,
                message: 'Automatic remediation is disabled.',
                allowed: true,
            );

            $this->auditService->record(
                action: 'monitoring.remediation.rejected',
                auditable: $event,
                newValues: [
                    'action' => $action,
                    'reason' => 'auto_remediation_disabled',
                ],
                request: $request,
            );

            return $result;
        }

        if ($automatic && $this->isHighRisk($action)) {
            $result = new RemediationResult(
                success: false,
                message: 'High-risk remediation requires human approval.',
                allowed: true,
            );

            $this->auditService->record(
                action: 'monitoring.remediation.rejected',
                auditable: $event,
                newValues: [
                    'action' => $action,
                    'reason' => 'high_risk_requires_approval',
                ],
                request: $request,
            );

            return $result;
        }

        $this->auditService->record(
            action: 'monitoring.remediation.attempted',
            auditable: $event,
            newValues: [
                'action' => $action,
                'automatic' => $automatic,
            ],
            request: $request,
        );

        $event->fill([
            'lifecycle_stage' => MonitoringEvent::STAGE_REMEDIATION_ATTEMPTED,
            'remediation_action' => $action,
            'remediation_status' => 'attempted',
        ])->save();

        $result = $this->executor->execute($action, $event);

        $event->fill([
            'remediation_status' => $result->success ? 'succeeded' : 'failed',
            'lifecycle_stage' => $result->success
                ? MonitoringEvent::STAGE_VERIFIED
                : MonitoringEvent::STAGE_REMEDIATION_ATTEMPTED,
        ])->save();

        $this->auditService->record(
            action: $result->success
                ? 'monitoring.remediation.succeeded'
                : 'monitoring.remediation.failed',
            auditable: $event,
            newValues: [
                'action' => $action,
                'message' => $result->message,
                'payload' => $result->payload,
            ],
            request: $request,
        );

        return $result;
    }
}
