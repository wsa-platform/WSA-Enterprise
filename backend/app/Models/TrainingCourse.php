<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TrainingCourse extends Model { use BelongsToOwner; protected $table='training_courses'; protected $fillable=['organization_id','owner_user_id','code','title','title_ar','description','description_ar','locale','status','sort_order']; public function lessons(): HasMany { return $this->hasMany(TrainingLesson::class, 'course_id'); } }
