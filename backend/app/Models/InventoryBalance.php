<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryBalance extends Model { protected $fillable=['organization_id','warehouse_id','product_id','quantity','reserved_quantity','average_cost']; protected function casts(): array { return ['quantity'=>'decimal:3','reserved_quantity'=>'decimal:3','average_cost'=>'decimal:2']; } public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); } public function product(): BelongsTo { return $this->belongsTo(Product::class); } }
