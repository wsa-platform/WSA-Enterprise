<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PurchaseOrder extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','supplier_id','warehouse_id','number','status','ordered_at','expected_at','currency','subtotal','tax_total','total','notes']; protected function casts(): array { return ['ordered_at'=>'date','expected_at'=>'date','subtotal'=>'decimal:2','tax_total'=>'decimal:2','total'=>'decimal:2']; } public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); } }
