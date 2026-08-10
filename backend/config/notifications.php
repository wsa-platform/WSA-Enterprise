<?php

return [
    'enabled' => env('NOTIFICATIONS_ENABLED', true),
    'queue' => env('NOTIFICATIONS_QUEUE', 'default'),
    'channels' => [
        'in_app' => true,
        'email' => env('NOTIFICATIONS_EMAIL_ENABLED', false),
    ],
];
