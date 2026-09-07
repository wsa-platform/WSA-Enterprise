<?php

return [
    'public_organization_slug' => env('WSA_PUBLIC_ORG_SLUG', 'wsa-demo'),
    'openalex_mailto' => env('OPENALEX_MAILTO', 'wsa-platform@example.com'),
    // Optional OpenAlex API key (query param). Empty → polite pool via mailto only.
    'openalex_api_key' => env('OPENALEX_API_KEY'),
    'scientific_http_timeout' => max(1, min(60, (int) env('SCIENTIFIC_HTTP_TIMEOUT', 15))),
    // Semantic Scholar Stage 3 adapter — optional key; never commit real keys.
    'semantic_scholar_api_key' => env('SEMANTIC_SCHOLAR_API_KEY'),
    // Consensus.app Stage 3 adapter (optional/legacy) — name only in .env.example; never commit real keys.
    'consensus_api_key' => env('CONSENSUS_API_KEY'),
    'consensus_base_url' => env('CONSENSUS_BASE_URL', 'https://api.consensus.app'),
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
