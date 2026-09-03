<?php

namespace App\Support;

/**
 * Bounded outbound HTTP timeout for scholarly adapters (OpenAlex, Crossref).
 */
final class ScientificHttp
{
    public static function timeoutSeconds(): int
    {
        return max(1, min(60, (int) config('wsa.scientific_http_timeout', 15)));
    }
}
