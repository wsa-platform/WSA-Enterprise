<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiKnowledgeIngestRequest;
use App\Http\Requests\Ai\AiKnowledgeReindexRequest;
use App\Http\Requests\Ai\AiRetrievalTelemetryRequest;
use App\Services\Ai\Retrieval\KnowledgeRetrievalOperationsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiRetrievalOperationsController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(private KnowledgeRetrievalOperationsService $operations) {}

    public function health(Request $request): JsonResponse
    {
        $this->authorizeOperator($request);

        return ApiResponse::success($this->operations->health($this->organization($request)));
    }

    public function strategy(Request $request): JsonResponse
    {
        $this->authorizeOperator($request);

        return ApiResponse::success($this->operations->strategy());
    }

    public function quality(Request $request): JsonResponse
    {
        $this->authorizeOperator($request);

        return ApiResponse::success($this->operations->qualitySummary($this->organization($request)));
    }

    public function telemetry(AiRetrievalTelemetryRequest $request): JsonResponse
    {
        $this->authorizeOperator($request);

        return ApiResponse::success($this->operations->telemetrySummary(
            $this->organization($request),
            $request->validated(),
        ));
    }

    public function ingest(AiKnowledgeIngestRequest $request): JsonResponse
    {
        $this->authorizeOperator($request);
        $payload = $request->validated();
        $result = $this->operations->ingest($this->organization($request), $payload, (int) ($request->user()?->id ?? 0));

        return ApiResponse::success($result, ($result['action'] ?? '') === 'created' ? 201 : 200);
    }

    public function reindex(AiKnowledgeReindexRequest $request, int $id): JsonResponse
    {
        $this->authorizeOperator($request);

        return ApiResponse::success($this->operations->reindex(
            $this->organization($request),
            $id,
            (int) ($request->user()?->id ?? 0),
        ));
    }

    public function publish(AiKnowledgeReindexRequest $request, int $id): JsonResponse
    {
        $this->authorizeOperator($request);

        return ApiResponse::success($this->operations->publish(
            $this->organization($request),
            $id,
            (int) ($request->user()?->id ?? 0),
        ));
    }

    public function unpublish(AiKnowledgeReindexRequest $request, int $id): JsonResponse
    {
        $this->authorizeOperator($request);

        return ApiResponse::success($this->operations->unpublish(
            $this->organization($request),
            $id,
            (int) ($request->user()?->id ?? 0),
        ));
    }

    private function authorizeOperator(Request $request): void
    {
        $this->authorizePermission($request, 'access.manage');
    }
}
