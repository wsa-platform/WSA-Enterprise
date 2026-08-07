<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['organization_id', 'name', 'legal_name', 'tax_number', 'email', 'phone', 'address', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
