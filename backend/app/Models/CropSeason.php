<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CropSeason extends Model { protected $fillable=['organization_id','farm_id','name','code','starts_at','ends_at','status']; protected function casts(): array { return ['starts_at'=>'date','ends_at'=>'date']; } }
