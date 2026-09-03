<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

use Illuminate\Support\Facades\Log;

/**
 * Observability for Stage 7 knowledge base — no secrets or image bytes.
 */
class DiagnosisKnowledgeObservabilityLogger
{
    /** @param  array<string, mixed>  $context */
    public function info(string $event, array $context = []): void
    {
        Log::info('plant_diagnosis_kb.'.$event, $this->sanitize($context));
    }

    /** @param  array<string, mixed>  $context */
    public function warning(string $event, array $context = []): void
    {
        Log::warning('plant_diagnosis_kb.'.$event, $this->sanitize($context));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        $blocked = ['image_binary', 'image_base64', 'binary', 'api_key', 'token', 'password', 'authorization'];
        foreach ($blocked as $key) {
            unset($context[$key]);
        }

        array_walk_recursive($context, static function (mixed &$value): void {
            if (is_string($value) && preg_match('/(sk-|api_key=|token=)/i', $value) === 1) {
                $value = '[redacted]';
            }
        });

        return $context;
    }
}
