<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Production Admin Bootstrap
    |--------------------------------------------------------------------------
    |
    | Creates or updates a single platform administrator on deploy when
    | ADMIN_PASSWORD is supplied via environment variables. Never commit
    | credentials to source control.
    |
    */

    'admin' => [
        'enabled' => filter_var(env('ADMIN_BOOTSTRAP_ENABLED', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),
        'email' => env('ADMIN_EMAIL', 'admin@wsa.test'),
        'password' => env('ADMIN_PASSWORD'),
        'name' => env('ADMIN_NAME', 'Platform Administrator'),
        'organization_name' => env('ADMIN_ORGANIZATION_NAME', 'WSA Enterprise'),
        'organization_slug' => env('ADMIN_ORGANIZATION_SLUG', 'wsa-enterprise'),
        'minimum_password_length' => (int) env('ADMIN_PASSWORD_MIN_LENGTH', 12),
    ],

];
