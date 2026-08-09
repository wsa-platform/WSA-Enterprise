<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DiagnosisRequest extends Model { protected $fillable=['organization_id','user_id','field_id','block_id','crop_type_id','subject_id','reference','status','notes','image_disk','image_path','symptom_ids']; protected function casts(): array { return ['symptom_ids'=>'array']; } public function results(): HasMany { return $this->hasMany(DiagnosisResult::class); } public function user(): BelongsTo { return $this->belongsTo(User::class); } }
