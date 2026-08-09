<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TrainingProgress extends Model { protected $table='training_progress'; protected $fillable=['organization_id','user_id','enrollment_id','lesson_id','status','score','completed_at']; protected function casts(): array { return ['completed_at'=>'datetime']; } public function enrollment(): BelongsTo { return $this->belongsTo(TrainingEnrollment::class, 'enrollment_id'); } public function lesson(): BelongsTo { return $this->belongsTo(TrainingLesson::class, 'lesson_id'); } }
