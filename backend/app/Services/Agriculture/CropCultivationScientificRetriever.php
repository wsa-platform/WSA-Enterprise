<?php

namespace App\Services\Agriculture;

use App\Models\LibraryItem;
use App\Services\Ai\Rag\RagOrchestrator;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;

/**
 * Retrieves verified cultivation knowledge from the existing WSA Library and RAG pipeline.
 * Does not fabricate content or call unverified external sources directly.
 */
class CropCultivationScientificRetriever
{
    public function __construct(
        private RagOrchestrator $ragOrchestrator,
        private ScientificSourceValidator $sourceValidator,
        private FieldCropLibraryRepository $libraryRepository,
    ) {}

    /**
     * @param  array{
     *   selected_crop_id: string,
     *   selected_crop_name: string,
     *   selected_category_id?: string,
     *   selected_category_name?: string
     * }  $cropContext
     * @param  list<string>  $missingKeys
     * @return array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>
     */
    public function retrieveMissingSections(int $organizationId, array $cropContext, array $missingKeys): array
    {
        if ($missingKeys === []) {
            return [];
        }

        $cropId = (string) $cropContext['selected_crop_id'];
        $cropName = (string) $cropContext['selected_crop_name'];
        $retrieved = [];

        foreach ($this->relatedLibraryItems($organizationId, $cropId, $cropName) as $item) {
            $retrieved = $this->extractVerifiedSections($item, $missingKeys, $retrieved);
        }

        $stillMissing = array_values(array_diff($missingKeys, array_keys($retrieved)));
        foreach ($stillMissing as $sectionKey) {
            $section = $this->retrieveSectionFromRag($organizationId, $cropId, $cropName, $sectionKey);
            if ($section !== null) {
                $retrieved[$sectionKey] = $section;
            }
        }

        return $retrieved;
    }

    /**
     * @param  list<string>  $missingKeys
     * @param  array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>  $accumulated
     * @return array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>
     */
    private function extractVerifiedSections(LibraryItem $item, array $missingKeys, array $accumulated): array
    {
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $sections = $metadata['cultivation_sections'] ?? [];
        if (! is_array($sections)) {
            return $accumulated;
        }

        foreach ($missingKeys as $key) {
            if (isset($accumulated[$key])) {
                continue;
            }

            $candidate = $sections[$key] ?? null;
            if (! is_array($candidate) || ! $this->sourceValidator->isVerifiedSection($candidate)) {
                continue;
            }

            $accumulated[$key] = $candidate;
        }

        return $accumulated;
    }

    /**
     * @return list<LibraryItem>
     */
    private function relatedLibraryItems(int $organizationId, string $cropId, string $cropName): array
    {
        $slug = $this->libraryRepository->slugFor($cropId);
        $like = LibraryItem::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $namePattern = '%'.KeywordKnowledgeRetriever::escapeLike($cropName).'%';

        return LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('publication_status', 'published')
            ->where(function ($builder) use ($cropId, $slug, $cropName, $like, $namePattern): void {
                $builder->where('slug', $slug)
                    ->orWhere('metadata->field_crop_id', $cropId)
                    ->orWhere('metadata->service_option', 'farming-needs')
                    ->orWhere('title_ar', $like, $namePattern)
                    ->orWhere('summary_ar', $like, $namePattern);
            })
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->all();
    }

    /**
     * @return array{content: string, source?: array<string, mixed>, verified?: bool}|null
     */
    private function retrieveSectionFromRag(int $organizationId, string $cropId, string $cropName, string $sectionKey): ?array
    {
        $sectionTitle = FieldCropCultivationSectionCatalog::titleFor($sectionKey);
        $query = sprintf('زراعة واحتياجات محصول %s — %s', $cropName, $sectionTitle);

        $rag = $this->ragOrchestrator->assemble($organizationId, [
            'query' => $query,
            'request_type' => 'library_qa',
            'field_crop_id' => $cropId,
            'topic' => 'cultivation',
            'section_key' => $sectionKey,
        ]);

        foreach ($rag->hits as $hit) {
            if ($hit->sourceType !== 'library_items' || $hit->sourceId < 1) {
                continue;
            }

            $item = LibraryItem::query()->find($hit->sourceId);
            if ($item === null) {
                continue;
            }

            $metadata = is_array($item->metadata) ? $item->metadata : [];
            if (($metadata['field_crop_id'] ?? null) !== null && (string) $metadata['field_crop_id'] !== $cropId) {
                continue;
            }

            $sections = $metadata['cultivation_sections'] ?? [];
            if (! is_array($sections)) {
                continue;
            }

            $candidate = $sections[$sectionKey] ?? null;
            if (is_array($candidate) && $this->sourceValidator->isVerifiedSection($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
