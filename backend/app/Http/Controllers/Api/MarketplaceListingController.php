<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\ContactAccessOrder;
use App\Models\MarketplaceEntitlement;
use App\Models\MarketplaceListing;
use App\Support\IsoCountries;
use App\Support\InternationalPhone;
use App\Support\MarketplaceCurrencyCountries;
use App\Services\Authorization\PermissionService;
use App\Services\Marketplace\MarketplaceContactService;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            ->with(['category', 'images', 'unit'])
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

    public function sellerContact(Request $request, ContactAccessOrder $order): JsonResponse
    {
        abort_unless($request->user() !== null, 401);
        $contact = $this->contactService->contactForPaidOrder($request->user(), $order);
        abort_unless($contact !== null, 403);

        return response()->json($contact);
    }

    public function myEntitlements(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['market.view', 'market.create', 'market.manage_own']);
        $entitlements = MarketplaceEntitlement::query()
            ->with(['listing:id,title,seller_display_name,country,city,seller_email,seller_phone'])
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
        foreach (['country', 'origin_country'] as $isoField) {
            $value = $request->input($isoField);
            if (is_string($value) && $value !== '') {
                $request->merge([$isoField => strtoupper(trim($value))]);
            } elseif ($request->exists($isoField) && ($value === '' || $value === null)) {
                $request->merge([$isoField => null]);
            }
        }

        $currency = $request->input('currency');
        if (is_string($currency) && $currency !== '') {
            $request->merge(['currency' => strtoupper(trim($currency))]);
        }

        if ($request->exists('seller_types')) {
            $types = $request->input('seller_types');
            $mapped = is_array($types) ? MarketplaceListing::sellerTypeFromTypes($types) : null;
            $request->merge(['seller_type' => $mapped]);
        }

        if ($request->filled('currency') && ! $request->filled('country')) {
            $derivedCountry = MarketplaceCurrencyCountries::countryFor((string) $request->input('currency'));
            if ($derivedCountry !== null) {
                $request->merge(['country' => $derivedCountry]);
            }
        }

        $isoCode = ['string', 'size:2', Rule::in(IsoCountries::codes())];

        $data = $request->validate([
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
            'product_type' => ['nullable', 'string', 'max:64'],
            'brand' => ['nullable', 'string', 'max:255'],
            'seller_types' => ['nullable', 'array', 'min:1'],
            'seller_types.*' => ['string', Rule::in(MarketplaceListing::SELLER_TYPE_FLAGS)],
            'seller_type' => [$partial ? 'sometimes' : 'required', 'string', Rule::in(MarketplaceListing::SELLER_TYPES)],
            'availability' => ['nullable', 'string', Rule::in(MarketplaceListing::AVAILABILITY_INPUTS)],
            'unit_id' => ['nullable', 'integer', 'exists:marketplace_units,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(MarketplaceCurrencyCountries::currencyCodes())],
            'country' => array_merge([$partial ? 'sometimes' : 'required'], $isoCode),
            'origin_country' => array_merge(['nullable'], $isoCode),
            'city' => ['nullable', 'string', 'max:100'],
            'seller_region' => ['nullable', 'string', 'max:150'],
            'seller_display_name' => ['nullable', 'string', 'max:255'],
            'seller_email' => [$partial ? 'sometimes' : 'required', 'email', 'max:255'],
            'seller_phone' => [$partial ? 'sometimes' : 'required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'export_ready' => ['sometimes', 'boolean'],
            'min_order_quantity' => ['nullable', 'numeric', 'min:0'],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'production_capacity' => ['nullable', 'numeric', 'min:0'],
            'wholesale' => ['sometimes', 'boolean'],
            'retail' => ['sometimes', 'boolean'],
            'packaging' => ['nullable', 'string'],
            'specifications' => ['nullable', 'array'],
            'contact_access_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        unset($data['seller_types']);

        if (isset($data['availability'])) {
            $data['availability'] = MarketplaceListing::canonicalizeAvailability($data['availability']);
        }

        if (! empty($data['currency']) && ! empty($data['country'])) {
            if (! MarketplaceCurrencyCountries::isValidPair($data['currency'], $data['country'])) {
                throw ValidationException::withMessages([
                    'currency' => ['The selected currency and country combination is invalid.'],
                    'country' => ['The selected currency and country combination is invalid.'],
                ]);
            }
        }

        if (isset($data['seller_email']) && is_string($data['seller_email'])) {
            $data['seller_email'] = strtolower(trim($data['seller_email']));
        }
        if (isset($data['seller_phone']) && is_string($data['seller_phone'])) {
            $normalized = InternationalPhone::normalize($data['seller_phone']);
            if ($normalized !== null) {
                $data['seller_phone'] = $normalized;
            }
        }

        return $data;
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
