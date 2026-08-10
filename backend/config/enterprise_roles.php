<?php

return [
    /*
    | Enterprise role slugs mapped to permission names from config/permissions.php.
    | '*' grants all catalog permissions for the organization.
    */
    'owner' => ['*'],

    'admin' => ['*'],

    'manager' => [
        'platform.view',
        'farm.view', 'farm.manage',
        'crop.view', 'crop.manage',
        'soil.view', 'soil.manage',
        'diagnosis.view', 'diagnosis.manage',
        'training.view', 'training.manage',
        'library.view', 'library.manage',
        'ai.use',
        'business.view', 'business.manage',
    ],

    'member' => [
        'platform.view',
        'farm.view', 'farm.manage',
        'crop.view', 'crop.manage',
        'soil.view', 'soil.manage',
        'diagnosis.view', 'diagnosis.manage',
        'training.view', 'training.manage',
        'library.view', 'library.manage',
        'ai.use',
        'business.view',
    ],

    'viewer' => [
        'platform.view',
        'farm.view',
        'crop.view',
        'soil.view',
        'diagnosis.view',
        'training.view',
        'library.view',
        'business.view',
    ],

    'labels' => [
        'owner' => 'Organization Owner',
        'admin' => 'Organization Admin',
        'manager' => 'Manager',
        'member' => 'Member',
        'viewer' => 'Viewer',
    ],

    'privileged_slugs' => ['owner', 'admin'],
];
