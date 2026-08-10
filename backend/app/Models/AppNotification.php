<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
class AppNotification extends Model {
    use BelongsToOrganization;
    protected $table='app_notifications';
    protected $fillable=['organization_id','user_id','type','title','body','data','read_at'];
    protected function casts(): array { return ['data'=>'array','read_at'=>'datetime']; }
}
