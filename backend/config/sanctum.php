<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000,localhost:5173')),
    'guard' => ['web'],
    'expiration' => env('SANCTUM_TOKEN_EXPIRATION') ? (int) env('SANCTUM_TOKEN_EXPIRATION') : null,
    // Platform Administrator tokens only (minutes). Does not change organization-user TTL.
    'admin_expiration' => max(1, (int) env('SANCTUM_ADMIN_TOKEN_EXPIRATION', 480)),
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
