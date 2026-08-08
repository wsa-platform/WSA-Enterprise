<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CropVariety extends Model { protected $fillable=['organization_id','crop_type_id','code','name','supplier','maturity_days','notes']; }
