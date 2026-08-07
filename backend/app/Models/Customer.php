<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model { protected $fillable = ['organization_id','code','name','email','phone','tax_number','billing_address','credit_limit','is_active']; protected function casts(): array { return ['credit_limit'=>'decimal:2','is_active'=>'boolean']; } }
