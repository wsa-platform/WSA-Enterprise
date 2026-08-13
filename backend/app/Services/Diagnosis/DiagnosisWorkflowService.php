<?php

namespace App\Services\Diagnosis;

use App\Models\DiagnosisRecommendation;
use App\Models\DiagnosisRequest;
use App\Models\DiagnosisResult;
use App\Models\User;
use App\Services\Ai\AiService;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Support\Facades\DB;

class DiagnosisWorkflowService
{
    public function __construct(
        private AiService $aiService,
        private ServiceOwnershipAuthorizer $ownership,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function submit(int $organizationId, int $userId, array $data): DiagnosisRequest
    {
        $owner = User::findOrFail($userId);

        return DB::transaction(function () use ($organizationId, $owner, $data) {
            $request = $this->ownership->createOwnedModel(DiagnosisRequest::class, [
                'organization_id' => $organizationId,
                'user_id' => $owner->id,
                'field_id' => $data['field_id'] ?? null,
                'block_id' => $data['block_id'] ?? null,
                'crop_type_id' => $data['crop_type_id'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'reference' => $data['reference'],
                'status' => 'submitted',
                'notes' => $data['notes'] ?? null,
                'image_disk' => $data['image_disk'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'symptom_ids' => $data['symptom_ids'] ?? null,
            ], $owner);

            $aiRequest = $this->aiService->run(
                $organizationId,
                'diagnosis',
                [
                    'reference' => $request->reference,
                    'notes' => $request->notes,
                    'symptom_ids' => $request->symptom_ids,
                    'image_path' => $request->image_path,
                ],
                $owner->id,
                DiagnosisRequest::class,
                $request->id,
            );

            if ($aiRequest->status !== 'completed') {
                $request->update(['status' => 'failed']);

                return $request->fresh();
            }

            $output = $aiRequest->output ?? [];

            $result = $this->ownership->createOwnedModel(DiagnosisResult::class, [
                'organization_id' => $organizationId,
                'diagnosis_request_id' => $request->id,
                'disease_id' => $data['disease_id'] ?? null,
                'title' => $output['title'] ?? 'Decision support result',
                'summary' => $output['summary'] ?? 'No summary available.',
                'confidence_score' => $output['confidence_score'] ?? 0,
                'severity' => $output['severity'] ?? 'medium',
                'priority' => $output['priority'] ?? 'medium',
                'provider' => $aiRequest->provider,
                'is_decision_support' => true,
                'raw_response' => $output,
            ], $owner);

            foreach ($output['recommendations'] ?? [] as $recommendation) {
                $this->ownership->createOwnedModel(DiagnosisRecommendation::class, [
                    'organization_id' => $organizationId,
                    'diagnosis_result_id' => $result->id,
                    'title' => $recommendation['title'],
                    'recommendation' => $recommendation['recommendation'],
                    'category' => $recommendation['category'] ?? null,
                    'priority' => $recommendation['priority'] ?? 'medium',
                    'status' => 'open',
                ], $owner);
            }

            $request->update(['status' => 'completed']);

            return $request->fresh()->load(['results.recommendations']);
        });
    }
}
