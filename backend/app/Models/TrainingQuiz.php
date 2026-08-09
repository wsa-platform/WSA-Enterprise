<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TrainingQuiz extends Model { protected $table='training_quizzes'; protected $fillable=['organization_id','lesson_id','title','title_ar','passing_score']; public function lesson(): BelongsTo { return $this->belongsTo(TrainingLesson::class, 'lesson_id'); } public function questions(): HasMany { return $this->hasMany(TrainingQuestion::class, 'quiz_id'); } }
