<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DiagnosisSymptom extends Model { protected $fillable=['organization_id','subject_id','code','name','name_ar','description']; public function subject(): BelongsTo { return $this->belongsTo(DiagnosisSubject::class, 'subject_id'); } }
