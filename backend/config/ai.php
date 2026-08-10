<?php

return [
    'provider' => env('AI_PROVIDER', 'mock'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'queue' => env('AI_QUEUE', 'default'),
    'queue_tries' => (int) env('AI_QUEUE_TRIES', 3),
];
