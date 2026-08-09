<?php

namespace App\Http\Requests\Diagnosis;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
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
        ];
    }
}
