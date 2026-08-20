<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class AiKnowledgeIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source_type' => ['nullable', 'string', 'in:library_items'],
            'slug' => ['required', 'string', 'max:128'],
            'title' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'content' => ['nullable', 'string', 'max:100000'],
            'body' => ['nullable', 'string', 'max:100000'],
            'source' => ['nullable', 'string', 'max:255'],
            'publication_status' => ['nullable', 'string', 'in:draft,published'],
            'organization_id' => ['prohibited'],
            'tenant_id' => ['prohibited'],
            'api_key' => ['prohibited'],
            'provider' => ['prohibited'],
            'model' => ['prohibited'],
            'authorization' => ['prohibited'],
        ];
    }
}
