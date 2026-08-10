<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use Auditable;
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'description'];

    protected static function booted(): void
    {
        static::saved(function (Permission $permission): void {
            app(\App\Services\Authorization\PermissionCacheInvalidator::class)
                ->forgetOrganization($permission->organization_id);
        });
    }

    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class); }
}
