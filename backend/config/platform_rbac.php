<?php

return [
    /*
    | Platform RBAC catalog — independent from config/permissions.php (Organization).
    */
    'permissions' => [
        'platform.access',
        'platform.organizations.view',
        'platform.organizations.manage',
        'platform.organizations.members',
        'platform.users.view',
        'platform.users.manage',
        'platform.roles.manage',
        'platform.settings.view',
        'platform.settings.manage',
        'platform.audit.view',
        'platform.jobs.view',
        'platform.jobs.manage',
        'platform.marketplace.view',
        'platform.marketplace.manage',
        'platform.library.view',
        'platform.library.manage',
        'platform.reports.view',
        'platform.monitoring.view',
    ],

    'roles' => [
        'platform_administrator' => [
            'name' => 'Platform Administrator',
            'description' => 'Full Platform Administrator role.',
            'permissions' => '*',
        ],
        'platform_auditor' => [
            'name' => 'Platform Auditor',
            'description' => 'Read-only platform audit and catalog access.',
            'permissions' => [
                'platform.access',
                'platform.organizations.view',
                'platform.users.view',
                'platform.audit.view',
                'platform.jobs.view',
                'platform.marketplace.view',
                'platform.library.view',
                'platform.reports.view',
                'platform.monitoring.view',
            ],
        ],
    ],
];
