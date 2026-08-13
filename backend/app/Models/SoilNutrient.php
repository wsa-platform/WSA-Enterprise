<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class SoilNutrient extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','soil_analysis_id','nutrient','value','unit','target_min','target_max','status']; protected function casts(): array { return ['value'=>'decimal:4','target_min'=>'decimal:4','target_max'=>'decimal:4']; } }
