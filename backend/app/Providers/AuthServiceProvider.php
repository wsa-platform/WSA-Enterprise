<?php

namespace App\Providers;

use App\Models\AiRequest;
use App\Models\Farm;
use App\Models\Team;
use App\Policies\AiRequestPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\FarmPolicy;
use App\Policies\TeamPolicy;
use App\Services\Authorization\PermissionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Farm::class => FarmPolicy::class,
        AiRequest::class => AiRequestPolicy::class,
        Team::class => TeamPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach (PermissionService::catalog() as $permission) {
            Gate::define($permission, function ($user, int $organizationId) use ($permission): bool {
                return app(PermissionService::class)->userCan($user, $organizationId, $permission);
            });
        }

        Gate::define('business.view', fn ($user, int $organizationId) => app(BusinessPolicy::class)->viewAny($user, $organizationId));
        Gate::define('business.manage', fn ($user, int $organizationId) => app(BusinessPolicy::class)->manage($user, $organizationId));

        RateLimiter::for('ai-org', function (Request $request) {
            $organizationId = $request->attributes->get('organization_id')
                ?? $request->header('X-Organization-Id')
                ?? 'guest';

            return Limit::perMinute((int) config('ai.rate_limit_per_minute', 30))
                ->by('ai-org:'.$organizationId);
        });
    }
}
