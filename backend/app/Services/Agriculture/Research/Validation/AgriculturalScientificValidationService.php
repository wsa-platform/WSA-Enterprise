<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Stage 4 scientific evidence validation pipeline.
 *
 * Search Outcome → Identity → Quality → Metadata → Extraction → Claim Match → Conflict → Ranking
 */
class AgriculturalScientificValidationService
{
    /** @var list<string> */
    private const VALIDATORS_USED = [
        'scientific_metadata_validator',
        'scientific_source_identity_validator',
        'scientific_source_quality_validator',
        'scientific_evidence_extractor',
        'claim_evidence_matcher',
        'evidence_conflict_detector',
        'evidence_quality_ranker',
    ];

    public function __construct(
        private ScientificMetadataValidator $metadataValidator,
        private ScientificSourceIdentityValidator $identityValidator,
        private ScientificSourceQualityValidator $qualityValidator,
        private ScientificEvidenceExtractor $evidenceExtractor,
        private ClaimEvidenceMatcher $claimMatcher,
        private EvidenceConflictDetector $conflictDetector,
        private EvidenceQualityRanker $qualityRanker,
    ) {}

    public function validate(
        KnowledgeQueryPlan $plan,
        ScientificSearchExecutionReport $searchReport,
    ): EvidenceValidationExecutionReport {
        if ($searchReport->status === 'needs_clarification') {
            return $this->emptyReport('needs_clarification', $searchReport);
        }

        if ($searchReport->deduplicatedResults === []) {
            return $this->emptyReport('no_search_results', $searchReport);
        }

        $seenDois = [];
        $duplicateCount = 0;
        $items = [];
        $retrievedAt = now()->toIso8601String();

        foreach ($searchReport->deduplicatedResults as $result) {
            $doiKey = $result->doi !== null ? strtolower(trim($result->doi)) : null;
            $isDuplicate = $doiKey !== null && isset($seenDois[$doiKey]);
            if ($doiKey !== null) {
                $seenDois[$doiKey] = true;
            }
            if ($isDuplicate) {
                $duplicateCount++;
            }

            $items[] = $this->validateResult($plan, $result, $retrievedAt, $isDuplicate);
        }

        $items = $this->conflictDetector->detect($items);
        $items = $this->qualityRanker->rank($items);

        $validated = array_values(array_filter($items, fn (ScientificEvidenceItem $item): bool => $item->isUsable()));
        $rejected = array_values(array_filter($items, fn (ScientificEvidenceItem $item): bool => $item->isRejected()));

        $conflictingCount = count(array_filter($items, fn (ScientificEvidenceItem $item): bool => $item->hasConflict));
        $supportedCount = count(array_filter(
            $validated,
            fn (ScientificEvidenceItem $item): bool => in_array(
                $item->claimRelationship,
                [ClaimEvidenceRelationship::SUPPORTED, ClaimEvidenceRelationship::PARTIALLY_SUPPORTED],
                true,
            ),
        ));

        $evidenceSufficient = $supportedCount >= 1;
        $status = match (true) {
            $validated !== [] => 'validation_completed',
            $items !== [] => 'validation_completed_with_rejections',
            default => 'no_valid_evidence',
        };

        $qualityDistribution = $this->qualityDistribution($items);

        return new EvidenceValidationExecutionReport(
            status: $status,
            validatedEvidence: $validated,
            rejectedEvidence: $rejected,
            sourcesReceived: count($searchReport->deduplicatedResults),
            validatedCount: count($validated),
            rejectedCount: count($rejected),
            duplicateCount: $duplicateCount,
            conflictingCount: $conflictingCount,
            evidenceSufficient: $evidenceSufficient,
            validatorsUsed: self::VALIDATORS_USED,
            qualityDistribution: $qualityDistribution,
            searchSummary: [
                'search_status' => $searchReport->status,
                'internet_first' => $searchReport->internetFirst,
                'search_query' => $searchReport->searchQuery,
                'selected_sources' => $searchReport->selectedSources,
                'attempted_sources' => $searchReport->attemptedSources,
                'successful_sources' => $searchReport->successfulSources,
            ],
            observability: [
                'failure_reasons' => $this->collectFailureReasons($items),
                'source_types_used' => array_values(array_unique(array_filter(array_map(
                    fn (ScientificEvidenceItem $item): ?string => $item->sourceType,
                    $items,
                )))),
                'validation_status_counts' => $this->statusCounts($items),
            ],
        );
    }

    private function validateResult(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        string $retrievedAt,
        bool $isDuplicate,
    ): ScientificEvidenceItem {
        $metadata = $this->metadataValidator->validate($result);
        $identity = $this->identityValidator->validate($result);
        $quality = $this->qualityValidator->validate($result);
        $extraction = $this->evidenceExtractor->extract($result, $plan);

        $failures = array_values(array_unique(array_merge(
            $metadata['failures'],
            $identity['failures'],
            $quality['failures'],
        )));

        if ($isDuplicate) {
            $failures[] = 'duplicate_result';
        }

        $validationStatus = $this->resolveStatus($metadata, $identity, $quality, $extraction, $failures);
        $claimMatch = $this->claimMatcher->match($plan, $result, $extraction['text'], $validationStatus);

        if ($validationStatus !== EvidenceValidationStatus::REJECTED
            && in_array($claimMatch['relationship'], [
                ClaimEvidenceRelationship::SUPPORTED,
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ], true)
            && $extraction['text'] !== null
            && $quality['trusted']) {
            $validationStatus = EvidenceValidationStatus::EVIDENCE_USABLE;
        } elseif ($validationStatus !== EvidenceValidationStatus::REJECTED
            && $claimMatch['relationship'] === ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE) {
            $failures[] = 'insufficient_evidence';
            if ($validationStatus === EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY) {
                $validationStatus = EvidenceValidationStatus::METADATA_VALID;
            }
        }

        if ($validationStatus === EvidenceValidationStatus::REJECTED) {
            $claimMatch = [
                'relationship' => ClaimEvidenceRelationship::NOT_VALIDATED,
                'confidence' => 0.0,
                'factors' => ['reason' => 'validation_rejected'],
            ];
        }

        $qualityScore = $this->qualityRanker->score(
            $metadata,
            $quality,
            $claimMatch,
            $result->publicationYear,
            $isDuplicate,
        );

        $source = is_array($quality['source'] ?? null) ? $quality['source'] : [];
        $sourceId = (string) ($result->sourceIdentifier ?? $result->doi ?? $result->canonicalUrl ?? md5($result->title));
        $evidenceId = md5($sourceId.'|'.$result->title);

        $cropOrEntity = $plan->normalizedQuery->cropId
            ?? (is_array($plan->subjectEntity) ? ($plan->subjectEntity['value'] ?? null) : null);

        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: $sourceId,
            sourceKey: $result->sourceKey,
            sourceType: isset($source['source_type']) ? (string) $source['source_type'] : null,
            publicationTitle: $result->title,
            authors: $result->authors,
            institution: isset($source['organization']) ? (string) $source['organization'] : null,
            journal: $result->journal,
            doi: $result->doi,
            url: $result->canonicalUrl,
            publicationYear: $result->publicationYear,
            retrievedAt: $retrievedAt,
            agriculturalDomain: $plan->agriculturalDomain,
            claimTopic: $extraction['claim_topic'],
            evidenceText: $extraction['text'],
            validationStatus: $validationStatus,
            validationFailures: $failures,
            claimRelationship: (string) $claimMatch['relationship'],
            confidence: (float) $claimMatch['confidence'],
            qualityScore: (float) $qualityScore['score'],
            qualityFactors: $qualityScore['factors'],
            sourceAttribution: [
                'organization' => $source['organization'] ?? null,
                'source_type' => $source['source_type'] ?? null,
                'found_by_sources' => $result->foundBySources,
                'confidence_level' => $quality['confidence_level'] ?? null,
            ],
            cropOrEntity: is_string($cropOrEntity) ? $cropOrEntity : null,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $quality
     * @param  array<string, mixed>  $extraction
     * @param  list<string>  $failures
     */
    private function resolveStatus(
        array $metadata,
        array $identity,
        array $quality,
        array $extraction,
        array $failures,
    ): string {
        if (($metadata['status'] ?? '') === EvidenceValidationStatus::REJECTED) {
            return EvidenceValidationStatus::REJECTED;
        }

        if (($quality['status'] ?? '') === EvidenceValidationStatus::REJECTED && ($quality['trusted'] ?? false) === false) {
            return EvidenceValidationStatus::REJECTED;
        }

        if (($identity['status'] ?? '') === EvidenceValidationStatus::REJECTED) {
            return EvidenceValidationStatus::REJECTED;
        }

        if (($quality['trusted'] ?? false) === true) {
            return EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY;
        }

        if (($identity['status'] ?? '') === EvidenceValidationStatus::SOURCE_IDENTITY_VALID) {
            return EvidenceValidationStatus::SOURCE_IDENTITY_VALID;
        }

        if (($metadata['status'] ?? '') === EvidenceValidationStatus::METADATA_VALID) {
            return EvidenceValidationStatus::METADATA_VALID;
        }

        if ($extraction['completeness'] === 'insufficient') {
            return EvidenceValidationStatus::REJECTED;
        }

        return EvidenceValidationStatus::DISCOVERED;
    }

    private function emptyReport(string $status, ScientificSearchExecutionReport $searchReport): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: $status,
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: count($searchReport->deduplicatedResults),
            validatedCount: 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: false,
            validatorsUsed: self::VALIDATORS_USED,
            qualityDistribution: [],
            searchSummary: [
                'search_status' => $searchReport->status,
                'internet_first' => $searchReport->internetFirst,
                'search_query' => $searchReport->searchQuery,
            ],
            observability: [
                'failure_reasons' => [$status],
                'source_types_used' => [],
                'validation_status_counts' => [],
            ],
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return array<string, int>
     */
    private function qualityDistribution(array $items): array
    {
        $distribution = ['high' => 0, 'medium' => 0, 'low' => 0];
        foreach ($items as $item) {
            if ($item->qualityScore >= 60) {
                $distribution['high']++;
            } elseif ($item->qualityScore >= 30) {
                $distribution['medium']++;
            } else {
                $distribution['low']++;
            }
        }

        return $distribution;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return array<string, int>
     */
    private function collectFailureReasons(array $items): array
    {
        $reasons = [];
        foreach ($items as $item) {
            foreach ($item->validationFailures as $failure) {
                $reasons[$failure] = ($reasons[$failure] ?? 0) + 1;
            }
        }

        return $reasons;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return array<string, int>
     */
    private function statusCounts(array $items): array
    {
        $counts = [];
        foreach ($items as $item) {
            $counts[$item->validationStatus] = ($counts[$item->validationStatus] ?? 0) + 1;
        }

        return $counts;
    }
}
