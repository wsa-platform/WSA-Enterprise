<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class LibraryCategory extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','parent_id','code','name','name_ar']; public function parent(): BelongsTo { return $this->belongsTo(LibraryCategory::class, 'parent_id'); } }
