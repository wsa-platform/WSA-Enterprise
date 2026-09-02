<?php

namespace App\Services\Agriculture\Discoverers;

use App\Contracts\ScientificSectionDiscovererInterface;
use App\Models\LibraryItem;
use App\Services\Agriculture\CropKnowledgeContext;
use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Rag\RagOrchestrator;

/**
 * Uses RAG over library items, extracting only structured verified cultivation sections.
 */
class LibraryRagSectionDiscoverer implements ScientificSectionDiscovererInterface
{
    public function __construct(
        private RagOrchestrator $ragOrchestrator,
        private ScientificSourceValidator $validator,
    ) {}

    public function name(): string
    {
        return 'library_rag';
    }

    public function discoverSections(int $organizationId, CropKnowledgeContext $context, array $sectionKeys): array
    {
        $found = [];
        foreach ($sectionKeys as $sectionKey) {
            $section = $this->discoverSection($organizationId, $context, $sectionKey);
            if ($section !== null) {
                $found[$sectionKey] = $section;
            }
        }

        return $found;
    }

    /**
     * @return array{content: string, source?: array<string, mixed>, verified?: bool}|null
     */
    private function discoverSection(int $organizationId, CropKnowledgeContext $context, string $sectionKey): ?array
    {
        $sectionTitle = CropKnowledgeSectionCatalog::titleFor($context->knowledgeOption, $sectionKey);
        $optionTitle = CropKnowledgeOptionCatalog::titleFor($context->knowledgeOption, $context->cropName);
        $query = sprintf('%s — %s', $optionTitle, $sectionTitle);

        if ($context->scientificName !== '') {
            $query .= ' '.$context->scientificName;
        }

        $rag = $this->ragOrchestrator->assemble($organizationId, [
            'query' => $query,
            'request_type' => 'library_qa',
            'field_crop_id' => $context->cropId,
            'knowledge_option' => $context->knowledgeOption,
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
            $itemCropId = (string) ($metadata['field_crop_id'] ?? '');
            if ($itemCropId !== '' && $itemCropId !== $context->cropId) {
                continue;
            }

            $sections = $metadata['cultivation_sections'] ?? [];
            if (! is_array($sections)) {
                continue;
            }

            $candidate = $sections[$sectionKey] ?? null;
            if (is_array($candidate) && $this->validator->isVerifiedSection($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
