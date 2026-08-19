<?php

namespace App\Services\Ai;

class AiResponseNormalizer
{
    /** @param  array<string, mixed>  $output */
    public function normalize(string $requestType, array $output, ?string $provider = null, ?string $model = null): array
    {
        $normalized = match ($requestType) {
            'diagnosis' => $this->normalizeDiagnosis($output),
            'library_summary', 'library_qa' => $this->normalizeLibrary($output),
            'training_assistance' => $this->normalizeTraining($output),
            'assistant' => $this->normalizeAssistant($output),
            default => [
                'request_type' => $requestType,
                'content' => $output,
                'is_decision_support' => true,
            ],
        };

        return array_merge($normalized, [
            'provider' => $provider ?? ($output['provider'] ?? null),
            'model' => $model ?? ($output['model'] ?? null),
            'tokens_used' => isset($output['tokens_used']) ? (int) $output['tokens_used'] : null,
            'finish_reason' => $output['finish_reason'] ?? 'stop',
            'sources' => $normalized['sources'] ?? $output['sources'] ?? [],
        ]);
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

    /** @param  array<string, mixed>  $output */
    private function normalizeAssistant(array $output): array
    {
        return [
            'request_type' => 'assistant',
            'reply' => (string) ($output['reply'] ?? $output['summary'] ?? ''),
            'confidence' => $output['confidence'] ?? null,
            'domain' => $output['domain'] ?? null,
            'requires_more_information' => (bool) ($output['requires_more_information'] ?? false),
            'sources' => $output['sources'] ?? [],
            'is_decision_support' => true,
        ];
    }
}
