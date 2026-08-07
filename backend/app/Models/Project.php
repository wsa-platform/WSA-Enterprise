<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'manager_id', 'code', 'name', 'description',
        'status', 'starts_at', 'ends_at', 'budget',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'budget' => 'decimal:2'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
