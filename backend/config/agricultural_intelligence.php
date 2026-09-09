<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Universal Agricultural Intelligence (ADR-001 / ADR-002)
    |--------------------------------------------------------------------------
    | Feature flags and provider config. Secrets via env only — never hard-code.
    */
    'orchestrator_enabled' => filter_var(env('UNIVERSAL_ANSWER_ORCHESTRATOR_ENABLED', true), FILTER_VALIDATE_BOOL),
    'enrich_legacy_synthesis' => filter_var(env('UNIVERSAL_ANSWER_ENRICH_LEGACY', true), FILTER_VALIDATE_BOOL),

    'web_search' => [
        'enabled' => filter_var(env('WEB_SEARCH_ENABLED', false), FILTER_VALIDATE_BOOL),
        'provider' => env('WEB_SEARCH_PROVIDER', 'generic'),
        'api_key' => env('WEB_SEARCH_API_KEY'),
        'endpoint' => env('WEB_SEARCH_ENDPOINT'),
        'timeout' => max(1, min(60, (int) env('WEB_SEARCH_TIMEOUT', 15))),
        'display_name' => env('WEB_SEARCH_DISPLAY_NAME', 'Web Search'),
    ],

    'fao' => [
        'enabled' => filter_var(env('FAO_ENABLED', false), FILTER_VALIDATE_BOOL),
        'base_url' => env('FAO_BASE_URL', 'https://fenixservices.fao.org/faostat/api/v1'),
        'timeout' => max(1, min(60, (int) env('FAO_TIMEOUT', 15))),
        'lang' => env('FAO_LANG', 'en'),
    ],

    'semantic_scholar' => [
        'enabled' => filter_var(env('SEMANTIC_SCHOLAR_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],

    'openalex' => [
        'enabled' => filter_var(env('OPENALEX_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],

    'crossref' => [
        'enabled' => filter_var(env('CROSSREF_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],

    'open_meteo' => [
        'enabled' => filter_var(env('OPEN_METEO_ENABLED', true), FILTER_VALIDATE_BOOL),
        'base_url' => env('OPEN_METEO_BASE_URL', 'https://api.open-meteo.com/v1/forecast'),
        'timeout' => max(1, min(60, (int) env('OPEN_METEO_TIMEOUT', 15))),
    ],

    'mcp' => [
        'agrisignal' => [
            'enabled' => filter_var(env('AGRISIGNAL_MCP_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('AGRISIGNAL_MCP_ENDPOINT'),
            'token' => env('AGRISIGNAL_MCP_TOKEN'),
            'timeout' => max(1, min(60, (int) env('AGRISIGNAL_MCP_TIMEOUT', 20))),
        ],
        'agriculture' => [
            'enabled' => filter_var(env('AGRICULTURE_MCP_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('AGRICULTURE_MCP_ENDPOINT'),
            'token' => env('AGRICULTURE_MCP_TOKEN'),
            'timeout' => max(1, min(60, (int) env('AGRICULTURE_MCP_TIMEOUT', 20))),
        ],
    ],

    'disease' => [
        'greensense' => [
            'enabled' => filter_var(env('DISEASE_GREENSENSE_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('DISEASE_GREENSENSE_ENDPOINT'),
            'api_key' => env('DISEASE_GREENSENSE_API_KEY'),
            'timeout' => (int) env('DISEASE_GREENSENSE_TIMEOUT', 20),
            'priority' => 80,
        ],
        'plant_disease_detector' => [
            'enabled' => filter_var(env('DISEASE_PLANT_DETECTOR_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('DISEASE_PLANT_DETECTOR_ENDPOINT'),
            'api_key' => env('DISEASE_PLANT_DETECTOR_API_KEY'),
            'timeout' => (int) env('DISEASE_PLANT_DETECTOR_TIMEOUT', 20),
            'priority' => 81,
        ],
        'leaf_disease' => [
            'enabled' => filter_var(env('DISEASE_LEAF_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('DISEASE_LEAF_ENDPOINT'),
            'api_key' => env('DISEASE_LEAF_API_KEY'),
            'timeout' => (int) env('DISEASE_LEAF_TIMEOUT', 20),
            'priority' => 82,
        ],
        'farm_advisor' => [
            'enabled' => filter_var(env('DISEASE_FARM_ADVISOR_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('DISEASE_FARM_ADVISOR_ENDPOINT'),
            'api_key' => env('DISEASE_FARM_ADVISOR_API_KEY'),
            'timeout' => (int) env('DISEASE_FARM_ADVISOR_TIMEOUT', 20),
            'priority' => 83,
        ],
        'farm_guard' => [
            'enabled' => filter_var(env('DISEASE_FARM_GUARD_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('DISEASE_FARM_GUARD_ENDPOINT'),
            'api_key' => env('DISEASE_FARM_GUARD_API_KEY'),
            'timeout' => (int) env('DISEASE_FARM_GUARD_TIMEOUT', 20),
            'priority' => 84,
        ],
        'huggingface' => [
            'enabled' => filter_var(env('HUGGINGFACE_DISEASE_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('HUGGINGFACE_DISEASE_ENDPOINT'),
            'api_key' => env('HUGGINGFACE_API_KEY'),
            'timeout' => (int) env('HUGGINGFACE_DISEASE_TIMEOUT', 30),
            'priority' => 85,
        ],
        'plant_disease_web' => [
            'enabled' => filter_var(env('DISEASE_WEB_ENABLED', false), FILTER_VALIDATE_BOOL),
            'endpoint' => env('DISEASE_WEB_ENDPOINT'),
            'api_key' => env('DISEASE_WEB_API_KEY'),
            'timeout' => (int) env('DISEASE_WEB_TIMEOUT', 20),
            'priority' => 86,
        ],
    ],

    'field_sense' => [
        'enabled' => filter_var(env('FIELD_SENSE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'endpoint' => env('FIELD_SENSE_ENDPOINT'),
        'api_key' => env('FIELD_SENSE_API_KEY'),
        'timeout' => (int) env('FIELD_SENSE_TIMEOUT', 20),
    ],

    'octopus' => [
        'enabled' => filter_var(env('OCTOPUS_ENABLED', false), FILTER_VALIDATE_BOOL),
        'endpoint' => env('OCTOPUS_ENDPOINT'),
        'api_key' => env('OCTOPUS_API_KEY'),
        'timeout' => (int) env('OCTOPUS_TIMEOUT', 30),
        'max_input_bytes' => (int) env('OCTOPUS_MAX_INPUT_BYTES', 16384),
        'max_output_bytes' => (int) env('OCTOPUS_MAX_OUTPUT_BYTES', 65536),
        // unknown|review_required|permitted|internal_ok — commercial OK is NOT assumed
        'license_status' => env('OCTOPUS_LICENSE_STATUS', 'unknown'),
    ],
];
