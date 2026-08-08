<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CropType extends Model { protected $fillable=['organization_id','code','name','scientific_name','description']; }
