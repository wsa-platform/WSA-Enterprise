<?php

namespace App\Services\Agriculture\Diagnosis\Vision;

use App\Services\Agriculture\Diagnosis\PlantDiagnosisRequest;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Provider-agnostic vision analysis contract for Plant AI Diagnosis.
 */
interface VisionAnalysisProviderInterface
{
    /**
     * Analyze validated plant imagery and return observations (not diagnoses).
     *
     * @return array{
     *     image_quality: string,
     *     plant_visible: bool,
     *     symptoms_visible: bool,
     *     quality_notes: list<string>,
     *     observations: list<VisionObservation>,
     *     provider: string,
     *     model: string|null,
     *     raw_status: string
     * }
     */
    public function analyze(PlantDiagnosisRequest $request): array;
}
