<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class AiKnowledgeBackfillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'organization_id' => ['prohibited'],
            'tenant_id' => ['prohibited'],
            'api_key' => ['prohibited'],
        ];
    }
}
