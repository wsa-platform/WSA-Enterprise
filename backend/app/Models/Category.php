<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Category extends Model { protected $fillable = ['organization_id','parent_id','name','code','description']; }
