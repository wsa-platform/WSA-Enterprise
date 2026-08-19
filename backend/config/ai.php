<?php

return [
    /*
    | Environment keys (names only; never commit secrets):
    | AI_PROVIDER, AI_MODEL, AI_TIMEOUT, AI_FALLBACK_PROVIDER
    | OPENAI_API_KEY, OPENAI_BASE_URL, OPENAI_MODEL, OPENAI_TIMEOUT, OPENAI_CONNECT_TIMEOUT
    | AI_RETRY_TIMES, AI_RETRY_SLEEP_MS
    | Existing keys: AI_QUEUE, AI_QUEUE_TRIES, AI_ASYNC_DISPATCH,
    | AI_RATE_LIMIT_PER_MINUTE, AI_QUOTA_ENABLED, AI_QUOTA_REQUESTS_PER_PERIOD, AI_QUOTA_PERIOD
    |
    | Mock remains the default provider. OpenAI is opt-in via AI_PROVIDER=openai.
    | AI_FALLBACK_PROVIDER is structural; AI-03 does not silently fail over.
    */
    'provider' => env('AI_PROVIDER', 'mock'),
    'model' => env('AI_MODEL', 'mock-v1'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'mock'),
    'implemented_providers' => ['mock', 'openai'],
    'models' => [
        'mock' => env('AI_MODEL', 'mock-v1'),
        'openai' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 30),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 10),
        'retry_times' => (int) env('AI_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('AI_RETRY_SLEEP_MS', 200),
        'retry_statuses' => [408, 429, 500, 502, 503, 504],
        'allowed_models' => array_values(array_filter(array_map('trim', explode(',', (string) env('OPENAI_ALLOWED_MODELS', ''))))),
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
