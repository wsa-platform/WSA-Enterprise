<?php

namespace App\Services\Agriculture\Diagnosis\Observability;

use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Support\Facades\Log;

/**
 * Structured diagnosis logging without secrets, tokens, or image binaries.
 */
class DiagnosisObservabilityLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $event, array $context = []): void
    {
        Log::info('plant_diagnosis.'.$event, $this->sanitize($context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $event, array $context = []): void
    {
        Log::warning('plant_diagnosis.'.$event, $this->sanitize($context));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function sanitize(array $context): array
    {
        $blocked = [
            'image', 'image_binary', 'imageBinary', 'image_base64', 'imageBase64',
            'binary', 'file', 'token', 'api_key', 'apiKey', 'authorization', 'password', 'secret',
        ];

        $clean = [];
        foreach ($context as $key => $value) {
            if (in_array((string) $key, $blocked, true)) {
                $clean[(string) $key] = '[redacted]';

                continue;
            }

            if (is_string($value)) {
                $clean[(string) $key] = AiErrorSanitizer::redact($value);

                continue;
            }

            if (is_array($value)) {
                $clean[(string) $key] = $this->sanitize($value);

                continue;
            }

            $clean[(string) $key] = $value;
        }

        return $clean;
    }
}
