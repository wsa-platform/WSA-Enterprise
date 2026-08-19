<?php

use App\Http\Controllers\Api\MarketplaceListingController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
| M23 account + own-listing routes.
| Loaded separately so unrelated dirty changes in routes/api.php are not required.
*/
Route::prefix('v1')->middleware(['auth.principal', 'resolve.organization', 'api_client.routes', 'throttle:120,1'])->group(function (): void {
    Route::patch('/account/profile', [UserProfileController::class, 'update']);
    Route::get('/market/my-listings/{listing}', [MarketplaceListingController::class, 'showMine'])->whereNumber('listing');
    Route::post('/market/listings/{listing}/unpublish', [MarketplaceListingController::class, 'unpublish'])->whereNumber('listing');
});
