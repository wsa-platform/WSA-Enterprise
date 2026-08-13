<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingTemplate extends Model
{
    use BelongsToOrganization;
    use BelongsToOwner;

    protected $fillable = [
        'organization_id',
        'owner_user_id',
        'slug',
        'name',
        'channel',
        'translations',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return ['translations' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketingCampaign::class, 'template_id');
    }

    /** @return array<string, mixed>|null */
    public function translationFor(string $locale): ?array
    {
        $translations = $this->translations ?? [];

        return $translations[$locale] ?? $translations['en'] ?? null;
    }
}
