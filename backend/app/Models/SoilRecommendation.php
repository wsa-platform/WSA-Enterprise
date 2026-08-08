<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SoilRecommendation extends Model { protected $fillable=['organization_id','soil_analysis_id','field_id','block_id','title','recommendation','category','priority','status','due_at']; protected function casts(): array { return ['due_at'=>'date']; } }
