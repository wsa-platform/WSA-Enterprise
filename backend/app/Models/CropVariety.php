<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class CropVariety extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','crop_type_id','code','name','supplier','maturity_days','notes']; }
