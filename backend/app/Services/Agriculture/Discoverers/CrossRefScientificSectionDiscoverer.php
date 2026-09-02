<?php

namespace App\Services\Agriculture\Discoverers;

use App\Contracts\ScientificSectionDiscovererInterface;
use App\Services\Agriculture\CropKnowledgeContext;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\CropScientificPublicationMatcher;
use App\Services\Agriculture\CrossRefScientificClient;
use App\Services\Agriculture\ScientificContentNormalizer;
use App\Services\Agriculture\ScientificSourceValidator;

/**
 * Discovers verified sections from the Crossref scholarly index.
 * Complements OpenAlex — failure of one index does not end discovery.
 */
class CrossRefScientificSectionDiscoverer implements ScientificSectionDiscovererInterface
{
    public function __construct(
        private CrossRefScientificClient $crossRefClient,
        private ScientificSourceValidator $validator,
        private ScientificContentNormalizer $normalizer,
        private CropScientificPublicationMatcher $publicationMatcher,
    ) {}

    public function name(): string
    {
        return 'external_crossref';
    }

    public function discoverSections(int $organizationId, CropKnowledgeContext $context, array $sectionKeys): array
    {
        $found = [];

        foreach ($sectionKeys as $sectionKey) {
            if (isset($found[$sectionKey])) {
                continue;
            }

            $section = $this->discoverSection($context, $sectionKey);
            if ($section !== null) {
                $found[$sectionKey] = $section;
            }
        }

        return $found;
    }

    /**
     * @return array{content: string, source: array<string, mixed>, verified: bool}|null
     */
    private function discoverSection(CropKnowledgeContext $context, string $sectionKey): ?array
    {
        $sectionTitle = CropKnowledgeSectionCatalog::titleFor($context->knowledgeOption, $sectionKey);
        $searchTerms = CropKnowledgeSectionCatalog::searchTermsFor($context->knowledgeOption, $sectionKey);
        $query = $this->buildSearchQuery($context, $searchTerms);

        $works = $this->crossRefClient->searchWorks($query, 8);

        foreach ($works as $work) {
            if (! is_array($work)) {
                continue;
            }

            $title = $this->crossRefClient->titleFromWork($work);
            if ($title === '') {
                continue;
            }

            $abstract = $this->crossRefClient->abstractFromWork($work);
            $body = $abstract !== '' ? $abstract : $title;

            if (! $this->publicationMatcher->isRelevant($context, $title, $body)) {
                continue;
            }

            $source = $this->crossRefClient->buildSourceFromWork($work);
            if (! $this->validator->isVerifiedSource($source)) {
                continue;
            }

            $rawContent = $abstract !== '' ? $abstract : $title;
            $content = $this->normalizer->normalizeForSection($rawContent, $context, $sectionTitle, $source);
            if (trim($content) === '') {
                continue;
            }

            return [
                'content' => $content,
                'source' => $source,
                'verified' => true,
            ];
        }

        return null;
    }

    private function buildSearchQuery(CropKnowledgeContext $context, string $searchTerms): string
    {
        $parts = array_filter([
            $context->scientificName,
            $context->cropId,
            $searchTerms,
            'agriculture',
        ]);

        return implode(' ', $parts);
    }
}
