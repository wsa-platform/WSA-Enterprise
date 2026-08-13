<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TrainingQuestion extends Model { use BelongsToOwner; protected $table='training_questions'; protected $fillable=['organization_id','owner_user_id','quiz_id','question','question_ar','question_type','options','correct_answer','sort_order']; protected function casts(): array { return ['options'=>'array']; } public function quiz(): BelongsTo { return $this->belongsTo(TrainingQuiz::class, 'quiz_id'); } }
