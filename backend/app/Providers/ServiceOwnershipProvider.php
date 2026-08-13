<?php

namespace App\Providers;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ServiceOwnershipProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (config('service_ownership.service_tables', []) as $table) {
            $modelClass = $this->modelClassForTable($table);

            if ($modelClass === null || ! class_exists($modelClass)) {
                continue;
            }

            if (in_array(BelongsToOwner::class, class_uses_recursive($modelClass), true)) {
                continue;
            }

            $modelClass::creating(function (Model $model): void {
                $column = config('service_ownership.owner_column', 'owner_user_id');

                if ($model->getAttribute($column) !== null) {
                    return;
                }

                $user = auth()->user();
                if ($user !== null) {
                    $model->setAttribute($column, $user->id);
                }
            });
        }
    }

    private function modelClassForTable(string $table): ?string
    {
        return 'App\\Models\\'.Str::studly(Str::singular($table));
    }
}
