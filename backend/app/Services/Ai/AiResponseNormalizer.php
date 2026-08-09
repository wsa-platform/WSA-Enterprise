<?php

namespace App\Services\Ai;

class AiResponseNormalizer
{
    /** @param  array<string, mixed>  $output */
    public function normalize(string $requestType, array $output): array
    {
        return match ($requestType) {
            'diagnosis' => $this->normalizeDiagnosis($output),
            'library_summary', 'library_qa' => $this->normalizeLibrary($output),
            'training_assistance' => $this->normalizeTraining($output),
            default => [
                'request_type' => $requestType,
                'content' => $output,
                'is_decision_support' => true,
            ],
        };
    }

    /** @param  array<string, mixed>  $output */
    private function normalizeDiagnosis(array $output): array
    {
        return [
            'request_type' => 'diagnosis',
            'title' => (string) ($output['title'] ?? 'Decision support result'),
            'summary' => (string) ($output['summary'] ?? ''),
            'confidence_score' => isset($output['confidence_score']) ? (float) $output['confidence_score'] : null,
            'severity' => $output['severity'] ?? null,
            'priority' => $output['priority'] ?? null,
            'recommendations' => $output['recommendations'] ?? [],
            'is_decision_support' => true,
        ];
    }

    /** @param  array<string, mixed>  $output */
    private function normalizeLibrary(array $output): array
    {
        return [
            'request_type' => 'library',
            'summary' => (string) ($output['summary'] ?? $output['answer'] ?? ''),
            'sources' => $output['sources'] ?? [],
            'is_decision_support' => true,
        ];
    }

    /** @param  array<string, mixed>  $output */
    private function normalizeTraining(array $output): array
    {
        return [
            'request_type' => 'training_assistance',
            'guidance' => (string) ($output['guidance'] ?? $output['summary'] ?? ''),
            'suggestions' => $output['suggestions'] ?? [],
            'is_decision_support' => true,
        ];
    }
}
