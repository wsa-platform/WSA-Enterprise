<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring & health checks (Phase 12 M12.4)
    |--------------------------------------------------------------------------
    */
    'enabled' => filter_var(env('MONITORING_ENABLED', true), FILTER_VALIDATE_BOOL),

    'record_incidents_on_ready_failure' => filter_var(
        env('MONITORING_RECORD_INCIDENTS', true),
        FILTER_VALIDATE_BOOL
    ),

    'auto_remediation' => filter_var(
        env('MONITORING_AUTO_REMEDIATION', false),
        FILTER_VALIDATE_BOOL
    ),

    /*
    | Explicit allowlist of safe remediation actions. Never execute shell
    | commands or destructive database operations from this list.
    */
    'allowed_remediation_actions' => [
        'cache.clear_probe_keys',
        'health.rerun_checks',
        'incident.mark_analyzed',
        'incident.escalate',
        'incident.resolve',
    ],

    'high_risk_actions' => [
        'incident.escalate',
    ],

    'checks' => [
        'database' => true,
        'cache' => true,
        'queue' => true,
        'storage' => true,
        'scheduler' => true,
        'authentication' => true,
    ],

    /*
    | Fixed cache key used by health probe writes (M13.1). Remediation may clear it.
    */
    'cache_probe_key' => env('MONITORING_CACHE_PROBE_KEY', 'healthcheck:probe:write'),

    /*
    | Skip creating a new incident when an open incident exists for the same component.
    */
    'deduplicate_open_incidents' => filter_var(
        env('MONITORING_DEDUPLICATE_OPEN_INCIDENTS', true),
        FILTER_VALIDATE_BOOL
    ),
];
