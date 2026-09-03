<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgriculturalResearchAgentController extends Controller
{
    public function __construct(
        private AgriculturalResearchAgent $researchAgent,
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
            'organization' => ['required_without:organization_id', 'string', 'max:255'],
            'organization_id' => ['required_without:organization', 'integer'],
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
            $organization = $this->resolveOrganization($validated);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => 'organization_not_found',
                'message' => 'Organization not found.',
            ], 404);
        }

        $result = $this->researchAgent->conductResearch($organization->id, $validated);

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
            'organization' => ['required_without:organization_id', 'string', 'max:255'],
            'organization_id' => ['required_without:organization', 'integer'],
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
            $organization = $this->resolveOrganization($validated);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => 'organization_not_found',
                'message' => 'Organization not found.',
            ], 404);
        }

        $result = $this->researchAgent->synthesizeResearch($organization->id, $validated);

        return response()->json($result);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveOrganization(array $validated): Organization
    {
        if (isset($validated['organization_id'])) {
            return Organization::query()->findOrFail($validated['organization_id']);
        }

        if (isset($validated['organization'])) {
            $organization = Organization::query()->where('slug', (string) $validated['organization'])->first();
            if ($organization === null) {
                throw (new ModelNotFoundException)->setModel(Organization::class);
            }

            return $organization;
        }

        $slug = (string) config('wsa.public_organization_slug', 'wsa-demo');
        $organization = Organization::query()->where('slug', $slug)->first();
        if ($organization !== null) {
            return $organization;
        }

        return Organization::query()->orderBy('id')->firstOrFail();
    }
}
