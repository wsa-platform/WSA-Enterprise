<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Agriculture\Diagnosis\DiagnosisStatus;
use App\Services\Agriculture\Diagnosis\PlantAiDiagnosisEngine;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public Stage 6 Plant AI Diagnosis API.
 * Invokes PlantAiDiagnosisEngine only — never the Research Agent stack.
 */
class PlantAiDiagnosisController extends Controller
{
    public function __construct(
        private PlantAiDiagnosisEngine $engine,
    ) {}

    public function analyze(Request $request): JsonResponse
    {
        if (! config('wsa.plant_diagnosis.enabled', true)) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Plant AI Diagnosis engine is disabled.',
            ], 503);
        }

        $validated = $request->validate([
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
            'image' => ['nullable', 'file', 'max:5120'],
            'image_base64' => ['nullable', 'string'],
            'image_name' => ['nullable', 'string', 'max:255'],
            'image_mime' => ['nullable', 'string', 'max:64'],
            'plant_name' => ['nullable', 'string', 'max:255'],
            'plant' => ['nullable', 'string', 'max:255'],
            'crop_type' => ['nullable', 'string', 'max:255'],
            'crop' => ['nullable', 'string', 'max:255'],
            'growth_stage' => ['nullable', 'string', 'max:128'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'symptoms' => ['nullable', 'array', 'max:30'],
            'symptoms.*' => ['string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:16'],
            'allow_management_guidance' => ['nullable', 'boolean'],
            // Explicitly reject path-based image references at the HTTP boundary.
            'image_path' => ['prohibited'],
            'storage_path' => ['prohibited'],
            'file_path' => ['prohibited'],
            'path' => ['prohibited'],
        ]);

        if (! isset($validated['image']) && empty($validated['image_base64'])) {
            return response()->json([
                'status' => DiagnosisStatus::INVALID_INPUT,
                'message' => 'An image file or image_base64 payload is required.',
                'stage' => 6,
                'engine' => 'plant_ai_diagnosis',
                'independent_of_research_agent' => true,
            ], 422);
        }

        $organizationId = null;
        if (isset($validated['organization_id']) || isset($validated['organization'])) {
            try {
                $organizationId = $this->resolveOrganization($validated)->id;
            } catch (ModelNotFoundException) {
                return response()->json([
                    'status' => 'organization_not_found',
                    'message' => 'Organization not found.',
                ], 404);
            }
        }

        $payload = array_merge($validated, [
            'organization_id' => $organizationId,
        ]);

        $result = $this->engine->diagnose($payload);
        $statusCode = match ($result->status) {
            DiagnosisStatus::INVALID_INPUT => 422,
            DiagnosisStatus::ANALYSIS_UNAVAILABLE => 503,
            default => 200,
        };

        return response()->json($result->toArray(), $statusCode);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveOrganization(array $validated): Organization
    {
        if (isset($validated['organization_id'])) {
            return Organization::query()->findOrFail($validated['organization_id']);
        }

        $slug = (string) ($validated['organization'] ?? config('wsa.public_organization_slug', 'wsa-demo'));
        $organization = Organization::query()->where('slug', $slug)->first();
        if ($organization !== null) {
            return $organization;
        }

        return Organization::query()->orderBy('id')->firstOrFail();
    }
}
