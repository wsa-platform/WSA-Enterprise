<?php

namespace App\Services\Agriculture\Diagnosis\Vision;

use App\Services\Agriculture\Diagnosis\DiagnosisStatus;
use App\Services\Agriculture\Diagnosis\PlantDiagnosisRequest;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Orchestrates vision observation extraction and image-quality gating.
 */
class VisionAnalysisService
{
    /** @var list<string> */
    private const INSUFFICIENT_QUALITIES = [
        'inadequate',
        'blurry',
        'poor_lighting',
        'too_dark',
        'too_bright',
        'out_of_frame',
        'not_a_plant',
        'corrupted',
        'insufficient',
    ];

    public function __construct(
        private readonly VisionAnalysisProviderInterface $provider,
    ) {}

    /**
     * @return array{
     *     status: string|null,
     *     message: string|null,
     *     image_quality: string,
     *     plant_visible: bool,
     *     symptoms_visible: bool,
     *     quality_notes: list<string>,
     *     observations: list<VisionObservation>,
     *     provider: string,
     *     model: string|null
     * }
     */
    public function analyze(PlantDiagnosisRequest $request): array
    {
        $analysis = $this->provider->analyze($request);

        $quality = strtolower((string) ($analysis['image_quality'] ?? 'adequate'));
        $plantVisible = (bool) ($analysis['plant_visible'] ?? false);
        $symptomsVisible = (bool) ($analysis['symptoms_visible'] ?? false);
        /** @var list<VisionObservation> $observations */
        $observations = $analysis['observations'] ?? [];

        $insufficient = in_array($quality, self::INSUFFICIENT_QUALITIES, true)
            || ! $plantVisible
            || ($observations === [] && ! $symptomsVisible);

        if ($insufficient) {
            return [
                'status' => DiagnosisStatus::INSUFFICIENT_IMAGE,
                'message' => 'Image quality or content is insufficient for reliable plant diagnosis observations.',
                'image_quality' => $quality,
                'plant_visible' => $plantVisible,
                'symptoms_visible' => $symptomsVisible,
                'quality_notes' => $analysis['quality_notes'] ?? [],
                'observations' => $observations,
                'provider' => (string) ($analysis['provider'] ?? 'unknown'),
                'model' => $analysis['model'] ?? null,
            ];
        }

        return [
            'status' => null,
            'message' => null,
            'image_quality' => $quality,
            'plant_visible' => $plantVisible,
            'symptoms_visible' => $symptomsVisible,
            'quality_notes' => $analysis['quality_notes'] ?? [],
            'observations' => $observations,
            'provider' => (string) ($analysis['provider'] ?? 'unknown'),
            'model' => $analysis['model'] ?? null,
        ];
    }
}
