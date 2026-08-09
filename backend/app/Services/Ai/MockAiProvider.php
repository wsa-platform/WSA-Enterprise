<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;

class MockAiProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'mock';
    }

    public function complete(string $requestType, array $input): array
    {
        return match ($requestType) {
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
            ],
            default => [
                'message' => 'Mock AI response for '.$requestType,
            ],
        };
    }
}
