<?php

namespace App\Services\Ai;

use App\Models\UsageRecord;

class AiUsageRecorder
{
    public function recordRequest(int $organizationId, ?int $aiRequestId = null, int $quantity = 1): UsageRecord
    {
        return UsageRecord::create([
            'organization_id' => $organizationId,
            'metric' => 'ai.requests',
            'quantity' => max(1, $quantity),
            'period_start' => $this->currentPeriodStart(),
            'metadata' => $aiRequestId ? ['ai_request_id' => $aiRequestId] : null,
        ]);
    }

    public function countRequestsForPeriod(int $organizationId): int
    {
        return (int) UsageRecord::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('metric', 'ai.requests')
            ->where('period_start', $this->currentPeriodStart())
            ->sum('quantity');
    }

    public function currentPeriodStart(): string
    {
        $period = config('ai.quota_period', 'monthly');

        return match ($period) {
            'daily' => now()->toDateString(),
            default => now()->startOfMonth()->toDateString(),
        };
    }
}
