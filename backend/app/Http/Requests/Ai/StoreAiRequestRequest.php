<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', 'max:64'],
            'input' => ['required', 'array'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer'],
        ];
    }
}
