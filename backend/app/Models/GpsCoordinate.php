<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class GpsCoordinate extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','coordinateable_type','coordinateable_id','latitude','longitude','altitude_meters','sequence']; protected function casts(): array { return ['latitude'=>'decimal:7','longitude'=>'decimal:7','altitude_meters'=>'decimal:2']; } }
