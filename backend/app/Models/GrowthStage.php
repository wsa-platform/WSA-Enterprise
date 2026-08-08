<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowthStage extends Model { protected $fillable=['organization_id','crop_type_id','name','sequence','expected_days','description']; }
