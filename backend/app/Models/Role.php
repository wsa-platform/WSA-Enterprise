<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use Auditable;

    protected $fillable = ['organization_id', 'name', 'description'];
    public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class); }
}
