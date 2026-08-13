<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model { use BelongsToOwner; protected $fillable = ['organization_id','owner_user_id','code','name','email','phone','tax_number','billing_address','credit_limit','is_active']; protected function casts(): array { return ['credit_limit'=>'decimal:2','is_active'=>'boolean']; } }
