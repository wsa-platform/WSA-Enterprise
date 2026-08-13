<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use BelongsToOwner;

    protected $fillable = ['organization_id', 'owner_user_id', 'company_id', 'name', 'code', 'email', 'phone', 'address', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
