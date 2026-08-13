<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DiagnosisSubject extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','category_id','crop_type_id','code','name','name_ar','subject_type','description','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } public function category(): BelongsTo { return $this->belongsTo(DiagnosisCategory::class, 'category_id'); } }
