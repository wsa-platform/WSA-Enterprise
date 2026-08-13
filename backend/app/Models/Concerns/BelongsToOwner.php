<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOwner
{
    public static function bootBelongsToOwner(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute(config('service_ownership.owner_column')) !== null) {
                return;
            }

            $user = auth()->user();
            if ($user !== null) {
                $model->setAttribute(config('service_ownership.owner_column'), $user->id);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, config('service_ownership.owner_column'));
    }
}
