<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class Warehouse extends Model { use BelongsToOwner; protected $fillable = ['organization_id','owner_user_id','branch_id','code','name','address','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } }
