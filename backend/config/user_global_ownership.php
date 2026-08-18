<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User-global ownership (not organization-scoped services)
    |--------------------------------------------------------------------------
    |
    | Resources owned by the authenticated user across the platform.
    | Jobs talent profiles use user_id; employers interact via org-scoped requests.
    |
    */

    'owner_column' => 'user_id',

    'forbidden_request_owner_keys' => [
        'user_id',
        'owner_user_id',
        'owner_id',
    ],

    'user_owned_models' => [
        'job_talent_profiles' => \App\Models\JobTalentProfile::class,
        'job_seeker_profiles' => \App\Models\JobSeekerProfile::class,
    ],

];
