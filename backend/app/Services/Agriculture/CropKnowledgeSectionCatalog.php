<?php

namespace App\Services\Agriculture;

/**
 * Generic section catalog keyed by knowledge option.
 */
class CropKnowledgeSectionCatalog
{
    /** @return list<array{key: string, title: string, search_terms_en?: string}> */
    public static function sectionsFor(string $knowledgeOption): array
    {
        return match ($knowledgeOption) {
            'farming-needs' => self::withSearchTerms(
                FieldCropCultivationSectionCatalog::sections(),
                self::farmingNeedsSearchTerms(),
            ),
            'scientific-research' => CropKnowledgeScientificResearchSectionCatalog::sections(),
            'industries' => CropKnowledgeIndustriesSectionCatalog::sections(),
            default => [],
        };
    }

    /** @return list<string> */
    public static function keysFor(string $knowledgeOption): array
    {
        return array_column(self::sectionsFor($knowledgeOption), 'key');
    }

    public static function titleFor(string $knowledgeOption, string $sectionKey): string
    {
        foreach (self::sectionsFor($knowledgeOption) as $section) {
            if ($section['key'] === $sectionKey) {
                return $section['title'];
            }
        }

        return $sectionKey;
    }

    public static function searchTermsFor(string $knowledgeOption, string $sectionKey): string
    {
        foreach (self::sectionsFor($knowledgeOption) as $section) {
            if ($section['key'] === $sectionKey) {
                return (string) ($section['search_terms_en'] ?? $section['title']);
            }
        }

        return $sectionKey;
    }

    /**
     * @param  list<array{key: string, title: string}>  $sections
     * @param  array<string, string>  $searchTerms
     * @return list<array{key: string, title: string, search_terms_en: string}>
     */
    private static function withSearchTerms(array $sections, array $searchTerms): array
    {
        return array_map(function (array $section) use ($searchTerms): array {
            return [
                'key' => $section['key'],
                'title' => $section['title'],
                'search_terms_en' => $searchTerms[$section['key']] ?? $section['key'],
            ];
        }, $sections);
    }

    /** @return array<string, string> */
    private static function farmingNeedsSearchTerms(): array
    {
        return [
            'commercial_scientific_name' => 'taxonomy botanical name',
            'seed_rate' => 'seed rate sowing density',
            'planting_season' => 'planting season sowing date',
            'soil' => 'soil requirements texture pH',
            'land_preparation' => 'land preparation tillage',
            'planting_methods' => 'planting methods sowing',
            'irrigation' => 'irrigation water requirements',
            'fertilization' => 'fertilization nutrient management',
            'environmental_requirements' => 'climate temperature environmental requirements',
            'vegetative_growth_duration' => 'vegetative growth development',
            'reproductive_phase' => 'reproductive phase flowering grain filling',
            'harvest' => 'harvest maturity yield',
            'diseases' => 'plant diseases pathology',
        ];
    }
}
