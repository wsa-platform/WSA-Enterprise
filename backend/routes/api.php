<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AccessController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\CommerceController;
use App\Http\Controllers\Api\FarmController;
use App\Http\Controllers\Api\CropController;
use App\Http\Controllers\Api\SoilController;
use App\Http\Controllers\Api\DiagnosisController;
use App\Http\Controllers\Api\DiagnosisRequestController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ApiClientController;
use App\Http\Controllers\Api\HealthController;

Route::prefix('v1/health')->group(function (): void {
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
    Route::get('/', [HealthController::class, 'legacy']);
});

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:20,1')->group(function (): void {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth.principal', 'resolve.organization', 'api_client.routes', 'throttle:120,1'])->group(function (): void {
        Route::get('/user', fn () => request()->user()?->only(['id', 'name', 'email']));
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/platform/organizations', [PlatformController::class, 'organizations']);
        Route::get('/platform/me', [PlatformController::class, 'me']);
        Route::get('/platform/access-summary', [PlatformController::class, 'accessSummary']);
        Route::get('/platform/workflow-summary', [PlatformController::class, 'workflowSummary']);
        Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('/api-clients', [ApiClientController::class, 'index']);
        Route::post('/api-clients', [ApiClientController::class, 'store']);
        Route::post('/api-clients/{apiClient}/revoke', [ApiClientController::class, 'revoke'])->whereNumber('apiClient');
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->whereNumber('task');
        Route::get('/users', [AccessController::class, 'users']);
        Route::post('/users', [AccessController::class, 'storeUser']);
        Route::post('/users/{user}/roles', [AccessController::class, 'assignRole']);
        Route::get('/roles', [AccessController::class, 'roles']);
        Route::post('/roles', [AccessController::class, 'storeRole']);
        Route::get('/permissions', [AccessController::class, 'permissions']);
        Route::post('/permissions', [AccessController::class, 'storePermission']);
        Route::get('/teams', [TeamController::class, 'index']);
        Route::get('/teams/{team}', [TeamController::class, 'show'])->whereNumber('team');
        Route::post('/teams', [TeamController::class, 'store']);
        Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->whereNumber('team');
        Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->whereNumber(['team', 'user']);
        Route::get('/audit-logs', [AuditController::class, 'index']);
        Route::prefix('billing')->group(function (): void {
            Route::get('/plans', [BillingController::class, 'plans']);
            Route::get('/subscription', [BillingController::class, 'subscription']);
            Route::get('/usage', [BillingController::class, 'usage']);
            Route::get('/invoices', [BillingController::class, 'invoices']);
            Route::post('/subscription/plan', [BillingController::class, 'assignPlan']);
            Route::post('/subscription/cancel', [BillingController::class, 'cancel']);
            Route::post('/subscription/reactivate', [BillingController::class, 'reactivate']);
            Route::get('/settings', [BillingController::class, 'settings']);
            Route::put('/settings', [BillingController::class, 'updateSettings']);
        });
        Route::prefix('directory')->group(function (): void {
            Route::get('/{module}', [DirectoryController::class, 'index']);
            Route::post('/{module}', [DirectoryController::class, 'store']);
            Route::put('/{module}/{id}', [DirectoryController::class, 'update']);
            Route::delete('/{module}/{id}', [DirectoryController::class, 'destroy']);
        });
        Route::prefix('catalog')->group(function (): void {
            Route::get('/{module}', [CatalogController::class, 'index']);
            Route::post('/{module}', [CatalogController::class, 'store']);
            Route::put('/{module}/{id}', [CatalogController::class, 'update']);
            Route::delete('/{module}/{id}', [CatalogController::class, 'destroy']);
        });
        Route::get('/inventory', [OperationsController::class, 'inventory']);
        Route::post('/inventory/adjustments', [OperationsController::class, 'adjustInventory']);
        Route::get('/purchase-orders', [OperationsController::class, 'purchaseOrders']);
        Route::post('/purchase-orders', [OperationsController::class, 'storePurchaseOrder']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [OperationsController::class, 'receivePurchaseOrder']);
        Route::get('/sales-orders', [CommerceController::class, 'salesOrders']);
        Route::post('/sales-orders', [CommerceController::class, 'storeSalesOrder']);
        Route::get('/invoices', [CommerceController::class, 'invoices']);
        Route::post('/invoices', [CommerceController::class, 'storeInvoice']);
        Route::get('/reports/summary', [CommerceController::class, 'report']);
        Route::get('/notifications', [CommerceController::class, 'notifications']);
        Route::post('/notifications/{notification}/read', [CommerceController::class, 'readNotification']);
        Route::prefix('farm')->group(function (): void {
            Route::get('/{module}', [FarmController::class, 'index']);
            Route::post('/{module}', [FarmController::class, 'store']);
            Route::put('/{module}/{id}', [FarmController::class, 'update']);
            Route::delete('/{module}/{id}', [FarmController::class, 'destroy']);
        });
        Route::prefix('crop')->group(function (): void {
            Route::get('/{module}', [CropController::class, 'index']);
            Route::post('/{module}', [CropController::class, 'store']);
            Route::put('/{module}/{id}', [CropController::class, 'update']);
            Route::delete('/{module}/{id}', [CropController::class, 'destroy']);
        });
        Route::prefix('soil')->group(function (): void {
            Route::get('/{module}', [SoilController::class, 'index']);
            Route::post('/{module}', [SoilController::class, 'store']);
            Route::put('/{module}/{id}', [SoilController::class, 'update']);
            Route::delete('/{module}/{id}', [SoilController::class, 'destroy']);
        });
        Route::prefix('diagnosis')->group(function (): void {
            Route::get('/requests', [DiagnosisRequestController::class, 'index']);
            Route::post('/requests', [DiagnosisRequestController::class, 'store']);
            Route::get('/requests/{id}', [DiagnosisRequestController::class, 'show']);
            Route::get('/{module}', [DiagnosisController::class, 'index']);
            Route::post('/{module}', [DiagnosisController::class, 'store']);
            Route::put('/{module}/{id}', [DiagnosisController::class, 'update']);
            Route::delete('/{module}/{id}', [DiagnosisController::class, 'destroy']);
        });
        Route::prefix('training')->group(function (): void {
            Route::get('/enrollments', [TrainingController::class, 'enrollments']);
            Route::post('/enrollments', [TrainingController::class, 'enroll']);
            Route::post('/progress/complete', [TrainingController::class, 'completeLesson']);
            Route::get('/{module}', [TrainingController::class, 'index']);
            Route::post('/{module}', [TrainingController::class, 'store']);
            Route::put('/{module}/{id}', [TrainingController::class, 'update']);
            Route::delete('/{module}/{id}', [TrainingController::class, 'destroy']);
        });
        Route::prefix('library')->group(function (): void {
            Route::get('/search', [LibraryController::class, 'search']);
            Route::get('/{module}', [LibraryController::class, 'index']);
            Route::post('/{module}', [LibraryController::class, 'store']);
            Route::put('/{module}/{id}', [LibraryController::class, 'update']);
            Route::delete('/{module}/{id}', [LibraryController::class, 'destroy']);
        });
        Route::prefix('ai')->middleware('throttle:ai-org')->group(function (): void {
            Route::get('/provider', [AiController::class, 'provider']);
            Route::get('/usage', [AiController::class, 'usage']);
            Route::get('/requests', [AiController::class, 'index']);
            Route::post('/requests', [AiController::class, 'store']);
            Route::get('/requests/{id}', [AiController::class, 'show'])->whereNumber('id');
            Route::post('/requests/{id}/cancel', [AiController::class, 'cancel'])->whereNumber('id');
        });
    });
});
