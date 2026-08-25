<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Services\Marketplace\MarketplaceContactService;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplacePublicController extends Controller
{
    public function __construct(
        private MarketplaceService $marketplace,
        private MarketplaceContactService $contactService,
    ) {}

    public function listings(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'country' => ['nullable', 'string'],
            'seller_type' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->marketplace->searchPublished($filters, $filters['per_page'] ?? 15);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (MarketplaceListing $l) => $l->toPublicArray())->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function show(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($listing->status === MarketplaceListing::STATUS_PUBLISHED, 404);

        $payload = $listing->load(['category', 'images'])->toPublicArray();

        $isPublicCatalog = str_contains($request->path(), 'public/market');
        $user = $request->user();
        if ($user && ! $isPublicCatalog) {
            $contact = $this->contactService->contactIfEntitled($user, $listing);
            if ($contact) {
                $payload['contact'] = $contact;
            } else {
                $payload['contact_access_required'] = true;
            }
        }

        return response()->json($payload);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => $this->marketplace->activeCategories()]);
    }

    public function units(): JsonResponse
    {
        return response()->json(['data' => $this->marketplace->activeUnits()]);
    }
}
