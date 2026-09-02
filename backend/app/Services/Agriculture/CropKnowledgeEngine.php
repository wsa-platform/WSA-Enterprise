<?php

namespace App\Services\Agriculture;

use App\Models\LibraryItem;

/**
 * Generic verified crop knowledge engine — works for any crop + knowledge option.
 */
class CropKnowledgeEngine
{
    public function __construct(
        private FieldCropLibraryRepository $libraryRepository,
        private ScientificSourceDiscoveryPipeline $discoveryPipeline,
        private ScientificSourceValidator $sourceValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $cropContextInput
     * @return array<string, mixed>
     */
    public function buildProfile(int $organizationId, array $cropContextInput): array
    {
        $context = CropKnowledgeContext::fromArray($cropContextInput);

        if (! CropKnowledgeOptionCatalog::isImplemented($context->knowledgeOption)) {
            return $this->notImplementedResponse($context);
        }

        $sectionKeys = CropKnowledgeSectionCatalog::keysFor($context->knowledgeOption);
        if ($sectionKeys === []) {
            return $this->notImplementedResponse($context);
        }

        try {
            return $this->assembleProfile($organizationId, $context, $sectionKeys);
        } catch (\Throwable $exception) {
            return $this->errorResponse($context, $sectionKeys, $exception->getMessage());
        }
    }

    /**
     * @param  list<string>  $sectionKeys
     * @return array<string, mixed>
     */
    private function assembleProfile(int $organizationId, CropKnowledgeContext $context, array $sectionKeys): array
    {
        $item = $this->libraryRepository->findKnowledgeItem(
            $organizationId,
            $context->cropId,
            $context->knowledgeOption,
        );
        $storedSections = $this->sectionsFromLibraryItem($item);
        $hadLibraryContent = $this->hasVerifiedContent($storedSections, $sectionKeys);
        $libraryWasMissing = $item === null && $storedSections === [];

        $missingKeys = $this->missingSectionKeys($storedSections, $sectionKeys);
        $discovery = ['sections' => [], 'discoverers_used' => [], 'retrieval_failed' => false];
        $scientificRetrieved = [];

        if ($missingKeys !== []) {
            $discovery = $this->discoveryPipeline->discoverMissingSections(
                $organizationId,
                $context,
                $missingKeys,
            );
            $scientificRetrieved = $discovery['sections'];

            if ($scientificRetrieved !== []) {
                $mergedForSave = $storedSections;
                foreach ($scientificRetrieved as $key => $section) {
                    if (! isset($mergedForSave[$key]) || ! $this->sourceValidator->isVerifiedSection($mergedForSave[$key])) {
                        $mergedForSave[$key] = $section;
                    }
                }
                $this->libraryRepository->mergeSections($organizationId, $context->toArray(), $mergedForSave);
                $item = $this->libraryRepository->findKnowledgeItem(
                    $organizationId,
                    $context->cropId,
                    $context->knowledgeOption,
                );
                $storedSections = $this->sectionsFromLibraryItem($item) ?: $mergedForSave;
            }
        }

        $merged = $storedSections;
        $stillMissing = $this->missingSectionKeys($merged, $sectionKeys);
        foreach ($stillMissing as $key) {
            $merged[$key] = [
                'content' => ScientificSourceValidator::UNCERTAINTY_MESSAGE,
                'verified' => false,
            ];
        }

        $verifiedCount = $this->verifiedSectionCount($merged, $sectionKeys);
        $loadState = $this->resolveLoadState(
            $hadLibraryContent,
            $libraryWasMissing,
            $scientificRetrieved,
            $verifiedCount,
            count($sectionKeys),
            (bool) ($discovery['retrieval_failed'] ?? false),
        );

        return [
            'crop' => [
                'id' => $context->cropId,
                'name' => $context->cropName,
                'category_id' => $context->categoryId,
                'category_name' => $context->categoryName,
                'scientific_name' => $this->resolveScientificName($merged, $context),
            ],
            'knowledge_option' => $context->knowledgeOption,
            'service_option' => $context->knowledgeOption,
            'title' => CropKnowledgeOptionCatalog::titleFor($context->knowledgeOption, $context->cropName),
            'load_state' => $loadState,
            'message' => $this->messageForLoadState($loadState),
            'sections' => $this->formatSections($merged, $context->knowledgeOption),
            'references' => $this->referencesFromSections($merged, $item),
            'library' => [
                'item_id' => $item?->id,
                'slug' => $item?->slug,
                'reused_existing' => $hadLibraryContent,
                'was_missing_before_retrieval' => $libraryWasMissing,
                'missing_sections_filled' => array_values(array_unique(array_merge(
                    array_keys($scientificRetrieved),
                    $stillMissing,
                ))),
                'scientific_sections_retrieved' => array_keys($scientificRetrieved),
                'discoverers_used' => $discovery['discoverers_used'] ?? [],
                'external_discoverers_used' => $discovery['external_discoverers_used'] ?? [],
                'library_discoverers_used' => $discovery['library_discoverers_used'] ?? [],
            ],
            'source_policy' => [
                'categories' => ScientificSourceRegistry::categories(),
                'traceability' => 'section_source_and_scientific_references',
            ],
        ];
    }

    /**
     * @param  array<string, array{content?: string, verified?: bool}>  $sections
     * @param  list<string>  $sectionKeys
     */
    private function hasVerifiedContent(array $sections, array $sectionKeys): bool
    {
        foreach ($sectionKeys as $key) {
            $section = $sections[$key] ?? null;
            if (is_array($section) && $this->sourceValidator->isVerifiedSection($section)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array{content?: string, verified?: bool}>  $sections
     * @param  list<string>  $sectionKeys
     * @return list<string>
     */
    private function missingSectionKeys(array $sections, array $sectionKeys): array
    {
        $missing = [];
        foreach ($sectionKeys as $key) {
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
    private function verifiedSectionCount(array $sections, array $sectionKeys): int
    {
        $count = 0;
        foreach ($sectionKeys as $key) {
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
    private function resolveLoadState(
        bool $hadLibraryContent,
        bool $libraryWasMissing,
        array $scientificRetrieved,
        int $verifiedCount,
        int $totalSections,
        bool $retrievalFailed,
    ): string {
        if ($retrievalFailed && $verifiedCount === 0) {
            return 'retrieval_error';
        }

        if ($verifiedCount >= $totalSections) {
            if ($hadLibraryContent && $scientificRetrieved === []) {
                return 'library_complete';
            }

            return 'scientific_generated';
        }

        if ($verifiedCount > 0) {
            return 'library_partial_completed';
        }

        if ($libraryWasMissing && $scientificRetrieved === []) {
            return 'library_missing';
        }

        return 'insufficient_verified_sources';
    }

    private function messageForLoadState(string $loadState): ?string
    {
        return match ($loadState) {
            'insufficient_verified_sources', 'library_missing' => ScientificSourceValidator::INSUFFICIENT_CROP_MESSAGE,
            'retrieval_error' => 'تعذر إكمال البحث في المصادر العلمية الموثوقة حاليًا. يرجى المحاولة لاحقًا.',
            'knowledge_option_not_implemented' => 'هذا الخيار المعرفي قيد التطوير ولم يُفعَّل بعد في المحرك العام.',
            default => null,
        };
    }

    /** @return array<string, array{content: string, source?: array<string, mixed>, verified?: bool}> */
    private function sectionsFromLibraryItem(?LibraryItem $item): array
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
     */
    private function resolveScientificName(array $sections, CropKnowledgeContext $context): string
    {
        if ($context->scientificName !== '') {
            return $context->scientificName;
        }

        $fromCatalog = FieldCropTaxonomyCatalog::scientificNameFor($context->cropId);
        if ($fromCatalog !== '') {
            return $fromCatalog;
        }

        $nameSection = $sections['commercial_scientific_name']['content'] ?? '';
        if (preg_match('/\b([A-Z][a-z]+(?:\s+[a-z]+)?\s+[A-Z][a-z]+)\b/u', (string) $nameSection, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * @param  array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>  $sections
     * @return list<array<string, mixed>>
     */
    private function formatSections(array $sections, string $knowledgeOption): array
    {
        $formatted = [];
        foreach (CropKnowledgeSectionCatalog::sectionsFor($knowledgeOption) as $definition) {
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
    private function referencesFromSections(array $sections, ?LibraryItem $item): array
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

    /**
     * @param  list<string>  $sectionKeys
     * @return array<string, mixed>
     */
    private function notImplementedResponse(CropKnowledgeContext $context): array
    {
        return [
            'crop' => [
                'id' => $context->cropId,
                'name' => $context->cropName,
                'category_id' => $context->categoryId,
                'category_name' => $context->categoryName,
                'scientific_name' => $context->scientificName,
            ],
            'knowledge_option' => $context->knowledgeOption,
            'service_option' => $context->knowledgeOption,
            'title' => CropKnowledgeOptionCatalog::titleFor($context->knowledgeOption, $context->cropName),
            'load_state' => 'knowledge_option_not_implemented',
            'message' => $this->messageForLoadState('knowledge_option_not_implemented'),
            'sections' => [],
            'references' => [],
            'library' => [
                'item_id' => null,
                'slug' => null,
                'reused_existing' => false,
                'was_missing_before_retrieval' => true,
                'missing_sections_filled' => [],
                'scientific_sections_retrieved' => [],
                'discoverers_used' => [],
            ],
            'source_policy' => [
                'categories' => ScientificSourceRegistry::categories(),
                'traceability' => 'section_source_and_scientific_references',
            ],
        ];
    }

    /**
     * @param  list<string>  $sectionKeys
     * @return array<string, mixed>
     */
    private function errorResponse(CropKnowledgeContext $context, array $sectionKeys, string $reason): array
    {
        $sections = [];
        foreach (CropKnowledgeSectionCatalog::sectionsFor($context->knowledgeOption) as $definition) {
            $sections[] = [
                'key' => $definition['key'],
                'title' => $definition['title'],
                'content' => ScientificSourceValidator::UNCERTAINTY_MESSAGE,
                'source' => null,
                'verified' => false,
            ];
        }

        return [
            'crop' => [
                'id' => $context->cropId,
                'name' => $context->cropName,
                'category_id' => $context->categoryId,
                'category_name' => $context->categoryName,
                'scientific_name' => $context->scientificName,
            ],
            'knowledge_option' => $context->knowledgeOption,
            'service_option' => $context->knowledgeOption,
            'title' => CropKnowledgeOptionCatalog::titleFor($context->knowledgeOption, $context->cropName),
            'load_state' => 'retrieval_error',
            'message' => $this->messageForLoadState('retrieval_error'),
            'sections' => $sections,
            'references' => [],
            'library' => [
                'item_id' => null,
                'slug' => null,
                'reused_existing' => false,
                'was_missing_before_retrieval' => true,
                'missing_sections_filled' => $sectionKeys,
                'scientific_sections_retrieved' => [],
                'discoverers_used' => [],
                'error' => $reason,
            ],
            'source_policy' => [
                'categories' => ScientificSourceRegistry::categories(),
                'traceability' => 'section_source_and_scientific_references',
            ],
        ];
    }
}
