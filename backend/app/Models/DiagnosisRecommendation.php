<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DiagnosisRecommendation extends Model { protected $fillable=['organization_id','diagnosis_result_id','title','recommendation','category','priority','status']; public function result(): BelongsTo { return $this->belongsTo(DiagnosisResult::class, 'diagnosis_result_id'); } }
