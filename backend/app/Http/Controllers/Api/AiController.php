<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Models\AiRequest;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\LibraryItem;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Services\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    use ResolvesOrganization;

    public function __construct(private AiService $aiService) {}

    public function index(Request $request): JsonResponse
    {
        $query = AiRequest::where('organization_id', $this->organization($request))
            ->select([
                'id', 'organization_id', 'user_id', 'request_type', 'source_type',
                'source_id', 'provider', 'status', 'latency_ms', 'tokens_used',
                'error_message', 'created_at', 'updated_at',
            ])
            ->latest();

        if ($type = $request->query('request_type')) {
            $query->where('request_type', $type);
        }

        return response()->json(
            $query->paginate((int) $request->query('per_page', 15))
                ->through(fn (AiRequest $record) => $this->sanitizeAiRequest($record))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'request_type' => ['required', 'string', 'max:64'],
            'input' => ['required', 'array'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer'],
        ]);

        $record = $this->aiService->run(
            $this->organization($request),
            $data['request_type'],
            $data['input'],
            $request->user()->id,
            $data['source_type'] ?? null,
            $data['source_id'] ?? null,
        );

        return response()->json($this->sanitizeAiRequest($record), 201);
    }

    public function provider(): JsonResponse
    {
        return response()->json([
            'provider' => $this->aiService->providerName(),
            'decision_support_notice' => 'AI outputs are agricultural decision support only and are not authoritative diagnoses.',
            'supported_request_types' => ['diagnosis', 'library_summary', 'library_qa', 'training_assistance'],
        ]);
    }

    private function sanitizeAiRequest(AiRequest $record): AiRequest
    {
        $record->makeHidden(['input']);

        return $record;
    }
}
