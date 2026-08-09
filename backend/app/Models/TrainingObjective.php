<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TrainingObjective extends Model { protected $table='training_objectives'; protected $fillable=['organization_id','lesson_id','objective','objective_ar','sort_order']; public function lesson(): BelongsTo { return $this->belongsTo(TrainingLesson::class, 'lesson_id'); } }
