<?php

return [
    // MODEL B: server-authoritative public/demo tenant for unauthenticated research/crop writes.
    // Clients may still send organization fields for compatibility; they are never used for tenant selection.
    'public_organization_slug' => env('WSA_PUBLIC_ORG_SLUG', 'wsa-demo'),

    /*
     | Public HTTP rate limits (Phase 8A-1 / U8.3).
     | public_global_per_minute inherits the pre-8A-1 shared `throttle:60,1` policy
     | and caps ALL public traffic (expensive + browse) so combined capacity cannot
     | exceed the historical 60/min. Named sub-buckets remain for future
     | expensive-only tightening (HUMAN_POLICY_REQUIRED) without redesign.
     */
    'public_global_per_minute' => max(1, (int) env('WSA_PUBLIC_GLOBAL_PER_MINUTE', 60)),
    'public_expensive_compute_per_minute' => max(1, (int) env('WSA_PUBLIC_EXPENSIVE_COMPUTE_PER_MINUTE', 60)),
    'public_browse_per_minute' => max(1, (int) env('WSA_PUBLIC_BROWSE_PER_MINUTE', 60)),

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
