<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active organization once per request and stores it on request attributes.
 */
class ResolveOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $request->attributes->has('organization_id')) {
            $header = $request->header('X-Organization-Id');

            if ($header !== null && $header !== '') {
                abort_unless(
                    $user->organizations()->where('organizations.id', (int) $header)->exists(),
                    403,
                    'You do not have access to this organization.'
                );

                $request->attributes->set('organization_id', (int) $header);
            } elseif ($user->organizations()->exists()) {
                $request->attributes->set('organization_id', $user->organizations()->first()->id);
            }
        }

        return $next($request);
    }
}
