<?php

return [
    'contact_exchange' => [
        'amount' => (float) env('JOBS_CONTACT_EXCHANGE_AMOUNT', 49.00),
        'currency' => env('JOBS_CONTACT_EXCHANGE_CURRENCY', 'USD'),
        'payment_provider' => env('JOBS_PAYMENT_PROVIDER', 'mock'),
        'force_fail' => (bool) env('JOBS_PAYMENT_FORCE_FAIL', false),
    ],

    'employment_statuses' => [
        'available',
        'selected',
        'payment_pending',
        'paid',
        'contact_exchanged',
        'hired',
    ],

    'contact_request_statuses' => [
        'pending',
        'payment_pending',
        'paid',
        'contact_exchanged',
        'cancelled',
        'failed',
    ],
];
