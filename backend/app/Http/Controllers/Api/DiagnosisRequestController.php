<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{CropType, DiagnosisDisease, DiagnosisRequest, DiagnosisSubject, FarmBlock, FarmField};
use App\Services\Diagnosis\DiagnosisWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisRequestController extends Controller
{
    public function __construct(private DiagnosisWorkflowService $workflow) {}

    private function organization(Request $request): int
    {
        return $request->user()->organizations()->firstOrFail()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $query = DiagnosisRequest::where('organization_id', $this->organization($request))
            ->with(['results.recommendations', 'user:id,name,email'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate((int) $request->query('per_page', 15)));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $record = DiagnosisRequest::where('organization_id', $this->organization($request))
            ->with(['results.recommendations'])
            ->findOrFail($id);

        return response()->json($record);
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organization($request);
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:64'],
            'field_id' => ['nullable', 'integer', 'exists:farm_fields,id'],
            'block_id' => ['nullable', 'integer', 'exists:farm_blocks,id'],
            'crop_type_id' => ['nullable', 'integer', 'exists:crop_types,id'],
            'subject_id' => ['nullable', 'integer', 'exists:diagnosis_subjects,id'],
            'disease_id' => ['nullable', 'integer', 'exists:diagnosis_diseases,id'],
            'notes' => ['nullable', 'string'],
            'image_disk' => ['nullable', 'string', 'max:32'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'symptom_ids' => ['nullable', 'array'],
            'symptom_ids.*' => ['integer'],
        ]);

        AgriculturalScopeValidator::assert($organizationId, $data, [
            'field_id' => FarmField::class,
            'block_id' => FarmBlock::class,
            'crop_type_id' => CropType::class,
            'subject_id' => DiagnosisSubject::class,
            'disease_id' => DiagnosisDisease::class,
        ]);

        abort_unless(
            ! DiagnosisRequest::where('organization_id', $organizationId)->where('reference', $data['reference'])->exists(),
            422,
            'Reference already exists.'
        );

        $record = $this->workflow->submit($organizationId, $request->user()->id, $data);

        return response()->json($record, 201);
    }
}
