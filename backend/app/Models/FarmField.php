<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class FarmField extends Model { use BelongsToOwner; protected $table='farm_fields'; protected $fillable=['organization_id','owner_user_id','farm_id','region_id','code','name','area_hectares','soil_type','status']; protected function casts(): array { return ['area_hectares'=>'decimal:3']; } }
