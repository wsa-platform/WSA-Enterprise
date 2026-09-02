<?php

namespace App\Services\Agriculture\Discoverers;

use App\Contracts\ScientificSectionDiscovererInterface;
use App\Models\LibraryItem;
use App\Services\Agriculture\CropKnowledgeContext;
use App\Services\Agriculture\FieldCropLibraryRepository;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;

/**
 * Discovers verified sections from library items tied to the same crop + knowledge option.
 */
class LibraryStructuredSectionDiscoverer implements ScientificSectionDiscovererInterface
{
    public function __construct(
        private FieldCropLibraryRepository $repository,
        private ScientificSourceValidator $validator,
    ) {}

    public function name(): string
    {
        return 'library_structured';
    }

    public function discoverSections(int $organizationId, CropKnowledgeContext $context, array $sectionKeys): array
    {
        if ($sectionKeys === []) {
            return [];
        }

        $found = [];
        foreach ($this->relatedItems($organizationId, $context) as $item) {
            $found = $this->extract($item, $sectionKeys, $found);
        }

        return $found;
    }

    /**
     * @return list<LibraryItem>
     */
    private function relatedItems(int $organizationId, CropKnowledgeContext $context): array
    {
        $slug = $this->repository->slugFor($context->cropId, $context->knowledgeOption);
        $like = LibraryItem::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $namePattern = '%'.KeywordKnowledgeRetriever::escapeLike($context->cropName).'%';

        return LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('publication_status', 'published')
            ->where(function ($builder) use ($context, $slug, $like, $namePattern): void {
                $builder->where('slug', $slug)
                    ->orWhere(function ($scoped) use ($context): void {
                        $scoped->where('metadata->field_crop_id', $context->cropId)
                            ->where('metadata->service_option', $context->knowledgeOption);
                    })
                    ->orWhere(function ($scoped) use ($context, $like, $namePattern): void {
                        $scoped->where('metadata->field_crop_id', $context->cropId)
                            ->where(function ($name) use ($like, $namePattern): void {
                                $name->where('title_ar', $like, $namePattern)
                                    ->orWhere('summary_ar', $like, $namePattern);
                            });
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
    private function extract(LibraryItem $item, array $sectionKeys, array $accumulated): array
    {
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $sections = $metadata['cultivation_sections'] ?? [];
        if (! is_array($sections)) {
            return $accumulated;
        }

        foreach ($sectionKeys as $key) {
            if (isset($accumulated[$key])) {
                continue;
            }
            $candidate = $sections[$key] ?? null;
            if (is_array($candidate) && $this->validator->isVerifiedSection($candidate)) {
                $accumulated[$key] = $candidate;
            }
        }

        return $accumulated;
    }
}
