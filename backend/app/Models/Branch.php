<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['organization_id', 'company_id', 'name', 'code', 'email', 'phone', 'address', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
