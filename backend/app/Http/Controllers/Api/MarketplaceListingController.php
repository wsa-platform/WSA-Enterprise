<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\ContactAccessOrder;
use App\Models\MarketplaceEntitlement;
use App\Models\MarketplaceListing;
use App\Services\Authorization\PermissionService;
use App\Services\Marketplace\MarketplaceContactService;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceListingController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private MarketplaceService $marketplace,
        private MarketplaceContactService $contactService,
    ) {}

    public function myListings(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['market.view', 'market.create', 'market.manage_own']);
        $organizationId = $this->organization($request);
        $query = MarketplaceListing::query()
            ->with(['category', 'images'])
            ->where('organization_id', $organizationId);

        if (! app(PermissionService::class)->userCan(
            $request->user(),
            $organizationId,
            'market.manage_all',
        ) && ! app(ServiceOwnershipAuthorizer::class)->canSupervise(
            $request->user(),
            $organizationId,
        )) {
            $query->where('seller_user_id', $request->user()->id);
        }

        $paginator = $query->latest()->paginate(min(max((int) $request->query('per_page', 15), 1), 100));

        return response()->json([
            'data' => collect($paginator->items())->map(fn (MarketplaceListing $l) => $l->toOwnerArray())->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'market.create');
        $data = $this->validatedListingData($request);

        $listing = $this->marketplace->createForSeller(
            $request->user(),
            $data,
            $this->organization($request),
        );

        return response()->json($listing->toOwnerArray(), 201);
    }

    public function update(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->assertOwnListing($request, $listing);
        abort_unless(in_array($listing->status, [
            MarketplaceListing::STATUS_DRAFT,
            MarketplaceListing::STATUS_REJECTED,
            MarketplaceListing::STATUS_UNPUBLISHED,
        ], true), 422);

        $data = $this->validatedListingData($request, partial: true);
        $listing = $this->marketplace->updateListing($listing, $data, $request->user());

        return response()->json($listing->toOwnerArray());
    }

    public function showMine(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->assertOwnListing($request, $listing);

        return response()->json($listing->load(['category', 'images'])->toOwnerArray());
    }

    public function unpublish(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->assertOwnListing($request, $listing);
        $listing = $this->marketplace->unpublish(
            $listing,
            $request->user(),
            $this->organization($request),
            $request,
        );

        return response()->json($listing->toOwnerArray());
    }

    public function destroy(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->assertOwnListing($request, $listing);
        $listing->delete();

        return response()->json(['message' => 'Listing deleted.']);
    }

    public function submit(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $this->assertOwnListing($request, $listing);
        $listing = $this->marketplace->submitForReview(
            $listing,
            $request->user(),
            $this->organization($request),
            $request,
        );

        return response()->json($listing->toOwnerArray());
    }

    public function requestContactAccess(Request $request, MarketplaceListing $listing): JsonResponse
    {
        abort_unless($listing->status === MarketplaceListing::STATUS_PUBLISHED, 404);
        $data = $request->validate(['idempotency_key' => ['nullable', 'string', 'max:64']]);
        $order = $this->contactService->requestAccess(
            $request->user(),
            $listing,
            $data['idempotency_key'] ?? null,
        );

        return response()->json($order, 201);
    }

    public function payContactAccess(Request $request, ContactAccessOrder $order): JsonResponse
    {
        abort_unless($order->buyer_user_id === $request->user()->id, 403);
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:64']]);

        $result = $this->contactService->payOrder(
            $order,
            $data['idempotency_key'],
            $this->organization($request),
            $request,
        );

        return response()->json($result);
    }

    public function myEntitlements(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['market.view', 'market.create', 'market.manage_own']);
        $entitlements = MarketplaceEntitlement::query()
            ->with(['listing:id,title,seller_display_name,country,city'])
            ->where('buyer_user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->latest('granted_at')
            ->paginate(min(max((int) $request->query('per_page', 15), 1), 100));

        return response()->json([
            'data' => collect($entitlements->items())->map(function (MarketplaceEntitlement $e) use ($request) {
                $contact = $this->contactService->contactIfEntitled($request->user(), $e->listing);

                return [
                    'id' => $e->id,
                    'listing' => $e->listing?->only(['id', 'title', 'seller_display_name', 'country', 'city']),
                    'granted_at' => $e->granted_at,
                    'contact' => $contact,
                ];
            })->values(),
            'current_page' => $entitlements->currentPage(),
            'last_page' => $entitlements->lastPage(),
            'total' => $entitlements->total(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validatedListingData(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
            'seller_type' => ['nullable', 'string', 'in:local,international'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'seller_display_name' => ['nullable', 'string', 'max:255'],
            'seller_email' => ['nullable', 'email'],
            'seller_phone' => ['nullable', 'string', 'max:50'],
            'export_ready' => ['sometimes', 'boolean'],
            'export_destination' => ['nullable', 'string', 'max:255'],
            'export_metadata' => ['nullable', 'array'],
            'contact_access_price' => ['nullable', 'numeric', 'min:0'],
        ];

        return $request->validate($rules);
    }

    private function assertOwnListing(Request $request, MarketplaceListing $listing): void
    {
        $organizationId = $this->organization($request);
        abort_unless((int) $listing->organization_id === $organizationId, 404);

        $user = $request->user();
        if ($listing->seller_user_id === $user->id) {
            $this->authorizeAnyPermission($request, ['market.manage_own', 'market.create', 'market.manage_all']);

            return;
        }

        if (app(PermissionService::class)->userCan(
            $user,
            $organizationId,
            'market.manage_all',
        ) || app(ServiceOwnershipAuthorizer::class)->canSupervise($user, $organizationId)) {
            return;
        }

        abort(403, 'You can only manage your own listings.');
    }
}
