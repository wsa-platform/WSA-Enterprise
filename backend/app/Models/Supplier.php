<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model { use BelongsToOwner; protected $fillable = ['organization_id','owner_user_id','code','name','email','phone','tax_number','address','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } }
