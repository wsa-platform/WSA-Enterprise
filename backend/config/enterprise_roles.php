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
        'services.supervise',
        'monitoring.view',
        'billing.view',
        'farm.view', 'farm.manage',
        'crop.view', 'crop.manage',
        'soil.view', 'soil.manage',
        'diagnosis.view', 'diagnosis.manage',
        'training.view', 'training.manage',
        'library.view', 'library.manage',
        'ai.use',
        'ai.assistant',
        'ai.vision',
        'ai.actions.execute',
        'marketing.view',
        'marketing.manage',
        'business.view', 'business.manage',
        'jobs.view', 'jobs.manage',
        'beekeeping.view', 'beekeeping.manage',
    ],

    'member' => [
        'platform.view',
        'billing.view',
        'farm.view', 'farm.manage',
        'crop.view', 'crop.manage',
        'soil.view', 'soil.manage',
        'diagnosis.view', 'diagnosis.manage',
        'training.view', 'training.manage',
        'library.view', 'library.manage',
        'ai.use',
        'ai.assistant',
        'marketing.view',
        'jobs.talent.register',
        'jobs.talent.manage',
        'beekeeping.view',
        'business.view', 'business.manage',
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
        'jobs.view',
        'beekeeping.view',
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
