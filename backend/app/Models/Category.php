<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class Category extends Model { use BelongsToOwner; protected $fillable = ['organization_id','owner_user_id','parent_id','name','code','description']; }
