<?php

if (! function_exists('wsa_uses_redis')) {
    function wsa_uses_redis(): bool
    {
        return (bool) (env('REDIS_HOST') || env('REDIS_URL'));
    }
}

if (! function_exists('wsa_default_cache_store')) {
    function wsa_default_cache_store(): string
    {
        if ($store = env('CACHE_STORE')) {
            return $store;
        }

        return match (env('APP_ENV')) {
            'testing' => 'array',
            'production' => wsa_uses_redis() ? 'redis' : 'file',
            default => wsa_uses_redis() ? 'redis' : 'database',
        };
    }
}

if (! function_exists('wsa_default_session_driver')) {
    function wsa_default_session_driver(): string
    {
        if ($driver = env('SESSION_DRIVER')) {
            return $driver;
        }

        return match (env('APP_ENV')) {
            'testing' => 'array',
            'production' => wsa_uses_redis() ? 'redis' : 'file',
            default => wsa_uses_redis() ? 'redis' : 'database',
        };
    }
}

if (! function_exists('wsa_default_queue_connection')) {
    function wsa_default_queue_connection(): string
    {
        if ($connection = env('QUEUE_CONNECTION')) {
            return $connection;
        }

        return match (env('APP_ENV')) {
            'testing' => 'sync',
            'production' => wsa_uses_redis() ? 'redis' : 'database',
            default => wsa_uses_redis() ? 'redis' : 'database',
        };
    }
}
