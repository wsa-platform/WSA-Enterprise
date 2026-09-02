<?php

namespace App\Services\Agriculture\Discoverers;

use App\Contracts\ScientificSectionDiscovererInterface;
use App\Services\Agriculture\CropKnowledgeContext;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\CropScientificPublicationMatcher;
use App\Services\Agriculture\OpenAlexScientificClient;
use App\Services\Agriculture\ScientificContentNormalizer;
use App\Services\Agriculture\ScientificSourceValidator;

/**
 * Discovers verified sections from external scholarly indexes (OpenAlex).
 * Only returns content backed by real publication metadata — never fabricates sources.
 */
class ExternalScientificSectionDiscoverer implements ScientificSectionDiscovererInterface
{
    public function __construct(
        private OpenAlexScientificClient $openAlexClient,
        private ScientificSourceValidator $validator,
        private ScientificContentNormalizer $normalizer,
        private CropScientificPublicationMatcher $publicationMatcher,
    ) {}

    public function name(): string
    {
        return 'external_openalex';
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

        $works = $this->openAlexClient->searchWorks($query, 8);

        foreach ($works as $work) {
            if (! is_array($work)) {
                continue;
            }

            $title = trim(strip_tags((string) ($work['display_name'] ?? $work['title'] ?? '')));
            if ($title === '') {
                continue;
            }

            $abstract = $this->openAlexClient->reconstructAbstract(
                is_array($work['abstract_inverted_index'] ?? null) ? $work['abstract_inverted_index'] : null,
            );

            $body = $abstract !== '' ? $abstract : $title;
            if (! $this->publicationMatcher->isRelevant($context, $title, $body)) {
                continue;
            }

            $source = $this->openAlexClient->buildSourceFromWork($work);
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
