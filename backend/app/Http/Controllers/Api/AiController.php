<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiRequest;
use App\Services\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(private AiService $aiService) {}

    private function organization(Request $request): int
    {
        return $request->user()->organizations()->firstOrFail()->id;
    }

    public function index(Request $request): JsonResponse
    {
        $query = AiRequest::where('organization_id', $this->organization($request))->latest();

        if ($type = $request->query('request_type')) {
            $query->where('request_type', $type);
        }

        return response()->json($query->paginate((int) $request->query('per_page', 15)));
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

        return response()->json($record, 201);
    }

    public function provider(): JsonResponse
    {
        return response()->json([
            'provider' => $this->aiService->providerName(),
            'decision_support_notice' => 'AI outputs are agricultural decision support only and are not authoritative diagnoses.',
        ]);
    }
}
