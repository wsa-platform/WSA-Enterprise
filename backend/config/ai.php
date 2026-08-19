<?php

return [
    /*
    | Environment keys (names only; never commit secrets):
    | AI_PROVIDER, AI_MODEL, AI_TIMEOUT, AI_FALLBACK_PROVIDER
    | Existing keys: AI_QUEUE, AI_QUEUE_TRIES, AI_ASYNC_DISPATCH,
    | AI_RATE_LIMIT_PER_MINUTE, AI_QUOTA_ENABLED, AI_QUOTA_REQUESTS_PER_PERIOD, AI_QUOTA_PERIOD
    */
    'provider' => env('AI_PROVIDER', 'mock'),
    'model' => env('AI_MODEL', 'mock-v1'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'mock'),
    'implemented_providers' => ['mock'],
    'models' => [
        'mock' => env('AI_MODEL', 'mock-v1'),
    ],
    'domains' => [
        'agriculture',
        'marketplace',
        'user',
        'jobs',
        'training',
        'business',
        'platform',
        'beekeeping',
        'marketing',
    ],
    'max_input_characters' => 8000,
    'queue' => env('AI_QUEUE', 'default'),
    'queue_tries' => (int) env('AI_QUEUE_TRIES', 3),
    'async_dispatch' => filter_var(env('AI_ASYNC_DISPATCH', false), FILTER_VALIDATE_BOOL),
    'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 30),
    'quota_enabled' => filter_var(env('AI_QUOTA_ENABLED', false), FILTER_VALIDATE_BOOL),
    'quota_requests_per_period' => (int) env('AI_QUOTA_REQUESTS_PER_PERIOD', 1000),
    'quota_period' => env('AI_QUOTA_PERIOD', 'monthly'),
];
