<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;

class PollinationPlant extends Model
{
    use BelongsToOrganization;
    use BelongsToOwner;

    protected $fillable = [
        'organization_id',
        'owner_user_id',
        'species_name',
        'common_name',
        'flowering_start',
        'flowering_end',
        'location',
        'country',
        'region',
        'pollination_relevance',
        'expected_seasons',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'flowering_start' => 'date',
            'flowering_end' => 'date',
            'expected_seasons' => 'array',
        ];
    }
}
