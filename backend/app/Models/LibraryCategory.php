<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class LibraryCategory extends Model { protected $fillable=['organization_id','parent_id','code','name','name_ar']; public function parent(): BelongsTo { return $this->belongsTo(LibraryCategory::class, 'parent_id'); } }
