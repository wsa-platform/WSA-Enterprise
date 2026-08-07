<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model { protected $fillable = ['organization_id','code','name','email','phone','tax_number','address','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } }
