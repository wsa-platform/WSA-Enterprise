<?php

namespace App\Services\Agriculture\Research\Persistence;

use App\Models\LibraryItem;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexSync;
use Illuminate\Support\Facades\Log;

/**
 * Persists verified research knowledge with evidence into the WSA Library memory layer.
 *
 * Library is memory — Internet-First discovery remains primary for future research.
 */
class ScientificKnowledgePersistenceService
{
    public function __construct(
        private KnowledgeSemanticIndexSync $semanticIndex,
        private ScientificSourceValidator $sourceValidator,
    ) {}

    public function persist(
        int $organizationId,
        KnowledgeQueryPlan $plan,
        AnswerSynthesisExecutionReport $synthesisReport,
        EvidenceValidationExecutionReport $validationReport,
    ): KnowledgePersistenceExecutionReport {
        if ($organizationId < 1) {
            return $this->skippedReport('invalid_organization', 'invalid_organization_id');
        }

        if (! $synthesisReport->performed || $synthesisReport->claims === []) {
            return $this->skippedReport('nothing_to_persist', 'no_verified_claims');
        }

        if ($synthesisReport->citations === []) {
            return $this->skippedReport('nothing_to_persist', 'no_verified_citations');
        }

        if ($validationReport->validatedCount < 1) {
            return $this->skippedReport('nothing_to_persist', 'no_validated_evidence');
        }

        $slug = $this->slugFor($plan);
        $fingerprint = $this->evidenceFingerprint($validationReport->validatedEvidence);
        $existing = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->first();

        if ($existing !== null) {
            $existingMeta = is_array($existing->metadata) ? $existing->metadata : [];
            $existingResearch = is_array($existingMeta['research_agent'] ?? null) ? $existingMeta['research_agent'] : [];
            $existingFingerprint = (string) ($existingResearch['evidence_fingerprint'] ?? '');

            if ($existingFingerprint === $fingerprint) {
                return new KnowledgePersistenceExecutionReport(
                    status: 'persistence_unchanged',
                    performed: true,
                    libraryItemId: (int) $existing->id,
                    slug: $slug,
                    action: 'unchanged',
                    provenance: is_array($existingResearch['provenance'] ?? null) ? $existingResearch['provenance'] : null,
                    observability: [
                        'duplicate_protection' => 'same_evidence_fingerprint',
                        'internet_first_preserved' => true,
                    ],
                );
            }

            $existingFreshness = (int) ($existingResearch['newest_publication_year'] ?? 0);
            $incomingFreshness = $this->newestPublicationYear($validationReport->validatedEvidence);
            if ($existingFreshness > $incomingFreshness && $existingFingerprint !== '') {
                return new KnowledgePersistenceExecutionReport(
                    status: 'persistence_skipped',
                    performed: false,
                    libraryItemId: (int) $existing->id,
                    slug: $slug,
                    action: 'skipped_newer_existing',
                    provenance: is_array($existingResearch['provenance'] ?? null) ? $existingResearch['provenance'] : null,
                    observability: [
                        'duplicate_protection' => 'existing_evidence_is_newer',
                        'internet_first_preserved' => true,
                    ],
                );
            }
        }

        try {
            $item = $this->writeLibraryItem(
                $organizationId,
                $plan,
                $synthesisReport,
                $validationReport,
                $slug,
                $fingerprint,
                $existing,
            );

            return new KnowledgePersistenceExecutionReport(
                status: 'persistence_completed',
                performed: true,
                libraryItemId: (int) $item->id,
                slug: $slug,
                action: $existing === null ? 'created' : 'updated',
                provenance: $this->provenance($plan, $synthesisReport, $validationReport),
                observability: [
                    'claims_persisted' => count($synthesisReport->claims),
                    'evidence_persisted' => $validationReport->validatedCount,
                    'citations_persisted' => count($synthesisReport->citations),
                    'internet_first_preserved' => true,
                ],
            );
        } catch (\Throwable $exception) {
            Log::warning('Research agent knowledge persistence failed', [
                'organization_id' => $organizationId,
                'slug' => $slug,
                'message' => $exception->getMessage(),
            ]);

            return new KnowledgePersistenceExecutionReport(
                status: 'persistence_failed',
                performed: false,
                libraryItemId: $existing !== null ? (int) $existing->id : null,
                slug: $slug,
                action: 'failed',
                provenance: null,
                observability: [
                    'failure_reason' => 'persistence_exception',
                    'internet_first_preserved' => true,
                ],
            );
        }
    }

    private function writeLibraryItem(
        int $organizationId,
        KnowledgeQueryPlan $plan,
        AnswerSynthesisExecutionReport $synthesisReport,
        EvidenceValidationExecutionReport $validationReport,
        string $slug,
        string $fingerprint,
        ?LibraryItem $existing,
    ): LibraryItem {
        $language = $plan->normalizedQuery->language;
        $item = $existing ?? new LibraryItem;
        $references = $this->scientificReferences($synthesisReport->citations);
        $primarySource = $references[0] ?? null;

        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $metadata['research_agent'] = [
            'stage' => 5,
            'provenance' => $this->provenance($plan, $synthesisReport, $validationReport),
            'verified_at' => now()->toIso8601String(),
            'query' => $plan->normalizedQuery->originalQuestion,
            'normalized_query' => $plan->normalizedQuery->normalizedQuestion,
            'research_intent' => $plan->researchIntent,
            'agricultural_domain' => $plan->agriculturalDomain,
            'subject_entity' => $plan->subjectEntity,
            'language' => $language,
            'claims' => array_map(
                static fn ($claim): array => $claim->toArray(),
                $synthesisReport->claims,
            ),
            'validated_evidence' => array_map(
                static fn (ScientificEvidenceItem $evidence): array => $evidence->toArray(),
                $validationReport->validatedEvidence,
            ),
            'citations' => array_map(
                static fn (ResearchAnswerCitation $citation): array => $citation->toArray(),
                $synthesisReport->citations,
            ),
            'confidence' => $synthesisReport->confidence,
            'limitations' => $synthesisReport->limitations,
            'uncertainty' => $synthesisReport->uncertainty,
            'conflicts' => $synthesisReport->conflicts,
            'synthesis_metadata' => $synthesisReport->researchMetadata,
            'evidence_fingerprint' => $fingerprint,
            'newest_publication_year' => $this->newestPublicationYear($validationReport->validatedEvidence),
            'internet_first' => $plan->isInternetFirst(),
        ];

        if (is_array($primarySource)) {
            $metadata['scientific_source'] = $primarySource;
        }

        if ($references !== []) {
            $metadata['scientific_references'] = $references;
        }

        $title = $this->titleFor($plan, $language);
        $summary = $synthesisReport->conciseSummary ?? '';

        $item->organization_id = $organizationId;
        $item->slug = $slug;
        $item->item_type = 'verified_research_knowledge';
        $item->locale = $language === 'ar' ? 'ar' : 'en';
        $item->publication_status = 'published';
        $item->published_at = $item->published_at ?? now();
        $item->metadata = $metadata;

        if ($language === 'ar') {
            $item->title_ar = $title;
            $item->summary_ar = $summary;
            $item->content_ar = (string) ($synthesisReport->answer ?? $summary);
            $item->title = $plan->normalizedQuery->normalizedQuestion;
            $item->summary = $summary;
            $item->content = (string) ($synthesisReport->detailedExplanation ?? $summary);
        } else {
            $item->title = $title;
            $item->summary = $summary;
            $item->content = (string) ($synthesisReport->answer ?? $summary);
            $item->title_ar = $title;
            $item->summary_ar = $summary;
            $item->content_ar = (string) ($synthesisReport->answer ?? $summary);
        }

        $item->source = $this->sourceAttribution($references);
        $item->save();
        $this->semanticIndex->syncLibraryItem($item);

        return $item->fresh() ?? $item;
    }

    /** @return array<string, mixed> */
    private function provenance(
        KnowledgeQueryPlan $plan,
        AnswerSynthesisExecutionReport $synthesisReport,
        EvidenceValidationExecutionReport $validationReport,
    ): array {
        return [
            'research_timestamp' => now()->toIso8601String(),
            'query' => $plan->normalizedQuery->originalQuestion,
            'research_intent' => $plan->researchIntent,
            'agricultural_domain' => $plan->agriculturalDomain,
            'validation_status' => $validationReport->status,
            'validation_summary' => [
                'validated_count' => $validationReport->validatedCount,
                'rejected_count' => $validationReport->rejectedCount,
                'evidence_sufficient' => $validationReport->evidenceSufficient,
            ],
            'synthesis_status' => $synthesisReport->status,
            'source_types_used' => $validationReport->observability['source_types_used'] ?? [],
            'internet_first' => $plan->isInternetFirst(),
            'pipeline' => 'agricultural_research_agent_stage_5',
        ];
    }

    /**
     * @param  list<ResearchAnswerCitation>  $citations
     * @return list<array<string, mixed>>
     */
    private function scientificReferences(array $citations): array
    {
        $references = [];
        foreach ($citations as $citation) {
            $reference = $citation->toScientificReference();
            if ($this->sourceValidator->isVerifiedSource($reference)) {
                $key = (string) ($reference['url'] ?? $reference['doi'] ?? json_encode($reference));
                $references[$key] = $reference;
            }
        }

        return array_values($references);
    }

    /**
     * @param  list<array<string, mixed>>  $references
     */
    private function sourceAttribution(array $references): ?string
    {
        if ($references === []) {
            return null;
        }

        $first = $references[0];
        $org = (string) ($first['organization'] ?? '');
        $title = (string) ($first['title'] ?? '');

        return trim($org.' — '.$title) ?: null;
    }

    private function slugFor(KnowledgeQueryPlan $plan): string
    {
        $parts = [
            mb_strtolower(trim($plan->normalizedQuery->normalizedQuestion)),
            mb_strtolower(trim($plan->agriculturalDomain)),
            mb_strtolower(trim((string) ($plan->subjectEntity['value'] ?? ''))),
            mb_strtolower(trim((string) ($plan->normalizedQuery->cropId ?? ''))),
        ];
        $hash = substr(md5(implode('|', array_filter($parts))), 0, 16);

        return 'research-knowledge-'.$hash;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $evidence
     */
    private function evidenceFingerprint(array $evidence): string
    {
        $ids = array_map(
            static fn (ScientificEvidenceItem $item): string => $item->evidenceId,
            $evidence,
        );
        sort($ids);

        return md5(implode('|', $ids));
    }

    /**
     * @param  list<ScientificEvidenceItem>  $evidence
     */
    private function newestPublicationYear(array $evidence): int
    {
        $years = array_filter(array_map(
            static fn (ScientificEvidenceItem $item): int => (int) ($item->publicationYear ?? 0),
            $evidence,
        ));

        return $years === [] ? 0 : max($years);
    }

    private function titleFor(KnowledgeQueryPlan $plan, string $language): string
    {
        $query = trim($plan->normalizedQuery->originalQuestion);
        if ($query === '') {
            return $language === 'ar' ? 'معرفة زراعية موثقة' : 'Verified agricultural knowledge';
        }

        return mb_strlen($query) > 200 ? mb_substr($query, 0, 197).'...' : $query;
    }

    private function skippedReport(string $status, string $reason): KnowledgePersistenceExecutionReport
    {
        return new KnowledgePersistenceExecutionReport(
            status: $status,
            performed: false,
            libraryItemId: null,
            slug: null,
            action: 'skipped',
            provenance: null,
            observability: [
                'failure_reason' => $reason,
                'internet_first_preserved' => true,
            ],
        );
    }
}
