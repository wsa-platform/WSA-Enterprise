<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TrainingCertificate extends Model { use BelongsToOwner; protected $table='training_certificates'; protected $fillable=['organization_id','owner_user_id','user_id','enrollment_id','certificate_code','issued_at','metadata']; protected function casts(): array { return ['issued_at'=>'datetime','metadata'=>'array']; } public function enrollment(): BelongsTo { return $this->belongsTo(TrainingEnrollment::class, 'enrollment_id'); } }
