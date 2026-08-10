<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API client scopes
    |--------------------------------------------------------------------------
    |
    | Scopes assigned to organization API clients map to permission strings
    | checked by controllers via AuthorizesOrganizationAccess.
    |
    */
    'scopes' => [
        'analytics.read' => [
            'permissions' => ['platform.view'],
        ],
        'ai.read' => [
            'permissions' => ['ai.use'],
        ],
        'billing.read' => [
            'permissions' => ['billing.view'],
        ],
    ],

    'last_used_touch_seconds' => 60,
];
