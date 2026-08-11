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
}
