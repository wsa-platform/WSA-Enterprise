<?php

return [
    'contact_access' => [
        'default_price' => (float) env('MARKETPLACE_CONTACT_PRICE', 49.00),
        'currency' => env('MARKETPLACE_CONTACT_CURRENCY', 'SAR'),
        'payment_provider' => env('MARKETPLACE_PAYMENT_PROVIDER', 'mock'),
        'force_fail' => (bool) env('MARKETPLACE_PAYMENT_FORCE_FAIL', false),
    ],
];
