<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TrainingEnrollment extends Model { protected $table='training_enrollments'; protected $fillable=['organization_id','user_id','course_id','status','enrolled_at','completed_at']; protected function casts(): array { return ['enrolled_at'=>'datetime','completed_at'=>'datetime']; } public function course(): BelongsTo { return $this->belongsTo(TrainingCourse::class, 'course_id'); } public function progress(): HasMany { return $this->hasMany(TrainingProgress::class, 'enrollment_id'); } }
