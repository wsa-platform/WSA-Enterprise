<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','warehouse_id','product_id','type','quantity','unit_cost','reference_type','reference_id','notes']; protected function casts(): array { return ['quantity'=>'decimal:3','unit_cost'=>'decimal:2']; } }
