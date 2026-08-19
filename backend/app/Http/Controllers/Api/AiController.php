<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\StoreAiRequestRequest;
use App\Models\AiRequest;
use App\Services\Ai\AiQuotaService;
use App\Services\Ai\AiService;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
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

        $organizationId = $this->organization($request);
        $query = app(ServiceOwnershipAuthorizer::class)->scopeAccessibleServices(
            AiRequest::query()
                ->where('organization_id', $organizationId)
                ->select([
                    'id', 'organization_id', 'user_id', 'owner_user_id', 'request_type', 'source_type',
                    'source_id', 'provider', 'status', 'latency_ms', 'tokens_used',
                    'error_message', 'cancelled_at', 'created_at', 'updated_at',
                ]),
            $request->user(),
            $organizationId,
        )->latest();

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
        $data = $request->validated();

        if ($data['request_type'] === 'vision_analysis') {
            $this->authorizeAnyPermission($request, ['ai.use', 'ai.vision']);
        } else {
            $this->authorizePermission($request, 'ai.use');
        }

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

        $record = $this->findAccessibleAiRequest($request, $id);

        return response()->json($this->sanitizeAiRequest($record));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $this->authorizePermission($request, 'ai.use');

        $record = $this->findAccessibleAiRequest($request, $id);

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
        $description = $this->aiService->providerDescription($organizationId);

        return response()->json([
            'provider' => $description['provider'],
            'model' => $description['model'],
            'requested_provider' => $description['requested_provider'],
            'fallback_provider' => $description['fallback_provider'],
            'used_fallback' => $description['used_fallback'],
            'decision_support_notice' => 'AI outputs are agricultural decision support only and are not authoritative diagnoses.',
            'supported_request_types' => ['diagnosis', 'library_summary', 'library_qa', 'training_assistance', 'assistant', 'vision_analysis', 'cv_parse', 'job_match'],
            'async_dispatch' => (bool) config('ai.async_dispatch', false),
            'quota' => $this->quotaService->summaryForOrganization($organizationId),
        ]);
    }

    private function sanitizeAiRequest(AiRequest $record): AiRequest
    {
        $record->makeHidden(['input']);

        return $record;
    }

    private function findAccessibleAiRequest(Request $request, int $id): AiRequest
    {
        $organizationId = $this->organization($request);
        $record = app(ServiceOwnershipAuthorizer::class)->scopeAccessibleServices(
            AiRequest::query()->where('organization_id', $organizationId),
            $request->user(),
            $organizationId,
        )->findOrFail($id);

        app(ServiceOwnershipAuthorizer::class)->assertOwnedByUser(
            $request->user(),
            $record,
            $organizationId,
        );

        return $record;
    }
}
