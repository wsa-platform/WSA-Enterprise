<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GpsCoordinate extends Model { protected $fillable=['organization_id','coordinateable_type','coordinateable_id','latitude','longitude','altitude_meters','sequence']; protected function casts(): array { return ['latitude'=>'decimal:7','longitude'=>'decimal:7','altitude_meters'=>'decimal:2']; } }
