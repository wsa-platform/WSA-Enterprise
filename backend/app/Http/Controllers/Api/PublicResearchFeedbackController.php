<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Agriculture\Research\Feedback\PositiveResearchFeedbackService;
use App\Services\Tenancy\PublicTenantResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * R7 — Public research feedback (positive-only dataset persistence).
 */
class PublicResearchFeedbackController extends Controller
{
    public function __construct(
        private PositiveResearchFeedbackService $feedbackService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Compatibility only — never authoritative (MODEL B / R1).
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
            'polarity' => ['nullable', 'string', 'max:32'],
            'useful' => ['nullable'],
            'research_source' => ['nullable', 'string', 'in:home,crop'],
            'question' => ['nullable', 'string', 'max:2000'],
            'question_language' => ['nullable', 'string', 'max:8'],
            'answer_language' => ['nullable', 'string', 'max:8'],
            'ui_locale' => ['nullable', 'string', 'max:8'],
            'library_item_id' => ['nullable', 'integer'],
            'research_fingerprint' => ['nullable', 'string', 'max:64'],
            'what_worked' => ['nullable', 'string', 'max:2000'],
            'search_notes' => ['nullable', 'string', 'max:2000'],
            'source_selection_notes' => ['nullable', 'string', 'max:2000'],
            'evidence_notes' => ['nullable', 'string', 'max:2000'],
            'ranking_notes' => ['nullable', 'string', 'max:2000'],
            'composition_notes' => ['nullable', 'string', 'max:2000'],
            'client_meta' => ['nullable', 'array'],
        ]);

        try {
            $result = $this->feedbackService->record($validated);
        } catch (PublicTenantResolutionException) {
            return response()->json([
                'status' => 'public_organization_unavailable',
                'load_state' => 'public_organization_unavailable',
                'message' => 'Public organization is unavailable.',
                'error' => [
                    'code' => 'public_organization_unavailable',
                    'http_status' => 503,
                    'message' => 'Public organization is unavailable.',
                    'details' => null,
                ],
            ], 503);
        }

        $http = $result['persisted'] ? 201 : 200;

        return response()->json([
            'status' => $result['status'],
            'persisted' => $result['persisted'],
            'id' => $result['id'] ?? null,
            'reason' => $result['reason'] ?? null,
            'policy' => [
                'positive_feedback_only' => true,
                'mutates_scientific_behavior' => false,
            ],
        ], $http);
    }
}
