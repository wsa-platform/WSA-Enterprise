<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FarmRegion extends Model { protected $table='farm_regions'; protected $fillable=['organization_id','farm_id','code','name','description','area_hectares']; protected function casts(): array { return ['area_hectares'=>'decimal:3']; } }
