<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Warehouse extends Model { protected $fillable = ['organization_id','branch_id','code','name','address','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } }
