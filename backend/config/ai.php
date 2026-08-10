<?php

return [
    'provider' => env('AI_PROVIDER', 'mock'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'queue' => env('AI_QUEUE', 'default'),
    'queue_tries' => (int) env('AI_QUEUE_TRIES', 3),
    'async_dispatch' => filter_var(env('AI_ASYNC_DISPATCH', false), FILTER_VALIDATE_BOOL),
    'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 30),
    'quota_enabled' => filter_var(env('AI_QUOTA_ENABLED', false), FILTER_VALIDATE_BOOL),
    'quota_requests_per_period' => (int) env('AI_QUOTA_REQUESTS_PER_PERIOD', 1000),
    'quota_period' => env('AI_QUOTA_PERIOD', 'monthly'),
];
