<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\ScientificSourceRegistry;

/**
 * Deterministic v1 research planner — generic across agriculture domains.
 */
class ResearchPlanner
{
    /** @var list<string> */
    public const RESEARCH_SEQUENCE = [
        'external_scientific_search',
        'source_validation',
        'evidence_extraction',
        'library_memory_recall',
        'library_enrichment_gap_fill',
        'evidence_comparison_merge',
    ];

    /** @var array<string, string> */
    private const DOMAIN_KEYWORDS = [
        'crop_cultivation' => 'crop cultivation agronomy',
        'irrigation' => 'irrigation water management scheduling',
        'fertilization' => 'fertilization nutrient management soil fertility',
        'soil' => 'soil health fertility management',
        'plant_nutrition' => 'plant nutrition micronutrients macronutrients',
        'pests' => 'agricultural pests integrated pest management',
        'diseases' => 'plant diseases pathology crop protection',
        'yield' => 'crop yield productivity harvest',
        'varieties' => 'crop varieties cultivars breeding',
        'animal_production' => 'livestock animal production husbandry',
        'poultry' => 'poultry production broiler layer',
        'beekeeping' => 'beekeeping apiculture pollination',
        'aquaculture' => 'aquaculture fish farming fisheries',
        'feed' => 'animal feed nutrition formulation',
        'agricultural_economics' => 'agricultural economics farm economics',
        'agricultural_industries' => 'agricultural industries value chain processing',
        'scientific_publications' => 'scientific publications peer reviewed research',
    ];

    /**
     * @param  array<string, mixed>  $input
     */
    public function plan(array $input): AgriculturalResearchPlan
    {
        $query = trim((string) ($input['query'] ?? ''));
        $cropId = trim((string) ($input['selected_crop_id'] ?? ''));
        $cropName = trim((string) ($input['selected_crop_name'] ?? ''));
        $knowledgeOption = trim((string) ($input['knowledge_option'] ?? $input['service_option'] ?? 'farming-needs'));
        $explicitDomain = trim((string) ($input['domain'] ?? $input['agricultural_domain'] ?? ''));

        if ($cropId !== '' && $cropName !== '') {
            return $this->planCropProfile($input, $query, $cropId, $cropName, $knowledgeOption);
        }

        return $this->planGenericResearch($input, $query, $explicitDomain);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function planCropProfile(
        array $input,
        string $query,
        string $cropId,
        string $cropName,
        string $knowledgeOption,
    ): AgriculturalResearchPlan {
        $domain = $this->resolveDomainForKnowledgeOption($knowledgeOption);
        $userQuery = $query !== ''
            ? $query
            : sprintf('%s %s %s', $cropName, $cropId, CropKnowledgeOptionCatalog::titleFor($knowledgeOption, $cropName));

        $sections = CropKnowledgeSectionCatalog::keysFor($knowledgeOption);
        if ($sections === []) {
            $sections = ['overview', 'scientific_evidence', 'recommendations'];
        }

        return new AgriculturalResearchPlan(
            userQuery: $userQuery,
            intent: 'crop_profile',
            agriculturalDomain: $domain,
            entities: $this->buildCropEntities($cropId, $cropName, $input),
            researchSections: $sections,
            requiredEvidenceTypes: $this->defaultEvidenceTypes(),
            sourceClasses: $this->defaultSourceClasses(),
            researchSequence: self::RESEARCH_SEQUENCE,
            contextInput: array_merge($input, [
                'selected_crop_id' => $cropId,
                'selected_crop_name' => $cropName,
                'knowledge_option' => $knowledgeOption,
            ]),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function planGenericResearch(array $input, string $query, string $explicitDomain): AgriculturalResearchPlan
    {
        $domain = $explicitDomain !== '' ? $explicitDomain : $this->inferDomain($query);
        $userQuery = $query !== '' ? $query : $domain;

        return new AgriculturalResearchPlan(
            userQuery: $userQuery,
            intent: 'generic_research',
            agriculturalDomain: $domain,
            entities: $this->parseEntities($input, $userQuery),
            researchSections: ['overview', 'scientific_evidence', 'practices', 'recommendations'],
            requiredEvidenceTypes: $this->defaultEvidenceTypes(),
            sourceClasses: $this->defaultSourceClasses(),
            researchSequence: self::RESEARCH_SEQUENCE,
            contextInput: $input,
        );
    }

    private function resolveDomainForKnowledgeOption(string $knowledgeOption): string
    {
        return match ($knowledgeOption) {
            'scientific-research' => 'scientific_publications',
            'industries' => 'agricultural_industries',
            default => 'crop_cultivation',
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{type: string, value: string, label?: string}>
     */
    private function buildCropEntities(string $cropId, string $cropName, array $input): array
    {
        $entities = [
            ['type' => 'crop', 'value' => $cropId, 'label' => $cropName],
        ];

        $scientificName = trim((string) ($input['scientific_name'] ?? ''));
        if ($scientificName !== '') {
            $entities[] = ['type' => 'scientific_name', 'value' => $scientificName];
        }

        $categoryId = trim((string) ($input['selected_category_id'] ?? ''));
        if ($categoryId !== '') {
            $entities[] = [
                'type' => 'category',
                'value' => $categoryId,
                'label' => (string) ($input['selected_category_name'] ?? $categoryId),
            ];
        }

        return $entities;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{type: string, value: string, label?: string}>
     */
    private function parseEntities(array $input, string $query): array
    {
        $entities = [];
        $raw = $input['entities'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $entity) {
                if (! is_array($entity)) {
                    continue;
                }
                $type = trim((string) ($entity['type'] ?? 'topic'));
                $value = trim((string) ($entity['value'] ?? ''));
                if ($value === '') {
                    continue;
                }
                $entities[] = [
                    'type' => $type,
                    'value' => $value,
                    'label' => (string) ($entity['label'] ?? $value),
                ];
            }
        }

        if ($entities === [] && $query !== '') {
            $entities[] = ['type' => 'topic', 'value' => $query, 'label' => $query];
        }

        return $entities;
    }

    private function inferDomain(string $query): string
    {
        $normalized = mb_strtolower($query);
        $bestDomain = 'crop_cultivation';
        $bestScore = 0;

        foreach (self::DOMAIN_KEYWORDS as $domain => $keywords) {
            $score = $this->domainMatchScore($normalized, $keywords);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDomain = $domain;
            }
        }

        return $bestDomain;
    }

    private function domainMatchScore(string $normalizedQuery, string $keywords): int
    {
        $tokens = array_values(array_filter(preg_split('/\s+/', $keywords) ?: []));
        $best = 0;

        for ($length = min(4, count($tokens)); $length >= 1; $length--) {
            for ($offset = 0; $offset <= count($tokens) - $length; $offset++) {
                $phrase = implode(' ', array_slice($tokens, $offset, $length));
                if ($phrase === '') {
                    continue;
                }
                if ($length === 1 && strlen($phrase) <= 4) {
                    if (preg_match('/\b'.preg_quote($phrase, '/').'\b/u', $normalizedQuery) === 1) {
                        $best = max($best, $length);
                    }
                    continue;
                }
                if (str_contains($normalizedQuery, $phrase)) {
                    $best = max($best, strlen($phrase));
                }
            }
        }

        return $best;
    }

    private function queryMatchesDomainKeywords(string $normalizedQuery, string $keywords): bool
    {
        return $this->domainMatchScore($normalizedQuery, $keywords) > 0;
    }

    /** @return list<string> */
    private function defaultEvidenceTypes(): array
    {
        return [
            'peer_reviewed_publication',
            'official_research',
            'extension_publication',
            'verified_technical_manual',
        ];
    }

    /** @return list<string> */
    private function defaultSourceClasses(): array
    {
        return ScientificSourceRegistry::approvedSourceTypes();
    }
}
