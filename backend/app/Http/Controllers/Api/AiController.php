<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\StoreAiRequestRequest;
use App\Models\AiRequest;
use App\Services\Ai\AiQuotaService;
use App\Services\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private AiService $aiService,
        private AiQuotaService $quotaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'ai.use');

        $query = AiRequest::query()
            ->select([
                'id', 'organization_id', 'user_id', 'request_type', 'source_type',
                'source_id', 'provider', 'status', 'latency_ms', 'tokens_used',
                'error_message', 'cancelled_at', 'created_at', 'updated_at',
            ])
            ->latest();

        if ($type = $request->query('request_type')) {
            $query->where('request_type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json(
            $query->paginate((int) $request->query('per_page', 15))
                ->through(fn (AiRequest $record) => $this->sanitizeAiRequest($record))
        );
    }

    public function store(StoreAiRequestRequest $request): JsonResponse
    {
        $this->authorizePermission($request, 'ai.use');

        $data = $request->validated();
        $organizationId = $this->organization($request);

        if (config('ai.async_dispatch')) {
            $record = $this->aiService->dispatchForProcessing(
                $organizationId,
                $data['request_type'],
                $data['input'],
                $request->user()->id,
                $data['source_type'] ?? null,
                $data['source_id'] ?? null,
            );

            return response()->json($this->sanitizeAiRequest($record), 202);
        }

        $record = $this->aiService->run(
            $organizationId,
            $data['request_type'],
            $data['input'],
            $request->user()->id,
            $data['source_type'] ?? null,
            $data['source_id'] ?? null,
        );

        return response()->json($this->sanitizeAiRequest($record), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizePermission($request, 'ai.use');

        $record = AiRequest::query()->findOrFail($id);

        return response()->json($this->sanitizeAiRequest($record));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $this->authorizePermission($request, 'ai.use');

        $record = AiRequest::query()->findOrFail($id);

        abort_unless($record->isCancellable(), 422, 'This AI request cannot be cancelled.');

        $record = $this->aiService->cancel($record, $request->user()->id);

        return response()->json($this->sanitizeAiRequest($record));
    }

    public function usage(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'ai.use');

        return response()->json(
            $this->quotaService->summaryForOrganization($this->organization($request))
        );
    }

    public function provider(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'ai.use');

        $organizationId = $this->organization($request);

        return response()->json([
            'provider' => $this->aiService->providerName($organizationId),
            'decision_support_notice' => 'AI outputs are agricultural decision support only and are not authoritative diagnoses.',
            'supported_request_types' => ['diagnosis', 'library_summary', 'library_qa', 'training_assistance'],
            'async_dispatch' => (bool) config('ai.async_dispatch', false),
            'quota' => $this->quotaService->summaryForOrganization($organizationId),
        ]);
    }

    private function sanitizeAiRequest(AiRequest $record): AiRequest
    {
        $record->makeHidden(['input']);

        return $record;
    }
}
