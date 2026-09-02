<?php

namespace App\Services\Agriculture;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;

/**
 * Backward-compatible facade over the agricultural research agent / knowledge engine.
 */
class FieldCropCultivationProfileService
{
    public function __construct(
        private AgriculturalResearchAgent $researchAgent,
    ) {}

    /**
     * @param  array{
     *   selected_crop_id: string,
     *   selected_crop_name: string,
     *   selected_category_id?: string,
     *   selected_category_name?: string,
     *   knowledge_option?: string
     * }  $cropContext
     * @return array<string, mixed>
     */
    public function getProfile(int $organizationId, array $cropContext): array
    {
        $cropContext['knowledge_option'] = $cropContext['knowledge_option']
            ?? $cropContext['service_option']
            ?? 'farming-needs';

        return $this->researchAgent->conductCropProfileResearch($organizationId, $cropContext);
    }
}
