<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CropHarvest extends Model { protected $fillable=['organization_id','season_id','crop_type_id','variety_id','field_id','block_id','harvested_at','quantity','unit','quality_score','notes']; protected function casts(): array { return ['harvested_at'=>'date','quantity'=>'decimal:3','quality_score'=>'decimal:2']; } }
