<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictApiClientRoutes
{
    /** @var list<string> */
    private array $allowedPatterns = [
        'api/v1/analytics/overview',
        'api/v1/ai/usage',
        'api/v1/billing/usage',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('auth_via') !== 'api_client') {
            return $next($request);
        }

        if ($request->method() !== 'GET') {
            abort(403, 'API client credentials cannot perform this action.');
        }

        $path = $request->path();

        foreach ($this->allowedPatterns as $pattern) {
            if ($path === $pattern) {
                return $next($request);
            }
        }

        abort(403, 'API client credentials are not permitted for this endpoint.');
    }
}
