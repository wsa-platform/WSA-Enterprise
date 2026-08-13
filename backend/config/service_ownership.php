<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global service ownership
    |--------------------------------------------------------------------------
    |
    | Every user-owned service stores owner_user_id and is created from the
    | authenticated user. Administrative roles with services.supervise may
    | access all services in an organization without replacing ownership.
    |
    */

    'owner_column' => 'owner_user_id',

    'supervise_permission' => 'services.supervise',

    'forbidden_request_owner_keys' => [
        'owner_user_id',
        'owner_id',
        'created_by_user_id',
        'created_by',
    ],

    /*
    | Tables that receive owner_user_id. Legacy rows may remain NULL until
    | backfilled; NULL owner rows are visible only to services.supervise roles.
    */
    'service_tables' => [
        'farms', 'farm_regions', 'farm_fields', 'farm_blocks', 'greenhouses', 'irrigation_zones', 'gis_maps', 'gps_coordinates',
        'crop_types', 'crop_varieties', 'crop_seasons', 'growth_stages', 'crop_harvests', 'crop_yields',
        'soil_analyses', 'soil_nutrients', 'soil_recommendations',
        'diagnosis_categories', 'diagnosis_subjects', 'diagnosis_symptoms', 'diagnosis_diseases',
        'diagnosis_requests', 'diagnosis_results', 'diagnosis_recommendations',
        'training_courses', 'training_lessons', 'training_objectives', 'training_quizzes', 'training_questions',
        'training_enrollments', 'training_progress', 'training_certificates',
        'library_categories', 'library_tags', 'library_items',
        'apiaries', 'hives', 'hive_inspections', 'hive_treatments', 'hive_feedings',
        'hive_production_records', 'bee_calendar_tasks', 'pollination_plants', 'beekeeper_profiles',
        'ai_requests', 'ai_conversations', 'ai_vision_uploads',
        'marketing_audience_segments', 'marketing_templates', 'marketing_campaigns', 'marketing_deliveries',
        'companies', 'branches', 'employees', 'customers', 'suppliers', 'categories', 'products', 'warehouses',
        'inventory_balances', 'inventory_movements', 'purchase_orders', 'sales_orders', 'invoices',
    ],

    /*
    | Tables where ownership cannot be inferred safely from existing columns.
    | Rows stay NULL owner_user_id and remain supervisor-only until manually resolved.
    */
    'legacy_unbackfillable_tables' => [
        'gps_coordinates',
        'inventory_balances',
        'marketing_consents',
        'marketing_suppressions',
    ],

    /*
    | Tables where user_id reliably equals the service owner. Never backfill from
    | user_id on tables like employees where user_id refers to a linked account.
    */
    'backfill_from_user_id_tables' => [
        'diagnosis_requests',
        'ai_requests',
        'ai_conversations',
        'ai_vision_uploads',
        'training_enrollments',
        'training_progress',
        'training_certificates',
        'beekeeper_profiles',
    ],

    'backfill_from_created_by_user_id_tables' => [
        'marketing_campaigns',
        'marketing_audience_segments',
        'marketing_templates',
    ],

];
