<?php

return [
    'enabled' => filter_var(env('BILLING_ENABLED', false), FILTER_VALIDATE_BOOL),
    'provider' => env('BILLING_PROVIDER', 'mock'),
    'default_plan_slug' => env('DEFAULT_PLAN_SLUG', 'free'),
    'currency' => env('BILLING_CURRENCY', 'USD'),
];
