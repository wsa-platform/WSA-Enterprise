<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active organization once per request and stores it on request attributes.
 */
class ResolveOrganizationContext
{
    public function __construct(
        private TenantContext $tenant,
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($request->attributes->get('auth_via') === 'api_client') {
            if ($request->attributes->has('organization_id')) {
                $this->tenant->setOrganizationId((int) $request->attributes->get('organization_id'));
            }

            return $next($request);
        }

        if ($user !== null && ! $request->attributes->has('organization_id')) {
            $header = $request->header('X-Organization-Id');

            if ($header !== null && $header !== '') {
                $organizationId = (int) $header;

                if (! $user->organizations()->where('organizations.id', $organizationId)->exists()) {
                    $this->audit->record(
                        action: 'security.cross_tenant_denied',
                        organizationId: null,
                        userId: $user->id,
                        newValues: [
                            'attempted_organization_id' => $organizationId,
                            'request_id' => $request->attributes->get('request_id'),
                        ],
                        request: $request,
                    );

                    $primaryOrganization = $user->organizations()->first();
                    if ($primaryOrganization !== null) {
                        $this->notifications->notifyCrossTenantAttempt(
                            $primaryOrganization->id,
                            $user->id,
                            $organizationId,
                            $request->attributes->get('request_id'),
                        );
                    }

                    abort(403, 'You do not have access to this organization.');
                }

                $request->attributes->set('organization_id', $organizationId);
            } elseif ($user->organizations()->exists()) {
                $request->attributes->set('organization_id', $user->organizations()->first()->id);
            }

            if ($request->attributes->has('organization_id')) {
                $this->tenant->setOrganizationId((int) $request->attributes->get('organization_id'));
            }
        }

        return $next($request);
    }
}
