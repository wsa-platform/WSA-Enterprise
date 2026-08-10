<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use Auditable;

    protected $fillable = ['organization_id', 'name', 'description'];
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class); }
}
