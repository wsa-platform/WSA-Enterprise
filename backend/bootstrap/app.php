<?php

use App\Exceptions\AiQuotaExceededException;
use App\Exceptions\PlanRestrictionException;
use App\Exceptions\SubscriptionInactiveException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/m23.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );
        $middleware->statefulApi();
        $middleware->alias([
            'auth.principal' => \App\Http\Middleware\AuthenticateApiPrincipal::class,
            'resolve.organization' => \App\Http\Middleware\ResolveOrganizationContext::class,
            'api_client.routes' => \App\Http\Middleware\RestrictApiClientRoutes::class,
        ]);
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\AssignRequestId::class,
            \App\Http\Middleware\SetLocaleFromHeader::class,
            \App\Http\Middleware\LogApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AiQuotaExceededException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'quota' => [
                        'limit' => $exception->limit,
                        'used' => $exception->used,
                    ],
                ], 429);
            }
        });

        $exceptions->render(function (SubscriptionInactiveException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'subscription' => [
                        'status' => $exception->status,
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (PlanRestrictionException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'feature' => [
                        'key' => $exception->featureKey,
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->is('api/*') && ! ($exception instanceof NotFoundHttpException) && ! ($exception instanceof ValidationException)) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'Request could not be processed.',
                ], $exception->getStatusCode());
            }
        });
    })->create();
