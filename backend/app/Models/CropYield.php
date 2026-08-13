<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class CropYield extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','season_id','crop_type_id','field_id','block_id','area_hectares','expected_quantity','actual_quantity','unit','reported_at','notes']; protected function casts(): array { return ['reported_at'=>'date','area_hectares'=>'decimal:3','expected_quantity'=>'decimal:3','actual_quantity'=>'decimal:3']; } }
