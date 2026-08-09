<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class LibraryTag extends Model { protected $fillable=['organization_id','name','name_ar']; public function items(): BelongsToMany { return $this->belongsToMany(LibraryItem::class, 'library_item_tag'); } }
