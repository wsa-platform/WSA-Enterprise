<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use Illuminate\Support\Facades\Log;

/**
 * Sanitized FAOSTAT diagnostics. Never logs credentials, tokens, or Authorization values.
 */
final class FaoStatOperationalLogger
{
    /** @var list<string> */
    private const ALLOWED_KEYS = [
        'provider',
        'domain',
        'operation',
        'success',
        'error_category',
        'latency_ms',
        'result_count',
        'http_status',
        'retry_count',
        'circuit_open',
        'readiness',
        'activation_state',
        'item',
        'area',
        'element',
        'year',
        'domain',
        'query_element_code',
        'response_element_code',
        'evidence_count',
        'considered',
        'selected',
    ];

    /**
     * @param  array<string, mixed>  $context
     */
    public function event(string $message, array $context, string $level = 'info'): void
    {
        $safe = $this->sanitize($context);
        if ($level === 'warning') {
            Log::warning($message, $safe);

            return;
        }
        Log::info($message, $safe);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function sanitize(array $context): array
    {
        $out = ['provider' => 'fao_stat'];
        foreach (self::ALLOWED_KEYS as $key) {
            if (array_key_exists($key, $context)) {
                $out[$key] = $context[$key];
            }
        }

        return $out;
    }
}
