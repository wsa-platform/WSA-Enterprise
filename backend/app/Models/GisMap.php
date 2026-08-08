<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GisMap extends Model { protected $fillable=['organization_id','farm_id','name','layer_type','source_url','geojson','metadata']; protected function casts(): array { return ['geojson'=>'array','metadata'=>'array']; } }
