<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;

class MockAiProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'mock';
    }

    public function model(): string
    {
        return (string) config('ai.models.mock', config('ai.model', 'mock-v1'));
    }

    public function complete(string $requestType, array $input): array
    {
        $payload = match ($requestType) {
            'diagnosis' => [
                'title' => 'Possible early blight (decision support)',
                'summary' => 'Demo mock analysis based on submitted symptoms. This is agricultural decision support only and is not a definitive scientific diagnosis.',
                'confidence_score' => 72.5,
                'severity' => 'medium',
                'priority' => 'high',
                'recommendations' => [
                    [
                        'title' => 'Inspect lower leaves',
                        'recommendation' => 'Remove affected leaves and monitor spread over 7 days.',
                        'category' => 'monitoring',
                        'priority' => 'medium',
                    ],
                ],
            ],
            'library_summary' => [
                'summary' => 'Demo summary generated for library content review.',
                'sources' => $input['sources'] ?? [],
            ],
            'library_qa' => [
                'answer' => 'Demo answer for agricultural library question. This is decision support only.',
                'sources' => [
                    ['title' => 'Demo library article', 'reference' => 'published-tomato-guide'],
                ],
            ],
            'training_assistance' => [
                'guidance' => 'Review the lesson objectives and apply the recommended field practices.',
                'suggestions' => [
                    'Revisit irrigation scheduling notes.',
                    'Complete the quiz to confirm understanding.',
                ],
            ],
            'cv_parse' => [
                'status' => 'completed',
                'confidence' => 0.55,
                'message' => 'CV parsing provider not configured. Structured extraction unavailable.',
                'skills' => $input['suggested_skills'] ?? [],
                'experience' => $input['suggested_experience'] ?? [],
                'education' => $input['suggested_education'] ?? [],
                'specialization' => $input['suggested_specialization'] ?? null,
            ],
            'job_match' => [
                'matches' => collect($input['candidates'] ?? [])->take($input['limit'] ?? 10)->values()->map(fn (array $candidate, int $index) => [
                    'talent_profile_id' => $candidate['id'] ?? null,
                    'score' => max(10, 90 - ($index * 8)),
                    'confidence' => 0.5,
                    'explanation' => 'Mock match based on available profile metadata. Configure a real provider for explainable matching.',
                ])->all(),
            ],
            'assistant' => [
                'reply' => 'WSA assistant foundation response. Configure an AI provider for domain-aware answers.',
                'confidence' => 0.4,
                'domain' => $input['domain'] ?? 'platform',
                'requires_more_information' => true,
                'sources' => $input['sources'] ?? [],
            ],
            'vision_analysis' => [
                'status' => 'completed',
                'confidence' => 0.35,
                'summary' => 'Vision analysis provider not configured. This is not a diagnostic conclusion.',
                'recommendation' => 'Provide additional images or escalate to a human expert.',
                'requires_more_information' => true,
                'escalate_to_expert' => true,
            ],
            'plant_vision_analysis' => [
                'status' => 'completed',
                'image_quality' => 'adequate',
                'plant_visible' => true,
                'symptoms_visible' => true,
                'quality_notes' => [],
                'observations' => [
                    [
                        'id' => 'obs-1',
                        'type' => 'leaf_spot',
                        'description' => 'Brown circular lesions with yellow halos on lower leaves.',
                        'location' => 'lower leaves',
                        'severity_hint' => 'moderate',
                        'observation_confidence' => 0.72,
                        'supporting_cues' => ['spot', 'lesion', 'yellow halo'],
                    ],
                    [
                        'id' => 'obs-2',
                        'type' => 'chlorosis',
                        'description' => 'Mild yellowing surrounding lesion margins.',
                        'location' => 'foliage',
                        'severity_hint' => 'low',
                        'observation_confidence' => 0.55,
                        'supporting_cues' => ['yellowing', 'chlorosis'],
                    ],
                ],
            ],
            default => [
                'message' => 'Mock AI response for '.$requestType,
            ],
        };

        return array_merge($payload, [
            'provider' => $this->name(),
            'model' => $this->model(),
            'tokens_used' => 0,
            'finish_reason' => 'stop',
        ]);
    }
}
