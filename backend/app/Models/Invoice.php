<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Invoice extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','customer_id','sales_order_id','number','status','issued_at','due_at','paid_at','currency','subtotal','tax_total','total']; protected function casts(): array { return ['issued_at'=>'date','due_at'=>'date','paid_at'=>'datetime','subtotal'=>'decimal:2','tax_total'=>'decimal:2','total'=>'decimal:2']; } public function items(): HasMany { return $this->hasMany(InvoiceItem::class); } }
