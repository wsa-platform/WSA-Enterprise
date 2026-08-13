<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class FarmBlock extends Model { use BelongsToOwner; protected $table='farm_blocks'; protected $fillable=['organization_id','owner_user_id','field_id','code','name','area_hectares','crop','variety','status']; protected function casts(): array { return ['area_hectares'=>'decimal:3']; } }
