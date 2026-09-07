<?php

namespace App\Support;

use Illuminate\Http\Client\Response;

/**
 * Bounded outbound HTTP helpers for scholarly adapters (OpenAlex, Crossref, Semantic Scholar).
 */
final class ScientificHttp
{
    public const MAX_RATE_LIMIT_ATTEMPTS = 3;

    public static function timeoutSeconds(): int
    {
        return max(1, min(60, (int) config('wsa.scientific_http_timeout', 15)));
    }

    /**
     * Sleep for Retry-After when present, otherwise bounded exponential backoff.
     * In testing, sleeps are skipped so Http::fake suites stay fast.
     */
    public static function sleepForRetryAfterOrBackoff(Response $response, int $attempt): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $seconds = self::retryDelaySeconds($response, $attempt);
        if ($seconds <= 0) {
            return;
        }

        usleep((int) round(min($seconds, 8.0) * 1_000_000));
    }

    public static function retryDelaySeconds(Response $response, int $attempt): float
    {
        $raw = $response->header('Retry-After');
        if (is_array($raw)) {
            $raw = $raw[0] ?? '';
        }
        $retryAfter = trim((string) $raw);
        if ($retryAfter !== '') {
            if (ctype_digit($retryAfter)) {
                return min(8.0, (float) $retryAfter);
            }
            $when = strtotime($retryAfter);
            if ($when !== false) {
                return min(8.0, max(0.0, (float) ($when - time())));
            }
        }

        // Bounded exponential: 0.5, 1.0, 2.0 … capped at 8s.
        return min(8.0, (2 ** max(0, $attempt - 1)) * 0.5);
    }
}
