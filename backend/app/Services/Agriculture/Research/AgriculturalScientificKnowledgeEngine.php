<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\CropKnowledgeContext;
use App\Services\Agriculture\CropKnowledgeEngine;
use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\ScientificSourceDiscoveryPipeline;
use App\Services\Agriculture\ScientificSourceValidator;

/**
 * Central scientific evidence/knowledge substrate.
 * Preserves CropKnowledgeEngine behavior via delegation.
 */
class AgriculturalScientificKnowledgeEngine
{
    public function __construct(
        private CropKnowledgeEngine $cropKnowledgeEngine,
        private ScientificSourceDiscoveryPipeline $discoveryPipeline,
        private ScientificSourceValidator $sourceValidator,
    ) {}

    public function execute(int $organizationId, AgriculturalResearchPlan $plan): AgriculturalResearchResult
    {
        if ($plan->isCropProfileIntent()) {
            return $this->executeCropProfile($organizationId, $plan);
        }

        return $this->executeGenericResearch($organizationId, $plan);
    }

    private function executeCropProfile(int $organizationId, AgriculturalResearchPlan $plan): AgriculturalResearchResult
    {
        $profile = $this->cropKnowledgeEngine->buildProfile($organizationId, $plan->contextInput);
        $libraryMeta = is_array($profile['library'] ?? null) ? $profile['library'] : [];
        $discoverersUsed = is_array($libraryMeta['discoverers_used'] ?? null)
            ? $libraryMeta['discoverers_used']
            : [];

        return new AgriculturalResearchResult(
            researchContext: $profile,
            planSummary: $plan->toArray(),
            discoverersUsed: $discoverersUsed,
            externalDiscoverersUsed: $this->filterExternalDiscoverers($discoverersUsed),
            libraryDiscoverersUsed: $this->filterLibraryDiscoverers($discoverersUsed),
            status: (string) ($profile['load_state'] ?? 'completed'),
        );
    }

    private function executeGenericResearch(int $organizationId, AgriculturalResearchPlan $plan): AgriculturalResearchResult
    {
        $context = $this->genericContextFromPlan($plan);
        $sectionKeys = $plan->researchSections;

        $discovery = $this->discoveryPipeline->discoverMissingSections(
            $organizationId,
            $context,
            $sectionKeys,
        );

        $sections = [];
        foreach ($sectionKeys as $key) {
            $section = $discovery['sections'][$key] ?? null;
            if (is_array($section) && $this->sourceValidator->isVerifiedSection($section)) {
                $sections[] = [
                    'key' => $key,
                    'title' => $key,
                    'content' => (string) ($section['content'] ?? ''),
                    'source' => is_array($section['source'] ?? null) ? $section['source'] : null,
                    'verified' => true,
                ];
                continue;
            }

            $sections[] = [
                'key' => $key,
                'title' => $key,
                'content' => ScientificSourceValidator::UNCERTAINTY_MESSAGE,
                'source' => null,
                'verified' => false,
            ];
        }

        $discoverersUsed = is_array($discovery['discoverers_used'] ?? null) ? $discovery['discoverers_used'] : [];
        $references = [];
        foreach ($sections as $section) {
            if (is_array($section['source'] ?? null) && $this->sourceValidator->isVerifiedSource($section['source'])) {
                $references[] = $section['source'];
            }
        }

        $researchContext = [
            'query' => $plan->userQuery,
            'agricultural_domain' => $plan->agriculturalDomain,
            'intent' => $plan->intent,
            'entities' => $plan->entities,
            'sections' => $sections,
            'references' => $references,
            'load_state' => $sections !== [] && collect($sections)->contains(fn (array $s): bool => (bool) ($s['verified'] ?? false))
                ? 'scientific_generated'
                : 'insufficient_verified_sources',
            'library' => [
                'discoverers_used' => $discoverersUsed,
                'retrieval_failed' => (bool) ($discovery['retrieval_failed'] ?? false),
            ],
        ];

        return new AgriculturalResearchResult(
            researchContext: $researchContext,
            planSummary: $plan->toArray(),
            discoverersUsed: $discoverersUsed,
            externalDiscoverersUsed: is_array($discovery['external_discoverers_used'] ?? null)
                ? $discovery['external_discoverers_used']
                : $this->filterExternalDiscoverers($discoverersUsed),
            libraryDiscoverersUsed: is_array($discovery['library_discoverers_used'] ?? null)
                ? $discovery['library_discoverers_used']
                : $this->filterLibraryDiscoverers($discoverersUsed),
            status: (string) ($researchContext['load_state'] ?? 'completed'),
        );
    }

    private function genericContextFromPlan(AgriculturalResearchPlan $plan): CropKnowledgeContext
    {
        $entityCrop = collect($plan->entities)->firstWhere('type', 'crop');
        $entityScientific = collect($plan->entities)->firstWhere('type', 'scientific_name');

        return CropKnowledgeContext::fromArray([
            'selected_crop_id' => is_array($entityCrop) ? (string) ($entityCrop['value'] ?? 'generic') : 'generic',
            'selected_crop_name' => is_array($entityCrop) ? (string) ($entityCrop['label'] ?? $entityCrop['value'] ?? 'generic') : $plan->userQuery,
            'selected_category_id' => $plan->agriculturalDomain,
            'selected_category_name' => $plan->agriculturalDomain,
            'knowledge_option' => CropKnowledgeOptionCatalog::isImplemented('scientific-research')
                ? 'scientific-research'
                : 'farming-needs',
            'scientific_name' => is_array($entityScientific) ? (string) ($entityScientific['value'] ?? '') : '',
        ]);
    }

    /**
     * Backward-compatible direct access to crop profile building.
     *
     * @param  array<string, mixed>  $cropContextInput
     * @return array<string, mixed>
     */
    public function buildCropProfile(int $organizationId, array $cropContextInput): array
    {
        return $this->cropKnowledgeEngine->buildProfile($organizationId, $cropContextInput);
    }

    /**
     * @param  list<string>  $discoverersUsed
     * @return list<string>
     */
    private function filterExternalDiscoverers(array $discoverersUsed): array
    {
        return array_values(array_filter(
            $discoverersUsed,
            fn (string $name): bool => str_starts_with($name, 'external_'),
        ));
    }

    /**
     * @param  list<string>  $discoverersUsed
     * @return list<string>
     */
    private function filterLibraryDiscoverers(array $discoverersUsed): array
    {
        return array_values(array_filter(
            $discoverersUsed,
            fn (string $name): bool => str_starts_with($name, 'library_'),
        ));
    }
}
