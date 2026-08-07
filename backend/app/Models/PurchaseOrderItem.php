<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderItem extends Model { protected $fillable=['purchase_order_id','product_id','quantity','received_quantity','unit_cost','tax_rate','line_total']; protected function casts(): array { return ['quantity'=>'decimal:3','received_quantity'=>'decimal:3','unit_cost'=>'decimal:2','tax_rate'=>'decimal:2','line_total'=>'decimal:2']; } }
