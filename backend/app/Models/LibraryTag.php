<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class LibraryTag extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','name','name_ar']; public function items(): BelongsToMany { return $this->belongsToMany(LibraryItem::class, 'library_item_tag'); } }
