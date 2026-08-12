<?php

return [
    'channels' => ['sms', 'email', 'whatsapp'],

    'campaign_statuses' => [
        'draft',
        'scheduled',
        'processing',
        'completed',
        'partially_failed',
        'failed',
        'cancelled',
    ],

    'delivery_statuses' => [
        'queued',
        'sent',
        'delivered',
        'failed',
        'rejected',
        'cancelled',
    ],

    'providers' => [
        'sms' => env('MARKETING_SMS_PROVIDER', 'mock'),
        'email' => env('MARKETING_EMAIL_PROVIDER', 'mock'),
        'whatsapp' => env('MARKETING_WHATSAPP_PROVIDER', 'mock'),
    ],

    'test_send_recipient' => env('MARKETING_TEST_RECIPIENT', 'test@wsa.test'),
];
