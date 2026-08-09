<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AiRequest extends Model { protected $fillable=['organization_id','user_id','request_type','source_type','source_id','provider','status','input','output','error_message','latency_ms','tokens_used']; protected function casts(): array { return ['input'=>'array','output'=>'array']; } public function user(): BelongsTo { return $this->belongsTo(User::class); } }
