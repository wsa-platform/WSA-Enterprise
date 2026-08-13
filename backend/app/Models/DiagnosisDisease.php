<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DiagnosisDisease extends Model { use BelongsToOwner; protected $fillable=['organization_id','owner_user_id','subject_id','code','name','name_ar','scientific_name','description','default_severity']; public function subject(): BelongsTo { return $this->belongsTo(DiagnosisSubject::class, 'subject_id'); } }
