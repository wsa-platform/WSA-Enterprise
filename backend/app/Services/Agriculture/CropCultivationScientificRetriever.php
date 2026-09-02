<?php

namespace App\Services\Agriculture;

/**
 * @deprecated Use ScientificSourceDiscoveryPipeline directly. Kept for backward compatibility.
 */
class CropCultivationScientificRetriever
{
    public function __construct(
        private ScientificSourceDiscoveryPipeline $pipeline,
    ) {}

    /**
     * @param  array{
     *   selected_crop_id: string,
     *   selected_crop_name: string,
     *   selected_category_id?: string,
     *   selected_category_name?: string,
     *   knowledge_option?: string
     * }  $cropContext
     * @param  list<string>  $missingKeys
     * @return array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>
     */
    public function retrieveMissingSections(int $organizationId, array $cropContext, array $missingKeys): array
    {
        $context = CropKnowledgeContext::fromArray($cropContext);
        $result = $this->pipeline->discoverMissingSections($organizationId, $context, $missingKeys);

        return $result['sections'];
    }
}
