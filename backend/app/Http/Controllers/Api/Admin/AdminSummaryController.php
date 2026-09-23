<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfile;
use App\Models\LibraryItem;
use App\Models\MarketplaceListing;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSummaryController extends Controller
{
    use AuthorizesPlatformAdmin;

    public function show(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.reports.view');

        return response()->json([
            'organizations' => Organization::query()->count(),
            'users' => User::query()->count(),
            'platform_administrators' => User::query()->where('is_platform_administrator', true)->count(),
            'job_seekers' => JobSeekerProfile::query()->count(),
            'marketplace_listings' => MarketplaceListing::query()->count(),
            'library_items' => LibraryItem::query()->count(),
        ]);
    }
}
