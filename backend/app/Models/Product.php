<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class Product extends Model { use BelongsToOwner; protected $fillable = ['organization_id','owner_user_id','category_id','sku','name','description','unit','cost_price','sale_price','reorder_level','is_active']; protected function casts(): array { return ['cost_price'=>'decimal:2','sale_price'=>'decimal:2','reorder_level'=>'decimal:3','is_active'=>'boolean']; } }
