<?php

use App\Http\Controllers\Api\Operator\AiRetrievalOperationsController;
use Illuminate\Support\Facades\Route;

/*
| AI-11 authenticated operator retrieval operations.
| Loaded separately so unrelated dirty changes in routes/api.php are not required.
*/
Route::prefix('v1/operator/ai')->middleware(['auth.principal', 'resolve.organization', 'api_client.routes', 'throttle:120,1'])->group(function (): void {
    Route::get('/retrieval/health', [AiRetrievalOperationsController::class, 'health']);
    Route::get('/retrieval/strategy', [AiRetrievalOperationsController::class, 'strategy']);
    Route::get('/retrieval/quality', [AiRetrievalOperationsController::class, 'quality']);
    Route::get('/retrieval/telemetry', [AiRetrievalOperationsController::class, 'telemetry']);
    Route::post('/knowledge', [AiRetrievalOperationsController::class, 'ingest']);
    Route::post('/knowledge/{id}/index', [AiRetrievalOperationsController::class, 'reindex'])->whereNumber('id');
    Route::post('/knowledge/{id}/publish', [AiRetrievalOperationsController::class, 'publish'])->whereNumber('id');
    Route::post('/knowledge/{id}/unpublish', [AiRetrievalOperationsController::class, 'unpublish'])->whereNumber('id');
});
