<?php

return [
    'public_organization_slug' => env('WSA_PUBLIC_ORG_SLUG', 'wsa-demo'),
    'openalex_mailto' => env('OPENALEX_MAILTO', 'wsa-platform@example.com'),
    'research_agent' => [
        'enabled' => filter_var(env('WSA_RESEARCH_AGENT_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],
    'plant_diagnosis' => [
        'enabled' => filter_var(env('WSA_PLANT_DIAGNOSIS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'max_image_bytes' => (int) env('WSA_PLANT_DIAGNOSIS_MAX_IMAGE_BYTES', 5242880),
        'knowledge_base' => [
            'enabled' => filter_var(env('WSA_PLANT_DIAGNOSIS_KB_ENABLED', true), FILTER_VALIDATE_BOOL),
            'seed_on_boot' => filter_var(env('WSA_PLANT_DIAGNOSIS_KB_SEED', true), FILTER_VALIDATE_BOOL),
        ],
    ],
];
