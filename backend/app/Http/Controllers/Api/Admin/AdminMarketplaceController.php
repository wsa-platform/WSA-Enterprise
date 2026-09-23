<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMarketplaceController extends Controller
{
    use AuthorizesPlatformAdmin;

    public function __construct(private MarketplaceService $marketplace) {}

    public function listings(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.marketplace.view');

        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->marketplace->searchPlatformAdmin($filters, $filters['per_page'] ?? 15);

        return response()->json([
            'data' => collect($paginator->items())->map(
                fn (MarketplaceListing $listing) => $listing->toOwnerArray()
            )->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function approve(Request $request, MarketplaceListing $listing): JsonResponse
    {
        return $this->moderate($request, $listing, 'approve');
    }

    public function reject(Request $request, MarketplaceListing $listing): JsonResponse
    {
        return $this->moderate($request, $listing, 'reject');
    }

    public function suspend(Request $request, MarketplaceListing $listing): JsonResponse
    {
        return $this->moderate($request, $listing, 'suspend');
    }

    public function moderate(Request $request, MarketplaceListing $listing, string $action): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.marketplace.manage');

        abort_unless(in_array($action, ['approve', 'reject', 'suspend'], true), 422);

        $data = $request->validate(['reason' => ['nullable', 'string']]);
        $listing = $this->marketplace->moderatePlatform(
            $listing,
            $action,
            $request->user(),
            $data['reason'] ?? null,
            $request,
        );

        return response()->json($listing->toOwnerArray());
    }
}
