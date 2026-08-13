<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class IrrigationZone extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','farm_id','field_id','block_id','greenhouse_id','code','name','method','flow_rate_lph','status']; protected function casts(): array { return ['flow_rate_lph'=>'decimal:2']; } }
