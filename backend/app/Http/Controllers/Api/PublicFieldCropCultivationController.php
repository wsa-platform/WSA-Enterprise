<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Agriculture\CropProfileIdentityValidator;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Tenancy\PublicTenantResolutionException;
use App\Services\Tenancy\PublicTenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicFieldCropCultivationController extends Controller
{
    public function __construct(
        private AgriculturalResearchAgent $researchAgent,
        private PublicTenantResolver $publicTenantResolver,
    ) {}

    public function farmingNeedsProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Accepted for client compatibility only — never authoritative for tenant selection (MODEL B).
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
            'selected_crop_id' => ['required', 'string', 'max:64'],
            'selected_crop_name' => ['required', 'string', 'max:255'],
            'selected_category_id' => ['nullable', 'string', 'max:64'],
            'selected_category_name' => ['nullable', 'string', 'max:255'],
            'knowledge_option' => ['nullable', 'string', 'max:64'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Phase 6 U6.3: authoritative taxonomy identity (ID owns identity; name must match).
        $identity = CropProfileIdentityValidator::normalizePair($validated);
        $validated['selected_crop_id'] = $identity['selected_crop_id'];
        $validated['selected_crop_name'] = $identity['selected_crop_name'];
        $validated['scientific_name'] = $identity['scientific_name'];

        try {
            $publicTenant = $this->publicTenantResolver->bindPublicTenant();
        } catch (PublicTenantResolutionException) {
            return response()->json([
                // Legacy Crop field.
                'load_state' => 'public_organization_unavailable',
                'message' => 'Public organization is unavailable.',
                // Shared Phase-2 error contract (P2-C03 transitional dual-emit).
                'status' => 'public_organization_unavailable',
                'error' => [
                    'code' => 'public_organization_unavailable',
                    'http_status' => 503,
                    'message' => 'Public organization is unavailable.',
                    'details' => null,
                ],
            ], 503);
        }

        $this->publicTenantResolver->recordIgnoredClientOrganizationInput($validated, $publicTenant);

        $profile = $this->researchAgent->conductCropProfileResearch($publicTenant->organizationId, [
            'selected_crop_id' => $validated['selected_crop_id'],
            'selected_crop_name' => $validated['selected_crop_name'],
            'selected_category_id' => $validated['selected_category_id'] ?? '',
            'selected_category_name' => $validated['selected_category_name'] ?? '',
            'knowledge_option' => $validated['knowledge_option'] ?? 'farming-needs',
            'scientific_name' => $validated['scientific_name'] ?? '',
        ]);

        return response()->json($profile);
    }
}
