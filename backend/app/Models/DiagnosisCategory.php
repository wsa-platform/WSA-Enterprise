<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DiagnosisCategory extends Model { protected $fillable=['organization_id','code','name','name_ar','description','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } public function subjects(): HasMany { return $this->hasMany(DiagnosisSubject::class, 'category_id'); } }
