<?php

namespace App\Http\Requests\Ai;

use App\Services\Ai\Retrieval\KnowledgeRetrievalConfig;
use App\Services\Ai\Retrieval\KnowledgeRetrievalOperationsService;
use Illuminate\Foundation\Http\FormRequest;

class AiRetrievalTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'strategy' => ['nullable', 'string', 'in:'.implode(',', KnowledgeRetrievalConfig::STRATEGIES)],
            'status' => ['nullable', 'string', 'in:ok,empty,failed,fallback,disabled'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.KnowledgeRetrievalOperationsService::TELEMETRY_MAX_LIMIT],
            'organization_id' => ['prohibited'],
            'tenant_id' => ['prohibited'],
            'api_key' => ['prohibited'],
        ];
    }
}
