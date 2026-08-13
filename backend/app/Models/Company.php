<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use BelongsToOwner;

    protected $fillable = ['organization_id', 'owner_user_id', 'name', 'legal_name', 'tax_number', 'email', 'phone', 'address', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
