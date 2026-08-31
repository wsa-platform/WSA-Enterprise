<?php

return [
    'status_label_disconnected' => 'غير متصل',
    'status_label_unconfigured' => 'غير مُهيأ',
    'status_label_connected' => 'متصل',

    'keys' => [
        'email',
        'sms',
        'whatsapp',
        'meta',
        'google_oauth',
        'facebook_oauth',
        'ai',
    ],

    'email' => [
        'driver' => env('MARKETING_EMAIL_PROVIDER', 'none'),
        'resend_key' => env('RESEND_API_KEY'),
        'postmark_key' => env('POSTMARK_API_KEY'),
    ],

    'sms' => [
        'driver' => env('MARKETING_SMS_PROVIDER', 'none'),
        'twilio_sid' => env('TWILIO_ACCOUNT_SID'),
        'twilio_token' => env('TWILIO_AUTH_TOKEN'),
        'twilio_from' => env('TWILIO_FROM_NUMBER'),
    ],

    'whatsapp' => [
        'driver' => env('MARKETING_WHATSAPP_PROVIDER', 'none'),
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'otp' => [
        'length' => (int) env('OTP_CODE_LENGTH', 6),
        'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 10),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    ],

    'welcome' => [
        'queue' => env('WELCOME_QUEUE', 'default'),
    ],
];
