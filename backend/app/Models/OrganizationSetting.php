<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationSetting extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
