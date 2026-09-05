<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Production-safe API errors: JSON only, no exception traces or filesystem paths.
 */
final class ProductionSafeApiExceptionRenderer
{
    private const TEMP_THROWABLE_EVENT = 'WSA_STAGE4_PRODUCTION_THROWABLE';

    private const TEMP_TRACE_MAX_CHARS = 4000;

    public function shouldRenderJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    public function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($this->isDelegatedException($exception)) {
            return null;
        }

        if (config('app.debug') && ! app()->environment('production')) {
            return null;
        }

        // TEMPORARY diagnostic: surface uncaught throwables in Render Application Logs
        // before the opaque production JSON body. Does not alter the HTTP response.
        $this->logTemporaryProductionThrowable($exception, $request);

        return response()->json([
            'message' => 'Server error.',
        ], 500);
    }

    /**
     * TEMPORARY: log exception metadata to stderr (+ default channel) for production diagnosis.
     * Never throws. Does not log request payloads, query text, secrets, or auth headers.
     */
    private function logTemporaryProductionThrowable(Throwable $exception, Request $request): void
    {
        try {
            $requestId = $request->attributes->get('request_id');
            if (! is_string($requestId) || $requestId === '') {
                $headerId = $request->header('X-Request-Id');
                $requestId = is_string($headerId) && $headerId !== '' ? $headerId : null;
            }

            $trace = $exception->getTraceAsString();
            if (strlen($trace) > self::TEMP_TRACE_MAX_CHARS) {
                $trace = substr($trace, 0, self::TEMP_TRACE_MAX_CHARS).'...[truncated]';
            }

            $context = [
                'event' => self::TEMP_THROWABLE_EVENT,
                'request_id' => $requestId,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
                'exception_trace' => $trace,
            ];

            try {
                Log::channel('stderr')->error(self::TEMP_THROWABLE_EVENT, $context);
            } catch (Throwable) {
                // stderr channel must not break opaque rendering
            }

            try {
                Log::error(self::TEMP_THROWABLE_EVENT, $context);
            } catch (Throwable) {
                // default channel must not break opaque rendering
            }
        } catch (Throwable) {
            // Diagnostic logging must never affect the HTTP response path
        }
    }

    /**
     * @param  array<string, mixed>|string  $payload
     */
    public static function payloadLeaksDebug(array|string $payload): bool
    {
        $raw = strtolower(is_string($payload) ? $payload : (string) json_encode($payload));

        foreach ([
            'stack trace',
            '"exception"',
            '"trace"',
            'vendor/laravel',
            '/var/www/html',
            'g:\\wsa-enterprise',
            'app_key',
            'db_password',
            'remember_token',
        ] as $needle) {
            if (str_contains($raw, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isDelegatedException(Throwable $exception): bool
    {
        return $exception instanceof ValidationException
            || $exception instanceof HttpExceptionInterface
            || $exception instanceof AuthenticationException
            || $exception instanceof AuthorizationException
            || $exception instanceof ModelNotFoundException;
    }
}
