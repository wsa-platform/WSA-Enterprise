<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['organization_id', 'company_id', 'branch_id', 'user_id', 'employee_number', 'first_name', 'last_name', 'email', 'title', 'status', 'hired_at'];
    protected function casts(): array { return ['hired_at' => 'date']; }
}
