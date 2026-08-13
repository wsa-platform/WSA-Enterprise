<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class CropSeason extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','farm_id','name','code','starts_at','ends_at','status']; protected function casts(): array { return ['starts_at'=>'date','ends_at'=>'date']; } }
