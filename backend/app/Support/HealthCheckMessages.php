<?php

namespace App\Support;

final class HealthCheckMessages
{
    public static function forFailure(string $component, ?\Throwable $exception = null): string
    {
        if (config('app.debug') && $exception !== null) {
            return $exception->getMessage();
        }

        return match ($component) {
            'database' => 'Database connection failed.',
            'cache' => 'Cache probe failed.',
            'queue' => 'Queue connection failed.',
            'storage' => 'Storage probe failed.',
            'scheduler' => 'Scheduler heartbeat check failed.',
            'authentication' => 'Authentication subsystem check failed.',
            default => 'Health check failed.',
        };
    }

    public static function sanitize(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            return 'Health check failed.';
        }

        if (str_contains($message, 'SQLSTATE')
            || str_contains($message, 'password')
            || str_contains($message, 'Connection refused')
            || str_contains($message, '/var/')
            || str_contains($message, 'C:\\')) {
            return 'Health check failed.';
        }

        return mb_strlen($message) > 200 ? mb_substr($message, 0, 200).'…' : $message;
    }
}
