<?php

namespace App\Http\Middleware;

use App\Services\Authorization\PlatformAdministratorAuthorizer;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePlatformAdministrator
{
    public function __construct(
        private PlatformAdministratorAuthorizer $authorizer,
        private TenantContext $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        // Platform Admin APIs must not inherit Organization tenant context.
        $this->tenant->setOrganizationId(null);

        if (! $this->authorizer->isPlatformAdministrator($user)) {
            $this->authorizer->deny($request, $user, 'platform.access');
        }

        return $next($request);
    }
}
