<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TrainingObjective extends Model { use BelongsToOwner; protected $table='training_objectives'; protected $fillable=['organization_id','owner_user_id','lesson_id','objective','objective_ar','sort_order']; public function lesson(): BelongsTo { return $this->belongsTo(TrainingLesson::class, 'lesson_id'); } }
