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

if (! function_exists('wsa_cors_allowed_origins')) {
    /**
     * @return list<string>
     */
    function wsa_cors_allowed_origins(): array
    {
        $frontendDefault = env('APP_ENV') === 'production' ? '' : 'http://localhost:5173';
        $configured = array_values(array_unique(array_filter(array_map(
            trim(...),
            array_merge(
                explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
                explode(',', (string) env('FRONTEND_URL', $frontendDefault)),
            ),
        ))));

        if (env('APP_ENV') === 'production') {
            return $configured;
        }

        $localDevelopment = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:8079',
            'http://127.0.0.1:8079',
            'http://localhost:8080',
            'http://127.0.0.1:8080',
            'http://localhost:8081',
            'http://127.0.0.1:8081',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:5496',
            'http://127.0.0.1:5496',
        ];

        return array_values(array_unique(array_merge($configured, $localDevelopment)));
    }
}
