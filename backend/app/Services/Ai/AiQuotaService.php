<?php

namespace App\Services\Ai;

use App\Exceptions\AiQuotaExceededException;
use App\Services\Audit\AuditService;
use App\Services\Billing\EntitlementService;

class AiQuotaService
{
    public function __construct(
        private AiUsageRecorder $usageRecorder,
        private AuditService $auditService,
        private EntitlementService $entitlementService,
    ) {}

    public function assertCanDispatch(int $organizationId, ?int $userId = null): void
    {
        $this->entitlementService->assertSubscriptionActive($organizationId, $userId);
        $this->entitlementService->assertFeatureAllowed($organizationId, 'ai.use', $userId);

        $limit = $this->effectiveLimit($organizationId);
        if ($limit === null) {
            return;
        }

        $used = $this->usageRecorder->countRequestsForPeriod($organizationId);

        if ($used >= $limit) {
            $this->auditService->record(
                action: 'ai.quota.exceeded',
                organizationId: $organizationId,
                userId: $userId,
                newValues: [
                    'limit' => $limit,
                    'used' => $used,
                    'period_start' => $this->usageRecorder->currentPeriodStart(),
                    'source' => $this->entitlementService->billingEnabled() ? 'billing' : 'config',
                ],
            );

            throw new AiQuotaExceededException($organizationId, $limit, $used);
        }
    }

    /** @return array{enabled: bool, limit: int|null, used: int, remaining: int|null, period_start: string, source: string|null} */
    public function summaryForOrganization(int $organizationId): array
    {
        $used = $this->usageRecorder->countRequestsForPeriod($organizationId);
        $periodStart = $this->usageRecorder->currentPeriodStart();
        $limit = $this->effectiveLimit($organizationId);

        if ($limit === null) {
            return [
                'enabled' => false,
                'limit' => null,
                'used' => $used,
                'remaining' => null,
                'period_start' => $periodStart,
                'source' => null,
            ];
        }

        return [
            'enabled' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'period_start' => $periodStart,
            'source' => $this->entitlementService->billingEnabled() ? 'billing' : 'config',
        ];
    }

    private function effectiveLimit(int $organizationId): ?int
    {
        if ($this->entitlementService->billingEnabled()) {
            return $this->entitlementService->getLimit($organizationId, 'ai.requests');
        }

        if (! config('ai.quota_enabled', false)) {
            return null;
        }

        return max(1, (int) config('ai.quota_requests_per_period', 1000));
    }
}
