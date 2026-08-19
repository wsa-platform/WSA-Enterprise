<?php

namespace App\Services\Ai;

use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiProviderUnavailableException;
use Throwable;

class AiErrorSanitizer
{
    public static function publicMessage(Throwable $exception): string
    {
        if ($exception instanceof AiProviderTimeoutException) {
            return 'The AI provider timed out.';
        }

        if ($exception instanceof AiProviderUnavailableException) {
            return $exception->getMessage();
        }

        return 'The AI provider failed to complete the request.';
    }

    public static function logMessage(Throwable $exception): string
    {
        return self::redact($exception->getMessage());
    }

    public static function redact(?string $value): string
    {
        $text = (string) $value;
        $text = preg_replace('/sk-[A-Za-z0-9_\-]+/', '[redacted]', $text) ?? $text;
        $text = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $text) ?? $text;
        $text = preg_replace('/(api[_-]?key|secret|token|password)\s*[:=]\s*\S+/i', '$1=[redacted]', $text) ?? $text;

        return $text;
    }
}
