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

    public function recordRetrievalTelemetry(AiRequest $request, array $telemetry): void
    {
        $safe = $this->sanitizeRetrieval($telemetry);
        if ($safe === null) {
            return;
        }

        try {
            AiUsageRecord::withoutGlobalScopes()
                ->where('ai_request_id', $request->id)
                ->update(['retrieval' => $safe]);
        } catch (\Throwable $exception) {
            Log::warning('AI retrieval telemetry failed', [
                'ai_request_id' => $request->id,
                'organization_id' => $request->organization_id,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $telemetry
     * @return array<string, mixed>|null
     */
    private function sanitizeRetrieval(array $telemetry): ?array
    {
        if ($telemetry === []) {
            return null;
        }

        $status = (string) ($telemetry['retrieval_status'] ?? 'empty');
        if (! in_array($status, ['ok', 'empty', 'failed', 'disabled', 'fallback'], true)) {
            $status = 'empty';
        }

        $sourceTypes = [];
        foreach ($telemetry['source_types'] ?? [] as $type) {
            if (is_string($type) && in_array($type, ['library_items', 'bee_knowledge_topics'], true)) {
                $sourceTypes[$type] = $type;
            }
        }

        $freshness = ['fresh' => 0, 'stale' => 0, 'unknown' => 0];
        if (is_array($telemetry['freshness_distribution'] ?? null)) {
            foreach (array_keys($freshness) as $key) {
                $freshness[$key] = max(0, (int) ($telemetry['freshness_distribution'][$key] ?? 0));
            }
        }

        $strategy = (string) ($telemetry['retrieval_strategy'] ?? 'keyword');
        if (! in_array($strategy, ['keyword', 'semantic', 'hybrid'], true)) {
            $strategy = 'keyword';
        }

        $reason = (string) ($telemetry['fallback_reason'] ?? '');
        if (! in_array($reason, ['semantic_unavailable', 'semantic_error', 'invalid_strategy'], true)) {
            $reason = '';
        }

        $requested = (string) ($telemetry['requested_strategy'] ?? '');
        if (! in_array($requested, ['keyword', 'semantic', 'hybrid'], true)) {
            $requested = '';
        }

        $safe = [
            'candidate_count' => max(0, (int) ($telemetry['candidate_count'] ?? 0)),
            'returned_count' => max(0, (int) ($telemetry['returned_count'] ?? 0)),
            'retrieval_duration_ms' => max(0, (int) ($telemetry['retrieval_duration_ms'] ?? 0)),
            'source_types' => array_values($sourceTypes),
            'freshness_distribution' => $freshness,
            'retrieval_status' => $status,
            'retrieval_strategy' => $strategy,
            'keyword_candidate_count' => max(0, (int) ($telemetry['keyword_candidate_count'] ?? 0)),
            'semantic_candidate_count' => max(0, (int) ($telemetry['semantic_candidate_count'] ?? 0)),
        ];
        if (array_key_exists('hybrid_result_count', $telemetry)) {
            $safe['hybrid_result_count'] = max(0, (int) $telemetry['hybrid_result_count']);
        }
        if ($reason !== '') {
            $safe['fallback_reason'] = $reason;
        }
        if ($requested !== '') {
            $safe['requested_strategy'] = $requested;
        }
        if (isset($telemetry['embedding_duration_ms'])) {
            $safe['embedding_duration_ms'] = max(0, (int) $telemetry['embedding_duration_ms']);
        }
        if (isset($telemetry['vector_search_duration_ms'])) {
            $safe['vector_search_duration_ms'] = max(0, (int) $telemetry['vector_search_duration_ms']);
        }
        $provider = strtolower((string) ($telemetry['embedding_provider'] ?? ''));
        if (in_array($provider, ['mock', 'openai'], true)) {
            $safe['embedding_provider'] = $provider;
        }
        $model = (string) ($telemetry['embedding_model'] ?? '');
        if ($model !== '' && preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $model) === 1) {
            $safe['embedding_model'] = $model;
        }
        if (isset($telemetry['similarity_threshold']) && is_numeric($telemetry['similarity_threshold'])) {
            $safe['similarity_threshold'] = max(0.0, min(1.0, (float) $telemetry['similarity_threshold']));
        }
        if (isset($telemetry['semantic_result_count'])) {
            $safe['semantic_result_count'] = max(0, (int) $telemetry['semantic_result_count']);
        }

        return $safe;
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
