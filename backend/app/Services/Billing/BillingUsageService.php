<?php

namespace App\Services\Billing;

use App\Models\UsageRecord;
use App\Services\Ai\AiUsageRecorder;

class BillingUsageService
{
    public function __construct(
        private AiUsageRecorder $aiUsageRecorder,
        private EntitlementService $entitlementService,
    ) {}

    /** @return array<string, mixed> */
    public function summaryForOrganization(int $organizationId): array
    {
        $periodStart = $this->aiUsageRecorder->currentPeriodStart();
        $aiUsed = $this->aiUsageRecorder->countRequestsForPeriod($organizationId);
        $aiLimit = $this->entitlementService->billingEnabled()
            ? $this->entitlementService->getLimit($organizationId, 'ai.requests')
            : null;

        if ($aiLimit === null && config('ai.quota_enabled', false)) {
            $aiLimit = max(1, (int) config('ai.quota_requests_per_period', 1000));
        }

        $remaining = $aiLimit === null ? null : max(0, $aiLimit - $aiUsed);
        $usagePercent = ($aiLimit !== null && $aiLimit > 0)
            ? round(($aiUsed / $aiLimit) * 100, 2)
            : null;

        $history = UsageRecord::withoutGlobalScopes()
            ->selectRaw('period_start, SUM(quantity) as total')
            ->where('organization_id', $organizationId)
            ->where('metric', 'ai.requests')
            ->groupBy('period_start')
            ->orderByDesc('period_start')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'period_start' => (string) $row->period_start,
                'quantity' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'period_start' => $periodStart,
            'metrics' => [
                'ai.requests' => [
                    'used' => $aiUsed,
                    'limit' => $aiLimit,
                    'remaining' => $remaining,
                    'usage_percent' => $usagePercent,
                ],
            ],
            'history' => $history,
        ];
    }
}
