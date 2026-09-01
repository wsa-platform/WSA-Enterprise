<?php

namespace App\Services\Agriculture;

class FieldCropCultivationProfileService
{
    public function __construct(
        private FieldCropLibraryRepository $libraryRepository,
        private CropCultivationScientificRetriever $scientificRetriever,
        private ScientificSourceValidator $sourceValidator,
    ) {}

    /**
     * @param  array{
     *   selected_crop_id: string,
     *   selected_crop_name: string,
     *   selected_category_id?: string,
     *   selected_category_name?: string
     * }  $cropContext
     * @return array<string, mixed>
     */
    public function getProfile(int $organizationId, array $cropContext): array
    {
        $cropId = (string) $cropContext['selected_crop_id'];
        $cropName = (string) $cropContext['selected_crop_name'];

        $item = $this->libraryRepository->findFarmingNeedsItem($organizationId, $cropId);
        $storedSections = $this->sectionsFromLibraryItem($item);
        $hadLibraryContent = $this->hasVerifiedContent($storedSections);

        $missingKeys = $this->missingSectionKeys($storedSections);
        $scientificRetrieved = [];

        if ($missingKeys !== []) {
            $scientificRetrieved = $this->scientificRetriever->retrieveMissingSections(
                $organizationId,
                $cropContext,
                $missingKeys,
            );

            if ($scientificRetrieved !== []) {
                $mergedForSave = $storedSections;
                foreach ($scientificRetrieved as $key => $section) {
                    if (! isset($mergedForSave[$key]) || ! $this->sourceValidator->isVerifiedSection($mergedForSave[$key])) {
                        $mergedForSave[$key] = $section;
                    }
                }
                $this->libraryRepository->mergeSections($organizationId, $cropContext, $mergedForSave);
                $item = $this->libraryRepository->findFarmingNeedsItem($organizationId, $cropId);
                $storedSections = $this->sectionsFromLibraryItem($item) ?: $mergedForSave;
            }
        }

        $merged = $storedSections;
        $stillMissing = $this->missingSectionKeys($merged);
        foreach ($stillMissing as $key) {
            $merged[$key] = [
                'content' => ScientificSourceValidator::UNCERTAINTY_MESSAGE,
                'verified' => false,
            ];
        }

        $verifiedCount = $this->verifiedSectionCount($merged);
        $loadState = $this->resolveLoadState($hadLibraryContent, $scientificRetrieved, $verifiedCount);

        $references = $this->referencesFromSections($merged, $item);

        return [
            'crop' => [
                'id' => $cropId,
                'name' => $cropName,
                'category_id' => (string) ($cropContext['selected_category_id'] ?? ''),
                'category_name' => (string) ($cropContext['selected_category_name'] ?? ''),
            ],
            'service_option' => 'farming-needs',
            'title' => 'زراعة واحتياجات محصول '.$cropName,
            'load_state' => $loadState,
            'message' => $this->messageForLoadState($loadState),
            'sections' => $this->formatSections($merged),
            'references' => $references,
            'library' => [
                'item_id' => $item?->id,
                'slug' => $item?->slug,
                'reused_existing' => $hadLibraryContent,
                'missing_sections_filled' => array_values(array_unique(array_merge(
                    array_keys($scientificRetrieved),
                    $stillMissing,
                ))),
                'scientific_sections_retrieved' => array_keys($scientificRetrieved),
            ],
            'source_policy' => [
                'categories' => ScientificSourceRegistry::categories(),
                'traceability' => 'section_source_and_scientific_references',
            ],
        ];
    }

    /**
     * @param  array<string, array{content?: string, verified?: bool}>  $sections
     */
    private function hasVerifiedContent(array $sections): bool
    {
        foreach ($sections as $section) {
            if ($this->sourceValidator->isVerifiedSection($section)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array{content?: string, verified?: bool}>  $sections
     * @return list<string>
     */
    private function missingSectionKeys(array $sections): array
    {
        $missing = [];
        foreach (FieldCropCultivationSectionCatalog::keys() as $key) {
            $section = $sections[$key] ?? null;
            if (! is_array($section) || ! $this->sourceValidator->isVerifiedSection($section)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, array{content?: string, verified?: bool}>  $sections
     */
    private function verifiedSectionCount(array $sections): int
    {
        $count = 0;
        foreach (FieldCropCultivationSectionCatalog::keys() as $key) {
            $section = $sections[$key] ?? null;
            if (is_array($section) && $this->sourceValidator->isVerifiedSection($section)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>  $scientificRetrieved
     */
    private function resolveLoadState(bool $hadLibraryContent, array $scientificRetrieved, int $verifiedCount): string
    {
        $total = count(FieldCropCultivationSectionCatalog::keys());

        if ($verifiedCount >= $total) {
            return $hadLibraryContent && $scientificRetrieved === [] ? 'library_complete' : 'scientific_generated';
        }

        if ($verifiedCount > 0) {
            return 'library_partial_completed';
        }

        return 'insufficient_verified_sources';
    }

    private function messageForLoadState(string $loadState): ?string
    {
        return match ($loadState) {
            'insufficient_verified_sources' => ScientificSourceValidator::INSUFFICIENT_CROP_MESSAGE,
            default => null,
        };
    }

    /** @return array<string, array{content: string, source?: array<string, mixed>, verified?: bool}> */
    private function sectionsFromLibraryItem(?\App\Models\LibraryItem $item): array
    {
        if ($item === null) {
            return [];
        }

        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $sections = $metadata['cultivation_sections'] ?? [];

        return is_array($sections) ? $sections : [];
    }

    /**
     * @param  array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>  $sections
     * @return list<array<string, mixed>>
     */
    private function formatSections(array $sections): array
    {
        $formatted = [];

        foreach (FieldCropCultivationSectionCatalog::sections() as $definition) {
            $key = $definition['key'];
            $section = $sections[$key] ?? ['content' => '', 'verified' => false];

            $formatted[] = [
                'key' => $key,
                'title' => $definition['title'],
                'content' => (string) ($section['content'] ?? ''),
                'source' => is_array($section['source'] ?? null) ? $section['source'] : null,
                'verified' => (bool) ($section['verified'] ?? false),
            ];
        }

        return $formatted;
    }

    /**
     * @param  array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>  $sections
     * @return list<array<string, mixed>>
     */
    private function referencesFromSections(array $sections, ?\App\Models\LibraryItem $item): array
    {
        $references = [];
        foreach ($sections as $section) {
            if (! is_array($section['source'] ?? null)) {
                continue;
            }
            if ($this->sourceValidator->isVerifiedSource($section['source'])) {
                $references[] = $section['source'];
            }
        }

        if ($item !== null) {
            $metadata = is_array($item->metadata) ? $item->metadata : [];
            $stored = $metadata['scientific_references'] ?? [];
            if (is_array($stored)) {
                foreach ($stored as $reference) {
                    if (is_array($reference) && $this->sourceValidator->isVerifiedSource($reference)) {
                        $references[] = $reference;
                    }
                }
            }
        }

        $unique = [];
        foreach ($references as $reference) {
            $url = (string) ($reference['url'] ?? '');
            $unique[$url !== '' ? $url : json_encode($reference)] = $reference;
        }

        return array_values($unique);
    }
}
