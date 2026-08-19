<?php

namespace App\Services\Ai;

use App\Models\AiRequest;
use App\Models\AiUsageRecord;
use App\Models\UsageRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiUsageRecorder
{
    /** @var list<string> */
    private const TERMINAL_STATUSES = ['completed', 'failed', 'cancelled'];

    /** @var list<string> */
    private const ERROR_CATEGORIES = [
        'timeout',
        'authentication',
        'rate_limit',
        'malformed',
        'invalid_request',
        'unavailable',
        'provider_failure',
        'cancelled',
    ];

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

    public function recordOutcome(AiRequest $request, ?string $errorCategory = null, ?string $model = null): void
    {
        try {
            $this->persistOutcome($request, $errorCategory, $model);
        } catch (\Throwable $exception) {
            Log::warning('AI usage persistence failed', [
                'ai_request_id' => $request->id,
                'organization_id' => $request->organization_id,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
        }
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

    private function persistOutcome(AiRequest $request, ?string $errorCategory = null, ?string $model = null): void
    {
        if (! in_array($request->status, self::TERMINAL_STATUSES, true)) {
            return;
        }

        $output = is_array($request->output) ? $request->output : [];
        $category = $this->sanitizeCategory($errorCategory ?? $this->inferCategory($request));

        DB::transaction(function () use ($request, $output, $category, $model): void {
            AiUsageRecord::withoutGlobalScopes()->updateOrCreate(
                ['ai_request_id' => $request->id],
                [
                    'organization_id' => $request->organization_id,
                    'user_id' => $request->user_id,
                    'provider' => (string) ($request->provider ?: 'unknown'),
                    'model' => $this->nullableString($output['model'] ?? $model),
                    'provider_request_id' => $this->nullableString($output['request_id'] ?? null),
                    'tokens_used' => $request->tokens_used,
                    'latency_ms' => $request->latency_ms,
                    'status' => $request->status,
                    'error_category' => $request->status === 'completed' ? null : $category,
                ],
            );
        });
    }

    private function inferCategory(AiRequest $request): ?string
    {
        if ($request->status === 'cancelled') {
            return 'cancelled';
        }

        if ($request->status !== 'failed') {
            return null;
        }

        $message = (string) $request->error_message;

        return match (true) {
            str_contains($message, 'timed out') => 'timeout',
            str_contains($message, 'authenticate') => 'authentication',
            str_contains($message, 'rate limit') => 'rate_limit',
            str_contains($message, 'malformed') => 'malformed',
            str_contains($message, 'rejected') => 'invalid_request',
            default => 'provider_failure',
        };
    }

    private function sanitizeCategory(?string $category): ?string
    {
        if ($category === null || $category === '') {
            return null;
        }

        return in_array($category, self::ERROR_CATEGORIES, true) ? $category : 'provider_failure';
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
