<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FarmField extends Model { protected $table='farm_fields'; protected $fillable=['organization_id','farm_id','region_id','code','name','area_hectares','soil_type','status']; protected function casts(): array { return ['area_hectares'=>'decimal:3']; } }
