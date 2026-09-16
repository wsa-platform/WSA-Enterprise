<?php

namespace App\Support;

use Illuminate\Http\Client\Response;

/**
 * Bounded outbound HTTP helpers for scholarly adapters (OpenAlex, Crossref, Semantic Scholar).
 */
final class ScientificHttp
{
    public const MAX_RATE_LIMIT_ATTEMPTS = 3;

    public static function timeoutSeconds(?float $remainingSeconds = null): int
    {
        $configured = max(1, min(60, (int) config('wsa.scientific_http_timeout', 15)));
        if ($remainingSeconds === null) {
            return $configured;
        }
        if ($remainingSeconds < 1.0) {
            return 1;
        }

        return max(1, min($configured, (int) floor($remainingSeconds)));
    }

    /**
     * Uncapped Retry-After (seconds). Null when the header is absent or unusable.
     */
    public static function retryAfterHeaderSeconds(Response $response): ?float
    {
        $raw = $response->header('Retry-After');
        if (is_array($raw)) {
            $raw = $raw[0] ?? '';
        }
        $retryAfter = trim((string) $raw);
        if ($retryAfter === '') {
            return null;
        }
        if (ctype_digit($retryAfter)) {
            return (float) $retryAfter;
        }
        $when = strtotime($retryAfter);
        if ($when === false) {
            return null;
        }

        return max(0.0, (float) ($when - time()));
    }

    /**
     * Whether another 429 retry can complete inside the remaining search budget.
     * A provider Retry-After larger than remaining time is fail-fast, not an 8s sleep loop.
     */
    public static function canAffordRetry(
        ?float $remainingSeconds,
        Response $response,
        int $attempt,
        int $httpTimeoutSeconds,
    ): bool {
        if ($remainingSeconds === null) {
            return true;
        }
        if ($remainingSeconds < 1.0) {
            return false;
        }

        $retryAfter = self::retryAfterHeaderSeconds($response);
        if ($retryAfter !== null && $retryAfter > $remainingSeconds) {
            return false;
        }

        $delay = self::retryDelaySeconds($response, $attempt);
        $needed = $delay + max(1, $httpTimeoutSeconds);

        return $needed <= $remainingSeconds;
    }

    /**
     * Sleep for Retry-After when present, otherwise bounded exponential backoff.
     * In testing, sleeps are skipped so Http::fake suites stay fast.
     * $maxSleepSeconds caps the wait so retries cannot overrun the search budget.
     */
    public static function sleepForRetryAfterOrBackoff(
        Response $response,
        int $attempt,
        ?float $maxSleepSeconds = null,
    ): void {
        if (app()->environment('testing')) {
            return;
        }

        $seconds = self::retryDelaySeconds($response, $attempt);
        if ($maxSleepSeconds !== null) {
            $seconds = min($seconds, max(0.0, $maxSleepSeconds));
        }
        if ($seconds <= 0) {
            return;
        }

        usleep((int) round(min($seconds, 8.0) * 1_000_000));
    }

    public static function retryDelaySeconds(Response $response, int $attempt): float
    {
        $retryAfter = self::retryAfterHeaderSeconds($response);
        if ($retryAfter !== null) {
            return min(8.0, $retryAfter);
        }

        // Bounded exponential: 0.5, 1.0, 2.0 … capped at 8s.
        return min(8.0, (2 ** max(0, $attempt - 1)) * 0.5);
    }
}
