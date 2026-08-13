<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class FarmRegion extends Model { use BelongsToOwner; protected $table='farm_regions'; protected $fillable=['organization_id','owner_user_id','farm_id','code','name','description','area_hectares']; protected function casts(): array { return ['area_hectares'=>'decimal:3']; } }
