<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class GisMap extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','farm_id','name','layer_type','source_url','geojson','metadata']; protected function casts(): array { return ['geojson'=>'array','metadata'=>'array']; } }
