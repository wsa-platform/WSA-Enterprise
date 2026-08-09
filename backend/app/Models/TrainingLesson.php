<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TrainingLesson extends Model { protected $table='training_lessons'; protected $fillable=['organization_id','course_id','code','title','title_ar','content','content_ar','sort_order','status']; public function course(): BelongsTo { return $this->belongsTo(TrainingCourse::class, 'course_id'); } public function objectives(): HasMany { return $this->hasMany(TrainingObjective::class, 'lesson_id'); } }
