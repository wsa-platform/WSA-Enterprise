<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AccessController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\CatalogController;

Route::get('/v1/health', fn () => response()->json(['status' => 'ok']));

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', fn () => request()->user());
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
        Route::get('/users', [AccessController::class, 'users']);
        Route::post('/users', [AccessController::class, 'storeUser']);
        Route::post('/users/{user}/roles', [AccessController::class, 'assignRole']);
        Route::get('/roles', [AccessController::class, 'roles']);
        Route::post('/roles', [AccessController::class, 'storeRole']);
        Route::get('/permissions', [AccessController::class, 'permissions']);
        Route::post('/permissions', [AccessController::class, 'storePermission']);
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
    });
});
