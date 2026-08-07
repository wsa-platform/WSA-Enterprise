<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceItem extends Model { protected $fillable=['invoice_id','product_id','description','quantity','unit_price','tax_rate','line_total']; }
