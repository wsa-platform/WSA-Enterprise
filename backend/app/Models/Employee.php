<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use BelongsToOwner;

    protected $fillable = ['organization_id', 'owner_user_id', 'company_id', 'branch_id', 'user_id', 'employee_number', 'first_name', 'last_name', 'email', 'title', 'status', 'hired_at'];
    protected function casts(): array { return ['hired_at' => 'date']; }
}
