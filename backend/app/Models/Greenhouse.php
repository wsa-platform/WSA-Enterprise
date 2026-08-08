<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Greenhouse extends Model { protected $fillable=['organization_id','farm_id','field_id','code','name','area_square_meters','structure_type','climate_control','status']; protected function casts(): array { return ['area_square_meters'=>'decimal:2']; } }
