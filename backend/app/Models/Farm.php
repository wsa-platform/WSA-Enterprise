<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Farm extends Model { protected $fillable=['organization_id','code','name','owner_name','address','area_hectares','is_active']; protected function casts(): array { return ['area_hectares'=>'decimal:3','is_active'=>'boolean']; } }
