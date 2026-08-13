<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class SoilRecommendation extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','soil_analysis_id','field_id','block_id','title','recommendation','category','priority','status','due_at']; protected function casts(): array { return ['due_at'=>'date']; } }
