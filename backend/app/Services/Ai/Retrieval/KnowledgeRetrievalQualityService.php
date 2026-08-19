<?php

namespace App\Services\Ai\Retrieval;

use App\Models\AiUsageRecord;

class KnowledgeRetrievalQualityService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(int $organizationId): array
    {
        $strategies = ['keyword' => 0, 'semantic' => 0, 'hybrid' => 0];
        $sourceTypes = ['bee_knowledge_topics' => 0, 'library_items' => 0];
        $fallback = 0;
        $empty = 0;
        $failed = 0;
        $ok = 0;
        $returnedTotal = 0;
        $returnedSamples = 0;

        $records = AiUsageRecord::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNotNull('retrieval')
            ->get(['retrieval']);

        foreach ($records as $record) {
            $telemetry = is_array($record->retrieval) ? $record->retrieval : [];
            $status = (string) ($telemetry['retrieval_status'] ?? '');
            match ($status) {
                'ok' => $ok++,
                'empty' => $empty++,
                'failed' => $failed++,
                'fallback' => $fallback++,
                default => null,
            };

            $strategy = (string) ($telemetry['retrieval_strategy'] ?? 'keyword');
            if (isset($strategies[$strategy])) {
                $strategies[$strategy]++;
            }

            if (array_key_exists('returned_count', $telemetry)) {
                $returnedTotal += max(0, (int) $telemetry['returned_count']);
                $returnedSamples++;
            }

            foreach ($telemetry['source_types'] ?? [] as $type) {
                if (is_string($type) && isset($sourceTypes[$type])) {
                    $sourceTypes[$type]++;
                }
            }
        }

        ksort($strategies);
        ksort($sourceTypes);

        return [
            'organization_id' => $organizationId,
            'keyword_results' => $strategies['keyword'],
            'semantic_results' => $strategies['semantic'],
            'hybrid_results' => $strategies['hybrid'],
            'fallback_count' => $fallback,
            'zero_result_count' => $empty,
            'error_count' => $failed,
            'success_count' => $ok,
            'average_returned_count' => $returnedSamples === 0 ? 0.0 : round($returnedTotal / $returnedSamples, 4),
            'strategy_distribution' => $strategies,
            'source_type_distribution' => $sourceTypes,
        ];
    }
}
