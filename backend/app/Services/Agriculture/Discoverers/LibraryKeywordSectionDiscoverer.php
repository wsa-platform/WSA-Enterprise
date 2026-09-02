<?php

namespace App\Services\Agriculture\Discoverers;

use App\Contracts\ScientificSectionDiscovererInterface;
use App\Models\LibraryItem;
use App\Services\Agriculture\CropKnowledgeContext;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\ScientificSourceRegistry;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;

/**
 * Searches published library articles for crop + section keywords.
 * Only accepts content when item metadata includes a verified scientific_source object.
 */
class LibraryKeywordSectionDiscoverer implements ScientificSectionDiscovererInterface
{
    public function __construct(
        private ScientificSourceValidator $validator,
    ) {}

    public function name(): string
    {
        return 'library_keyword';
    }

    public function discoverSections(int $organizationId, CropKnowledgeContext $context, array $sectionKeys): array
    {
        $found = [];
        $like = LibraryItem::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        foreach ($sectionKeys as $sectionKey) {
            if (isset($found[$sectionKey])) {
                continue;
            }

            $sectionTitle = CropKnowledgeSectionCatalog::titleFor($context->knowledgeOption, $sectionKey);
            $keywords = array_filter([
                $context->cropName,
                $context->scientificName,
                $sectionTitle,
            ]);

            if ($keywords === []) {
                continue;
            }

            $items = LibraryItem::query()
                ->where('organization_id', $organizationId)
                ->where('publication_status', 'published')
                ->where(function ($builder) use ($keywords, $like): void {
                    foreach ($keywords as $keyword) {
                        $pattern = '%'.KeywordKnowledgeRetriever::escapeLike($keyword).'%';
                        $builder->orWhere('title_ar', $like, $pattern)
                            ->orWhere('summary_ar', $like, $pattern)
                            ->orWhere('content_ar', $like, $pattern)
                            ->orWhere('content', $like, $pattern);
                    }
                })
                ->orderByDesc('published_at')
                ->limit(5)
                ->get();

            foreach ($items as $item) {
                $section = $this->sectionFromItem($item, $context, $sectionKey);
                if ($section !== null) {
                    $found[$sectionKey] = $section;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * @return array{content: string, source: array<string, mixed>, verified: bool}|null
     */
    private function sectionFromItem(LibraryItem $item, CropKnowledgeContext $context, string $sectionKey): ?array
    {
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $structured = $metadata['cultivation_sections'][$sectionKey] ?? null;
        if (is_array($structured) && $this->validator->isVerifiedSection($structured)) {
            return $structured;
        }

        $source = $metadata['scientific_source'] ?? null;
        if (! is_array($source) || ! $this->validator->isVerifiedSource($source)) {
            return null;
        }

        $haystack = mb_strtolower(
            (string) $item->title_ar.' '.(string) $item->summary_ar.' '.(string) $item->content_ar,
        );
        $cropNeedle = mb_strtolower($context->cropName);
        if ($cropNeedle !== '' && ! str_contains($haystack, $cropNeedle)) {
            if ($context->scientificName === '' || ! str_contains($haystack, mb_strtolower($context->scientificName))) {
                return null;
            }
        }

        $excerpt = trim((string) ($item->summary_ar ?: $item->content_ar ?: $item->summary ?: $item->content));
        if ($excerpt === '') {
            return null;
        }

        return [
            'content' => $excerpt,
            'source' => array_merge($source, [
                'source_type' => (string) ($source['source_type'] ?? ScientificSourceRegistry::approvedSourceTypes()[0] ?? 'supporting_verified'),
            ]),
            'verified' => true,
        ];
    }
}
