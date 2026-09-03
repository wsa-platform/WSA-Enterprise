<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Production-safe API errors: JSON only, no exception traces or filesystem paths.
 */
final class ProductionSafeApiExceptionRenderer
{
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

        return response()->json([
            'message' => 'Server error.',
        ], 500);
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
