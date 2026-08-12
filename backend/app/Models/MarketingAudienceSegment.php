<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingAudienceSegment extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'criteria',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return ['criteria' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketingCampaign::class, 'audience_segment_id');
    }
}
