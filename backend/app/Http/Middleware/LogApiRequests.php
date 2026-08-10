<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        if ($request->is('api/*')) {
            Log::info('api.request', [
                'request_id' => $request->attributes->get('request_id'),
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                'user_id' => $request->user()?->id,
                'organization_id' => $request->attributes->get('organization_id') ?? $request->header('X-Organization-Id'),
            ]);
        }

        return $response;
    }
}
