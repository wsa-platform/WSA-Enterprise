<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use Auditable;
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'slug', 'name', 'description'];

    protected static function booted(): void
    {
        static::saved(function (Role $role): void {
            app(\App\Services\Authorization\PermissionCacheInvalidator::class)->forgetRole($role);
        });
    }

    public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class); }
}
