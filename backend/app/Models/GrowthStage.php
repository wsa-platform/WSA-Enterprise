<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class GrowthStage extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','crop_type_id','name','sequence','expected_days','description']; }
