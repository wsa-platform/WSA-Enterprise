<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalesOrderItem extends Model { protected $fillable=['sales_order_id','product_id','quantity','fulfilled_quantity','unit_price','tax_rate','line_total']; }
