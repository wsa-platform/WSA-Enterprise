<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DiagnosisResult extends Model { protected $fillable=['organization_id','diagnosis_request_id','disease_id','title','summary','confidence_score','severity','priority','provider','is_decision_support','raw_response']; protected function casts(): array { return ['confidence_score'=>'decimal:2','is_decision_support'=>'boolean','raw_response'=>'array']; } public function recommendations(): HasMany { return $this->hasMany(DiagnosisRecommendation::class); } public function request(): BelongsTo { return $this->belongsTo(DiagnosisRequest::class, 'diagnosis_request_id'); } }
