<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::creating(function (Model $model): void {
            $column = config('user_global_ownership.owner_column', 'user_id');

            if ($model->getAttribute($column) !== null) {
                return;
            }

            $user = auth()->user();
            if ($user !== null) {
                $model->setAttribute($column, $user->id);
            }
        });
    }

    public function owningUser(): BelongsTo
    {
        return $this->belongsTo(User::class, config('user_global_ownership.owner_column', 'user_id'));
    }
}
