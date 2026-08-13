<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class CropType extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','code','name','scientific_name','description']; }
