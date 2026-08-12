<?php

namespace App\Services\Ai;

class AiRequestValidator
{
    private const ALLOWED_TYPES = [
        'diagnosis',
        'library_summary',
        'library_qa',
        'training_assistance',
        'cv_parse',
        'job_match',
        'assistant',
        'vision_analysis',
    ];

    /** @return array<string, mixed> */
    public function validate(string $requestType, array $input): array
    {
        abort_unless(
            in_array($requestType, self::ALLOWED_TYPES, true),
            422,
            'Unsupported AI request type.'
        );

        abort_if(empty($input), 422, 'AI input payload is required.');

        return match ($requestType) {
            'diagnosis' => $this->validateDiagnosisInput($input),
            'library_summary', 'library_qa' => $this->validateLibraryInput($input),
            'training_assistance' => $this->validateTrainingInput($input),
            'cv_parse' => $this->validateCvParseInput($input),
            'job_match' => $this->validateJobMatchInput($input),
            'assistant' => $this->validateAssistantInput($input),
            'vision_analysis' => $this->validateVisionInput($input),
            default => $input,
        };
    }

    /** @param  array<string, mixed>  $input */
    private function validateDiagnosisInput(array $input): array
    {
        abort_unless(
            isset($input['notes']) || isset($input['symptom_ids']) || isset($input['subject_id']),
            422,
            'Diagnosis input requires notes, symptoms, or a subject.'
        );

        return $input;
    }

    /** @param  array<string, mixed>  $input */
    private function validateLibraryInput(array $input): array
    {
        abort_unless(
            isset($input['title']) || isset($input['query']) || isset($input['content']),
            422,
            'Library AI input requires title, query, or content.'
        );

        return $input;
    }

    /** @param  array<string, mixed>  $input */
    private function validateTrainingInput(array $input): array
    {
        abort_unless(
            isset($input['lesson_title']) || isset($input['question']) || isset($input['course_code']),
            422,
            'Training AI input requires lesson, question, or course context.'
        );

        return $input;
    }

    /** @param  array<string, mixed>  $input */
    private function validateCvParseInput(array $input): array
    {
        abort_unless(isset($input['cv_path']), 422, 'CV path is required for parsing.');

        return $input;
    }

    /** @param  array<string, mixed>  $input */
    private function validateJobMatchInput(array $input): array
    {
        abort_unless(isset($input['requirements']) && is_array($input['requirements']), 422, 'Job requirements are required.');

        return $input;
    }

    /** @param  array<string, mixed>  $input */
    private function validateAssistantInput(array $input): array
    {
        abort_unless(isset($input['message']) && is_string($input['message']), 422, 'Assistant message is required.');

        return $input;
    }

    /** @param  array<string, mixed>  $input */
    private function validateVisionInput(array $input): array
    {
        abort_unless(isset($input['image_path']) || isset($input['image_url']), 422, 'Image path or URL is required.');

        return $input;
    }
}
