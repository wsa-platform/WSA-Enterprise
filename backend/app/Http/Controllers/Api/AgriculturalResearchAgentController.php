<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Tenancy\PublicTenantResolutionException;
use App\Services\Tenancy\PublicTenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgriculturalResearchAgentController extends Controller
{
    public function __construct(
        private AgriculturalResearchAgent $researchAgent,
        private PublicTenantResolver $publicTenantResolver,
    ) {}

    public function query(Request $request): JsonResponse
    {
        if (! config('wsa.research_agent.enabled', true)) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Agricultural research agent is disabled.',
            ], 503);
        }

        $validated = $request->validate([
            // Accepted for client compatibility only — never authoritative for tenant selection (MODEL B).
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
            'query' => ['required', 'string', 'max:2000'],
            'domain' => ['nullable', 'string', 'max:128'],
            'agricultural_domain' => ['nullable', 'string', 'max:128'],
            'entities' => ['nullable', 'array'],
            'entities.*.type' => ['required_with:entities', 'string', 'max:64'],
            'entities.*.value' => ['required_with:entities', 'string', 'max:255'],
            'entities.*.label' => ['nullable', 'string', 'max:255'],
            'selected_crop_id' => ['nullable', 'string', 'max:64'],
            'selected_crop_name' => ['nullable', 'string', 'max:255'],
            'selected_category_id' => ['nullable', 'string', 'max:64'],
            'selected_category_name' => ['nullable', 'string', 'max:255'],
            'knowledge_option' => ['nullable', 'string', 'max:64'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $publicTenant = $this->publicTenantResolver->bindPublicTenant();
        } catch (PublicTenantResolutionException) {
            return response()->json([
                // Legacy field (Home consumers).
                'status' => 'public_organization_unavailable',
                'message' => 'Public organization is unavailable.',
                // Shared Phase-2 error contract.
                'error' => [
                    'code' => 'public_organization_unavailable',
                    'http_status' => 503,
                    'message' => 'Public organization is unavailable.',
                    'details' => null,
                ],
            ], 503);
        }

        $this->publicTenantResolver->recordIgnoredClientOrganizationInput($validated, $publicTenant);

        $result = $this->researchAgent->conductResearch($publicTenant->organizationId, $validated);

        return response()->json($result);
    }

    public function plan(Request $request): JsonResponse
    {
        if (! config('wsa.research_agent.enabled', true)) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Agricultural research agent is disabled.',
            ], 503);
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'domain' => ['nullable', 'string', 'max:128'],
            'agricultural_domain' => ['nullable', 'string', 'max:128'],
            'entities' => ['nullable', 'array'],
            'entities.*.type' => ['required_with:entities', 'string', 'max:64'],
            'entities.*.value' => ['required_with:entities', 'string', 'max:255'],
            'entities.*.label' => ['nullable', 'string', 'max:255'],
            'selected_crop_id' => ['nullable', 'string', 'max:64'],
            'selected_crop_name' => ['nullable', 'string', 'max:255'],
            'selected_category_id' => ['nullable', 'string', 'max:64'],
            'selected_category_name' => ['nullable', 'string', 'max:255'],
            'knowledge_option' => ['nullable', 'string', 'max:64'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'constraints' => ['nullable', 'array'],
            'location' => ['nullable', 'string', 'max:255'],
            'research_intent' => ['nullable', 'string', 'max:64'],
        ]);

        $result = $this->researchAgent->planResearch($validated);

        return response()->json($result);
    }

    public function search(Request $request): JsonResponse
    {
        if (! config('wsa.research_agent.enabled', true)) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Agricultural research agent is disabled.',
            ], 503);
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'domain' => ['nullable', 'string', 'max:128'],
            'agricultural_domain' => ['nullable', 'string', 'max:128'],
            'entities' => ['nullable', 'array'],
            'entities.*.type' => ['required_with:entities', 'string', 'max:64'],
            'entities.*.value' => ['required_with:entities', 'string', 'max:255'],
            'entities.*.label' => ['nullable', 'string', 'max:255'],
            'selected_crop_id' => ['nullable', 'string', 'max:64'],
            'selected_crop_name' => ['nullable', 'string', 'max:255'],
            'selected_category_id' => ['nullable', 'string', 'max:64'],
            'selected_category_name' => ['nullable', 'string', 'max:255'],
            'knowledge_option' => ['nullable', 'string', 'max:64'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'constraints' => ['nullable', 'array'],
            'location' => ['nullable', 'string', 'max:255'],
            'research_intent' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $result = $this->researchAgent->searchResearch($validated);

        return response()->json($result);
    }

    public function validate(Request $request): JsonResponse
    {
        if (! config('wsa.research_agent.enabled', true)) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Agricultural research agent is disabled.',
            ], 503);
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'domain' => ['nullable', 'string', 'max:128'],
            'agricultural_domain' => ['nullable', 'string', 'max:128'],
            'entities' => ['nullable', 'array'],
            'entities.*.type' => ['required_with:entities', 'string', 'max:64'],
            'entities.*.value' => ['required_with:entities', 'string', 'max:255'],
            'entities.*.label' => ['nullable', 'string', 'max:255'],
            'selected_crop_id' => ['nullable', 'string', 'max:64'],
            'selected_crop_name' => ['nullable', 'string', 'max:255'],
            'selected_category_id' => ['nullable', 'string', 'max:64'],
            'selected_category_name' => ['nullable', 'string', 'max:255'],
            'knowledge_option' => ['nullable', 'string', 'max:64'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'constraints' => ['nullable', 'array'],
            'location' => ['nullable', 'string', 'max:255'],
            'research_intent' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'force_execute' => ['nullable', 'boolean'],
        ]);

        $result = $this->researchAgent->validateResearch($validated);

        return response()->json($result);
    }

    public function synthesize(Request $request): JsonResponse
    {
        if (! config('wsa.research_agent.enabled', true)) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Agricultural research agent is disabled.',
            ], 503);
        }

        $validated = $request->validate([
            // Accepted for client compatibility only — never authoritative for tenant selection (MODEL B).
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
            'query' => ['required', 'string', 'max:2000'],
            'domain' => ['nullable', 'string', 'max:128'],
            'agricultural_domain' => ['nullable', 'string', 'max:128'],
            'entities' => ['nullable', 'array'],
            'entities.*.type' => ['required_with:entities', 'string', 'max:64'],
            'entities.*.value' => ['required_with:entities', 'string', 'max:255'],
            'entities.*.label' => ['nullable', 'string', 'max:255'],
            'selected_crop_id' => ['nullable', 'string', 'max:64'],
            'selected_crop_name' => ['nullable', 'string', 'max:255'],
            'selected_category_id' => ['nullable', 'string', 'max:64'],
            'selected_category_name' => ['nullable', 'string', 'max:255'],
            'knowledge_option' => ['nullable', 'string', 'max:64'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'constraints' => ['nullable', 'array'],
            'location' => ['nullable', 'string', 'max:255'],
            'research_intent' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'force_execute' => ['nullable', 'boolean'],
        ]);

        try {
            $publicTenant = $this->publicTenantResolver->bindPublicTenant();
        } catch (PublicTenantResolutionException) {
            return response()->json([
                // Legacy field (Home consumers).
                'status' => 'public_organization_unavailable',
                'message' => 'Public organization is unavailable.',
                // Shared Phase-2 error contract.
                'error' => [
                    'code' => 'public_organization_unavailable',
                    'http_status' => 503,
                    'message' => 'Public organization is unavailable.',
                    'details' => null,
                ],
            ], 503);
        }

        $this->publicTenantResolver->recordIgnoredClientOrganizationInput($validated, $publicTenant);

        $result = $this->researchAgent->synthesizeResearch($publicTenant->organizationId, $validated);

        return response()->json($result);
    }
}
