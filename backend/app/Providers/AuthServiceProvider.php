<?php

namespace App\Providers;

use App\Models\Farm;
use App\Policies\BusinessPolicy;
use App\Policies\FarmPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Farm::class => FarmPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Gate::define('business.view', fn ($user, int $organizationId) => app(BusinessPolicy::class)->viewAny($user, $organizationId));
        Gate::define('business.manage', fn ($user, int $organizationId) => app(BusinessPolicy::class)->manage($user, $organizationId));
    }
}
