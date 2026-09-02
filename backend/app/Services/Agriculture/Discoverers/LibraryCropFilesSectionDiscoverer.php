<?php

namespace App\Services\Agriculture\Discoverers;

use App\Contracts\ScientificSectionDiscovererInterface;
use App\Models\LibraryItem;
use App\Services\Agriculture\CropKnowledgeContext;
use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\ScientificSourceRegistry;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;

/**
 * Reads published Phase 2 library crop files (category → crop → section) without changing library UI.
 */
class LibraryCropFilesSectionDiscoverer implements ScientificSectionDiscovererInterface
{
    public function __construct(
        private ScientificSourceValidator $validator,
    ) {}

    public function name(): string
    {
        return 'library_crop_files';
    }

    public function discoverSections(int $organizationId, CropKnowledgeContext $context, array $sectionKeys): array
    {
        if ($sectionKeys === [] || $context->categoryId === '') {
            return [];
        }

        $sectionId = $this->libraryFileSectionFor($context->knowledgeOption);
        if ($sectionId === null) {
            return [];
        }

        $found = [];
        foreach ($this->relatedFileItems($organizationId, $context, $sectionId) as $item) {
            $found = $this->extractFromItem($item, $context, $sectionKeys, $found);
        }

        return $found;
    }

    private function libraryFileSectionFor(string $knowledgeOption): ?string
    {
        return match ($knowledgeOption) {
            'farming-needs', 'scientific-research', 'industries', 'other' => $knowledgeOption,
            default => null,
        };
    }

    /**
     * @return list<LibraryItem>
     */
    private function relatedFileItems(int $organizationId, CropKnowledgeContext $context, string $sectionId): array
    {
        return LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('publication_status', 'published')
            ->where(function ($query) use ($context, $sectionId): void {
                $query->where(function ($scoped) use ($context, $sectionId): void {
                    $scoped->where('metadata->plant_production_category_id', $context->categoryId)
                        ->where('metadata->field_crop_id', $context->cropId)
                        ->where('metadata->library_file_section', $sectionId);
                })->orWhere(function ($scoped) use ($context, $sectionId): void {
                    $scoped->where('metadata->field_crop_category_id', $context->categoryId)
                        ->where('metadata->field_crop_id', $context->cropId)
                        ->where('metadata->library_file_section', $sectionId);
                });
            })
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->all();
    }

    /**
     * @param  list<string>  $sectionKeys
     * @param  array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>  $accumulated
     * @return array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>
     */
    private function extractFromItem(
        LibraryItem $item,
        CropKnowledgeContext $context,
        array $sectionKeys,
        array $accumulated,
    ): array {
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $sections = $metadata['cultivation_sections'] ?? [];
        if (is_array($sections)) {
            foreach ($sectionKeys as $key) {
                if (isset($accumulated[$key])) {
                    continue;
                }
                $candidate = $sections[$key] ?? null;
                if (is_array($candidate) && $this->validator->isVerifiedSection($candidate)) {
                    $accumulated[$key] = $candidate;
                }
            }
        }

        $source = $metadata['scientific_source'] ?? null;
        if (! is_array($source) || ! $this->validator->isVerifiedSource($source)) {
            return $accumulated;
        }

        $haystack = mb_strtolower(implode(' ', array_filter([
            (string) $item->title_ar,
            (string) $item->summary_ar,
            (string) $item->content_ar,
            (string) $item->title,
            (string) $item->summary,
            (string) $item->content,
        ])));

        $cropNeedle = mb_strtolower($context->cropName);
        if ($cropNeedle !== '' && ! str_contains($haystack, $cropNeedle)) {
            if ($context->scientificName === '' || ! str_contains($haystack, mb_strtolower($context->scientificName))) {
                return $accumulated;
            }
        }

        foreach ($sectionKeys as $sectionKey) {
            if (isset($accumulated[$sectionKey])) {
                continue;
            }

            $sectionTitle = CropKnowledgeSectionCatalog::titleFor($context->knowledgeOption, $sectionKey);
            $titlePattern = '%'.KeywordKnowledgeRetriever::escapeLike($sectionTitle).'%';
            $like = LibraryItem::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $matchesSection = str_contains($haystack, mb_strtolower($sectionTitle));

            if (! $matchesSection) {
                $searchTerms = CropKnowledgeSectionCatalog::searchTermsFor($context->knowledgeOption, $sectionKey);
                $matchesSection = $searchTerms !== '' && str_contains($haystack, mb_strtolower($searchTerms));
            }

            if (! $matchesSection && ! $this->itemMentionsSection($item, $sectionTitle, $like, $titlePattern)) {
                continue;
            }

            $excerpt = trim((string) ($item->summary_ar ?: $item->content_ar ?: $item->summary ?: $item->content));
            if ($excerpt === '') {
                continue;
            }

            $accumulated[$sectionKey] = [
                'content' => $excerpt,
                'source' => array_merge($source, [
                    'source_type' => (string) ($source['source_type'] ?? ScientificSourceRegistry::approvedSourceTypes()[0] ?? 'supporting_verified'),
                ]),
                'verified' => true,
            ];
        }

        return $accumulated;
    }

    private function itemMentionsSection(LibraryItem $item, string $sectionTitle, string $like, string $titlePattern): bool
    {
        $fields = [
            (string) $item->title_ar,
            (string) $item->summary_ar,
            (string) $item->content_ar,
        ];

        foreach ($fields as $field) {
            if ($field !== '' && str_contains(mb_strtolower($field), mb_strtolower($sectionTitle))) {
                return true;
            }
        }

        return false;
    }
}
