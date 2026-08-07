<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/v1/health', fn () => response()->json(['status' => 'ok']));

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', fn () => request()->user());
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});
