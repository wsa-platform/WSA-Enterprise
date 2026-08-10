<?php

namespace App\Services\Ai;

use App\Exceptions\AiQuotaExceededException;
use App\Services\Audit\AuditService;

class AiQuotaService
{
    public function __construct(
        private AiUsageRecorder $usageRecorder,
        private AuditService $auditService,
    ) {}

    public function assertCanDispatch(int $organizationId, ?int $userId = null): void
    {
        if (! config('ai.quota_enabled', false)) {
            return;
        }

        $limit = max(1, (int) config('ai.quota_requests_per_period', 1000));
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
                ],
            );

            throw new AiQuotaExceededException($organizationId, $limit, $used);
        }
    }

    /** @return array{enabled: bool, limit: int|null, used: int, remaining: int|null, period_start: string} */
    public function summaryForOrganization(int $organizationId): array
    {
        $enabled = (bool) config('ai.quota_enabled', false);
        $used = $this->usageRecorder->countRequestsForPeriod($organizationId);
        $periodStart = $this->usageRecorder->currentPeriodStart();

        if (! $enabled) {
            return [
                'enabled' => false,
                'limit' => null,
                'used' => $used,
                'remaining' => null,
                'period_start' => $periodStart,
            ];
        }

        $limit = max(1, (int) config('ai.quota_requests_per_period', 1000));

        return [
            'enabled' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'period_start' => $periodStart,
        ];
    }
}
