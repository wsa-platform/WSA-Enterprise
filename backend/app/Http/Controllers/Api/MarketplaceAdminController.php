<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\ContactAccessOrder;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceEntitlement;
use App\Models\MarketplaceListing;
use App\Services\Authorization\PermissionService;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceAdminController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(private MarketplaceService $marketplace) {}

    public function listings(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'market.review');
        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'seller_type' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->marketplace->searchAdmin(
            $filters,
            $filters['per_page'] ?? 15,
            $this->organization($request),
        );
        $includePrivate = $this->canViewSellerPrivate($request);

        return response()->json([
            'data' => collect($paginator->items())->map(function (MarketplaceListing $l) use ($includePrivate) {
                $data = $l->toOwnerArray();
                if (! $includePrivate) {
                    unset($data['seller_email'], $data['seller_phone']);
                }

                return $data;
            })->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function approve(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->authorizePermission($request, 'market.approve');
        $data = $request->validate(['reason' => ['nullable', 'string']]);
        $listing = $this->marketplace->moderate(
            $listing,
            'approve',
            $request->user(),
            $data['reason'] ?? null,
            $this->organization($request),
            $request,
        );

        return response()->json($listing->toOwnerArray());
    }

    public function reject(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->authorizePermission($request, 'market.approve');
        $data = $request->validate(['reason' => ['nullable', 'string']]);
        $listing = $this->marketplace->moderate(
            $listing,
            'reject',
            $request->user(),
            $data['reason'] ?? null,
            $this->organization($request),
            $request,
        );

        return response()->json($listing->toOwnerArray());
    }

    public function suspend(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->authorizePermission($request, 'market.suspend');
        $data = $request->validate(['reason' => ['nullable', 'string']]);
        $listing = $this->marketplace->moderate(
            $listing,
            'suspend',
            $request->user(),
            $data['reason'] ?? null,
            $this->organization($request),
            $request,
        );

        return response()->json($listing->toOwnerArray());
    }

    public function categories(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'market.categories');
        $categories = MarketplaceCategory::orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'market.categories');
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'unique:marketplace_categories,slug'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = MarketplaceCategory::create($data);

        return response()->json($category, 201);
    }

    public function report(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'reports.marketplace');
        $organizationId = $this->organization($request);
        $days = min(max((int) $request->query('days', 30), 1), 365);
        $since = now()->subDays($days);

        $listings = MarketplaceListing::query()->where('organization_id', $organizationId);
        $orders = ContactAccessOrder::query()
            ->where('created_at', '>=', $since)
            ->whereHas('listing', fn ($query) => $query->where('organization_id', $organizationId));

        return response()->json([
            'period_days' => $days,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'listings_total' => (clone $listings)->count(),
                'published' => (clone $listings)->where('status', MarketplaceListing::STATUS_PUBLISHED)->count(),
                'pending_review' => (clone $listings)->where('status', MarketplaceListing::STATUS_PENDING_REVIEW)->count(),
                'contact_orders' => (clone $orders)->count(),
                'contact_orders_paid' => (clone $orders)->where('payment_status', ContactAccessOrder::PAYMENT_PAID)->count(),
                'active_entitlements' => MarketplaceEntitlement::query()
                    ->whereNull('revoked_at')
                    ->whereHas('listing', fn ($query) => $query->where('organization_id', $organizationId))
                    ->count(),
            ],
            'by_status' => (clone $listings)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    private function canViewSellerPrivate(Request $request): bool
    {
        return app(PermissionService::class)->userCan(
            $request->user(),
            $this->organization($request),
            'market.seller_private_data',
        );
    }
}
