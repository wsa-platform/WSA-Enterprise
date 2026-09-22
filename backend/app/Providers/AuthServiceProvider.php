<?php

namespace App\Providers;

use App\Models\AiRequest;
use App\Models\Farm;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Policies\AiRequestPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\FarmPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\TeamPolicy;
use App\Services\Authorization\PermissionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Farm::class => FarmPolicy::class,
        AiRequest::class => AiRequestPolicy::class,
        Team::class => TeamPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
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

        // Phase 8A-1 / U8.3 — global public compatibility cap (inherits historical throttle:60,1).
        // Applies to ALL /api/v1/public/* traffic so expensive+browse cannot exceed 60/min combined.
        RateLimiter::for('public-global', function (Request $request) {
            $limit = (int) config('wsa.public_global_per_minute', 60);

            return Limit::perMinute($limit)
                ->by('public-global:'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $requestId = $request->attributes->get('request_id');

                    Log::warning('security.public_global_throttled', [
                        'request_id' => is_string($requestId) ? $requestId : null,
                        'route' => $request->path(),
                        'ip' => $request->ip(),
                        'limiter' => 'public-global',
                    ]);

                    return response()->json([
                        'message' => 'Too many public requests. Please try again later.',
                        'error' => [
                            'code' => 'public_global_rate_limited',
                            'http_status' => 429,
                            'message' => 'Too many public requests. Please try again later.',
                            'details' => null,
                        ],
                    ], 429, $headers);
                });
        });

        // Phase 8A-1 / U8.3 — specialized expensive public research/crop compute sub-bucket.
        RateLimiter::for('public-expensive-compute', function (Request $request) {
            $limit = (int) config('wsa.public_expensive_compute_per_minute', 60);

            return Limit::perMinute($limit)
                ->by('public-expensive-compute:'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $requestId = $request->attributes->get('request_id');

                    Log::warning('security.public_expensive_compute_throttled', [
                        'request_id' => is_string($requestId) ? $requestId : null,
                        'route' => $request->path(),
                        'ip' => $request->ip(),
                        'limiter' => 'public-expensive-compute',
                    ]);

                    return response()->json([
                        'message' => 'Too many expensive public research requests. Please try again later.',
                        'error' => [
                            'code' => 'public_expensive_compute_rate_limited',
                            'http_status' => 429,
                            'message' => 'Too many expensive public research requests. Please try again later.',
                            'details' => null,
                        ],
                    ], 429, $headers);
                });
        });

        // Phase 8A-1 / U8.3 — specialized browse/catalog/feedback sub-bucket.
        RateLimiter::for('public-browse', function (Request $request) {
            $limit = (int) config('wsa.public_browse_per_minute', 60);

            return Limit::perMinute($limit)
                ->by('public-browse:'.$request->ip());
        });
    }
}
