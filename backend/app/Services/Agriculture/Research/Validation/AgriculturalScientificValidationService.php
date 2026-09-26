<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimSupportAssessor;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatObservationRelevanceGate;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSupportState;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceModality;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Search\ScientificStructuredObservation;

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
        'evidence_verification_layer',
        'statistical_observation_validator',
    ];

    public function __construct(
        private ScientificMetadataValidator $metadataValidator,
        private ScientificSourceIdentityValidator $identityValidator,
        private ScientificSourceQualityValidator $qualityValidator,
        private ScientificEvidenceExtractor $evidenceExtractor,
        private ClaimEvidenceMatcher $claimMatcher,
        private EvidenceConflictDetector $conflictDetector,
        private EvidenceQualityRanker $qualityRanker,
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
        private EvidenceVerificationLayer $evidenceVerificationLayer,
        private FaoStatClaimSupportAssessor $faostatClaimSupport,
        private ScientificStatisticalClaimAligner $statisticalClaimAligner,
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
        $retained = array_values(array_filter(
            $items,
            static fn (ScientificEvidenceItem $item): bool => ! $item->isUsable() && ! $item->isRejected(),
        ));

        $conflictingCount = count(array_filter($items, fn (ScientificEvidenceItem $item): bool => $item->hasConflict));
        $supportedCount = count(array_filter(
            $validated,
            fn (ScientificEvidenceItem $item): bool => in_array(
                $item->claimRelationship,
                [ClaimEvidenceRelationship::SUPPORTED, ClaimEvidenceRelationship::PARTIALLY_SUPPORTED],
                true,
            ),
        ));

        $directCount = count(array_filter(
            $validated,
            fn (ScientificEvidenceItem $item): bool => $this->isDirectClass($item),
        ));
        $supportingCount = count(array_filter(
            $validated,
            static fn (ScientificEvidenceItem $item): bool => ($item->qualityFactors['evidence_directness'] ?? null)
                === ScientificEvidenceDirectnessAssessor::SUPPORTING,
        ));
        $answerEligibleSupportingCount = count(array_filter(
            $validated,
            static fn (ScientificEvidenceItem $item): bool => ($item->qualityFactors['answer_eligible'] ?? false) === true
                && ($item->qualityFactors['evidence_directness'] ?? null) !== ScientificEvidenceDirectnessAssessor::DIRECT,
        ));
        $backgroundCount = count(array_filter(
            $validated,
            static fn (ScientificEvidenceItem $item): bool => ($item->qualityFactors['evidence_directness'] ?? null)
                === ScientificEvidenceDirectnessAssessor::BACKGROUND,
        ));
        $maxConfidence = 0.0;
        foreach ($validated as $item) {
            $maxConfidence = max($maxConfidence, $item->confidence);
        }

        $requiresCropTopic = ($plan->normalizedQuery->cropId !== null
                || $plan->normalizedQuery->scientificName !== null)
            && is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            && ($plan->normalizedQuery->constraints['scientific_factors'] ?? []) !== [];

        $requiresFactualDirect = $this->requiresFactualDirectEvidence($plan);

        // Verified-answer sufficiency matches Phase 5: DIRECT is required.
        // Broader availability remains observable as evidence_capability.
        $evidenceCapability = match (true) {
            $directCount >= 1 => true,
            $requiresFactualDirect && $answerEligibleSupportingCount >= 2 => true,
            $requiresFactualDirect && $directCount === 0 => false,
            $requiresCropTopic && $backgroundCount > 0 && $directCount === 0 && $supportingCount === 0 => false,
            $supportingCount >= 1 && $supportedCount >= 1 => true,
            $supportedCount >= 1 && ! $requiresCropTopic => true,
            $supportedCount >= 2 => true,
            default => false,
        };
        $evidenceSufficient = $directCount >= 1;

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
            searchSummary: $this->buildSearchSummary(
                $searchReport,
                [
                    'direct_evidence_count' => $directCount,
                    'supporting_evidence_count' => $supportingCount,
                    'answer_eligible_supporting_count' => $answerEligibleSupportingCount,
                ],
            ),
            retainedEvidence: $retained,
            observability: [
                'failure_reasons' => $this->collectFailureReasons($items),
                'source_types_used' => array_values(array_unique(array_filter(array_map(
                    fn (ScientificEvidenceItem $item): ?string => $item->sourceType,
                    $items,
                )))),
                'validation_status_counts' => $this->statusCounts($items),
                'evidence_directness_counts' => [
                    'direct' => $directCount,
                    'supporting' => $supportingCount,
                    'answer_eligible_supporting' => $answerEligibleSupportingCount,
                ],
                'evidence_capability' => $evidenceCapability,
                'item_dispositions' => $this->itemDispositions($items),
                // Phase-4: preserve Phase-3 FAOSTAT pipeline taxonomy (do not collapse to empty).
                'faostat_pipeline_outcome' => $searchReport->planSummary['faostat_pipeline_outcome'] ?? null,
            ],
        );
    }

    private function validateResult(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        string $retrievedAt,
        bool $isDuplicate,
    ): ScientificEvidenceItem {
        if ($this->isStatisticalEvidence($result)) {
            return $this->validateStatisticalEvidence($plan, $result, $retrievedAt, $isDuplicate);
        }

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

        $directness = $this->evidenceVerificationLayer->assess(
            $plan,
            $result,
            $extraction['text'] ?? $result->abstract,
            $result->doi,
        );
        // Directness usefulness is scored inside EvidenceQualityRanker; keep factors authoritative.
        $qualityScore['factors']['evidence_directness'] = $directness['directness'];
        $qualityScore['factors']['verification_label'] = $directness['verification_label'] ?? null;
        $qualityScore['factors']['directness_score'] = $directness['score'];
        $qualityScore['factors']['directness_reasons'] = $directness['reasons'];
        $qualityScore['factors']['entity_matched'] = (bool) ($directness['entity_matched'] ?? false);
        $qualityScore['factors']['topic_matched'] = (bool) ($directness['topic_matched'] ?? false);
        $qualityScore['factors']['sense_coverage'] = (bool) ($directness['sense_coverage'] ?? false);
        $qualityScore['factors']['factor_coverage'] = (float) ($directness['factor_coverage'] ?? 0.0);
        $qualityScore['factors'] = array_merge(
            $this->preservedUpstreamFacts($result),
            $qualityScore['factors'],
        );
        $qualityScore['factors']['evidence_modality'] = ScientificEvidenceModality::fromResult($result);
        $qualityScore['factors']['ranking_class'] = ScientificEvidenceDirectnessAssessor::rankingClass(
            (string) $directness['directness'],
        );
        $qualityScore['factors']['answer_eligible'] = $directness['directness'] === ScientificEvidenceDirectnessAssessor::DIRECT
            || $this->evidenceVerificationLayer->isAnswerEligibleSupporting(
                (string) $directness['directness'],
                $directness,
            );
        // Re-rank score after authoritative Stage-3/verification directness (claimMatch may have run first).
        if ($directness['directness'] === ScientificEvidenceDirectnessAssessor::DIRECT
            && ($claimMatch['factors']['evidence_directness'] ?? null) !== ScientificEvidenceDirectnessAssessor::DIRECT) {
            $qualityScore['score'] = min(100.0, $qualityScore['score'] + 18.0);
        } elseif (in_array($directness['directness'], [
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED,
        ], true)) {
            $qualityScore['score'] = max(0.0, $qualityScore['score'] - 12.0);
        } elseif ($directness['directness'] === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH) {
            $qualityScore['score'] = max(0.0, $qualityScore['score'] - 40.0);
            if ($claimMatch['relationship'] !== ClaimEvidenceRelationship::NOT_VALIDATED) {
                $claimMatch['relationship'] = ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE;
                $claimMatch['confidence'] = min((float) $claimMatch['confidence'], 0.08);
                $claimMatch['factors']['evidence_directness'] = ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH;
            }
        }

        // Semantic score is a weak ranking aid only — never upgrades to DIRECT.
        $semantic = is_array($result->relevanceMetadata) ? ($result->relevanceMetadata['semantic_score'] ?? null) : null;
        if (is_numeric($semantic) && $directness['directness'] !== ScientificEvidenceDirectnessAssessor::DIRECT
            && $directness['directness'] !== ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH
            && $directness['directness'] !== ScientificEvidenceDirectnessAssessor::IRRELEVANT) {
            $qualityScore['score'] = min(100.0, $qualityScore['score'] + min(4.0, ((float) $semantic) * 0.5));
            $qualityScore['factors']['semantic_score_boost'] = min(4.0, ((float) $semantic) * 0.5);
        }

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
                'evidence_directness' => $directness['directness'],
                'evidence_modality' => ScientificEvidenceModality::fromResult($result),
                'verification_label' => $directness['verification_label'] ?? null,
                'provenance' => $this->provenanceFromResult($result),
            ],
            cropOrEntity: is_string($cropOrEntity) ? $cropOrEntity : null,
        );
    }

    private function isStatisticalEvidence(ScientificSearchResult $result): bool
    {
        return ScientificEvidenceModality::isDirectStatistical($result);
    }

    private function validateStatisticalEvidence(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        string $retrievedAt,
        bool $isDuplicate,
    ): ScientificEvidenceItem {
        $observationBag = $this->observationBag($result);
        $observation = ScientificStructuredObservation::fromResult($result);
        $alignment = $observation !== null
            ? $this->statisticalClaimAligner->assess($plan, $observation)
            : ['relevant' => false, 'mismatches' => ['observation']];

        $claimMatch = $this->faostatClaimSupport->assess($plan, $result);
        $assessorState = (string) ($claimMatch['factors']['faostat_support_state'] ?? '');
        $rawMeta = is_array($result->rawMetadata) ? $result->rawMetadata : [];
        $hasOfficialStatisticalBag = is_array($rawMeta['faostat'] ?? null);
        $assessorRelevant = ($claimMatch['factors']['faostat_relevance'] ?? null)
            === FaoStatObservationRelevanceGate::RELEVANT;

        $complete = $observation !== null && $observation->isComplete();
        $aligned = $alignment['relevant'] === true;
        $claimBlocked = in_array((string) ($claimMatch['factors']['faostat_support_reason'] ?? ''), [
            'causal_claim_not_statistical',
            'recommendation_claim_not_statistical',
        ], true);
        $relevant = match (true) {
            $claimBlocked => false,
            $hasOfficialStatisticalBag && $assessorState !== FaoStatSupportState::NOT_APPLICABLE => $assessorRelevant,
            default => $complete && $aligned,
        };

        $failures = $isDuplicate ? ['duplicate_result'] : [];
        if ($observationBag === [] || ! $complete) {
            $failures[] = 'statistical_observation_incomplete';
        }
        if ($hasOfficialStatisticalBag && $assessorState !== FaoStatSupportState::NOT_APPLICABLE && ! $assessorRelevant) {
            $failures[] = 'statistical_not_relevant';
        }
        if (! $hasOfficialStatisticalBag && ! $aligned) {
            $failures[] = 'statistical_claim_mismatch';
        }

        $validationStatus = $relevant && ! $isDuplicate && $complete
            ? EvidenceValidationStatus::EVIDENCE_USABLE
            : EvidenceValidationStatus::REJECTED;

        if (! $claimBlocked && (! $hasOfficialStatisticalBag || $assessorState === FaoStatSupportState::NOT_APPLICABLE)) {
            $claimMatch = [
                'relationship' => $relevant
                    ? ClaimEvidenceRelationship::SUPPORTED
                    : ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => $relevant ? 0.8 : 0.0,
                'factors' => [
                    'evidence_modality' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                    'statistical_alignment' => $alignment,
                ],
            ];
        }

        $directness = $relevant
            ? ScientificEvidenceDirectnessAssessor::DIRECT
            : ScientificEvidenceDirectnessAssessor::IRRELEVANT;
        $sourceId = (string) ($result->sourceIdentifier ?? $result->canonicalUrl ?? md5($result->title));
        $cropOrEntity = $plan->normalizedQuery->cropId
            ?? (is_array($plan->subjectEntity) ? ($plan->subjectEntity['value'] ?? null) : null);
        $raw = is_array($result->rawMetadata) ? $result->rawMetadata : [];
        $organization = is_string($raw['organization'] ?? null) && $raw['organization'] !== ''
            ? $raw['organization']
            : (is_string($observationBag['source'] ?? null) && $observationBag['source'] !== ''
                ? (string) $observationBag['source']
                : null);

        return new ScientificEvidenceItem(
            evidenceId: md5($sourceId.'|'.$result->title),
            sourceId: $sourceId,
            sourceKey: $result->sourceKey,
            sourceType: 'official_statistics',
            publicationTitle: $result->title,
            authors: $result->authors,
            institution: $organization,
            journal: $result->journal,
            doi: $result->doi,
            url: $result->canonicalUrl,
            publicationYear: $result->publicationYear,
            retrievedAt: $retrievedAt,
            agriculturalDomain: $plan->agriculturalDomain,
            claimTopic: $observation !== null && $observation->property !== ''
                ? $observation->property
                : 'official_statistics',
            evidenceText: $result->abstract,
            validationStatus: $validationStatus,
            validationFailures: $failures,
            claimRelationship: (string) $claimMatch['relationship'],
            confidence: (float) $claimMatch['confidence'],
            qualityScore: $relevant ? 80.0 : 0.0,
            qualityFactors: array_merge($this->preservedUpstreamFacts($result), $claimMatch['factors'], [
                'not_literature' => true,
                'answer_eligible' => $relevant,
                'evidence_modality' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                'evidence_directness' => $directness,
                'ranking_class' => ScientificEvidenceDirectnessAssessor::rankingClass($directness),
                'observation' => $observationBag,
                'observation_year' => $observationBag['year'] ?? ($observation?->year ?: null),
                'observation_location' => $observationBag['area'] ?? ($observation?->location ?: null),
                'observation_entity' => $observationBag['item'] ?? ($observation?->entity ?: null),
                'observation_measure' => $observationBag['element'] ?? ($observation?->property ?: null),
                'observation_unit' => $observationBag['unit'] ?? ($observation?->unit ?: null),
                'observation_value' => $observationBag['value'] ?? ($observation?->value ?: null),
                'statistical_alignment' => $alignment,
            ]),
            sourceAttribution: [
                'organization' => $organization,
                'source_type' => 'official_statistics',
                'found_by_sources' => $result->foundBySources,
                'evidence_directness' => $directness,
                'evidence_modality' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                'provenance' => $this->provenanceFromResult($result),
                'observation' => $observationBag,
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
            searchSummary: $this->buildSearchSummary($searchReport, [
                'direct_evidence_count' => 0,
                'supporting_evidence_count' => 0,
                'answer_eligible_supporting_count' => 0,
            ]),
            observability: [
                'failure_reasons' => [$status],
                'source_types_used' => [],
                'validation_status_counts' => [],
                'faostat_pipeline_outcome' => $searchReport->planSummary['faostat_pipeline_outcome'] ?? null,
                'evidence_directness_counts' => [
                    'direct' => 0,
                    'supporting' => 0,
                    'answer_eligible_supporting' => 0,
                ],
                'evidence_capability' => false,
                'item_dispositions' => [],
            ],
        );
    }

    /**
     * Propagate Phase-3 search observability into validation without collapsing outcomes.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function buildSearchSummary(ScientificSearchExecutionReport $searchReport, array $extra = []): array
    {
        $summary = [
            'search_status' => $searchReport->status,
            'internet_first' => $searchReport->internetFirst,
            'search_query' => $searchReport->searchQuery,
            'search_queries' => $searchReport->searchQueries !== []
                ? $searchReport->searchQueries
                : [$searchReport->searchQuery],
            'selected_sources' => $searchReport->selectedSources,
            'attempted_sources' => $searchReport->attemptedSources,
            'successful_sources' => $searchReport->successfulSources,
            'failed_sources' => $searchReport->failedSources,
            // Phase-4 handoff: keep FAOSTAT retrieval vs downstream rejection distinguishable.
            'faostat_pipeline_outcome' => $searchReport->planSummary['faostat_pipeline_outcome'] ?? null,
            'result_pipeline' => $searchReport->planSummary['result_pipeline'] ?? null,
        ];

        foreach ($extra as $key => $value) {
            $summary[$key] = $value;
        }

        return $summary;
    }

    /**
     * Classification / temperature / timing / requirement / recommended-range questions
     * require DIRECT evidence; piles of SUPPORTING alone are not sufficient.
     * Entity-less general/industry questions may still use supporting evidence.
     */
    private function requiresFactualDirectEvidence(KnowledgeQueryPlan $plan): bool
    {
        $subjectType = is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null;
        $hasEntity = $plan->normalizedQuery->cropId !== null
            || $plan->normalizedQuery->scientificName !== null
            || $subjectType === 'crop'
            || $subjectType === 'plant_family';
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        if ($hasEntity && $factors !== []) {
            return true;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        // Sequential checks keep plant_family staging separable from land WIP.
        if ($sense === 'plant_family_members') {
            return true;
        }
        if ($sense === 'land_classification') {
            return true;
        }

        if (! $hasEntity) {
            return false;
        }

        if (in_array($sense, [
            'seed_germination',
            'crop_water_requirement',
            'salinity_physiology',
            'drying_processing',
            'storage',
            'plant_growth',
            'varieties',
        ], true)) {
            return true;
        }

        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));
        if (in_array($qualifier, ['optimal_range', 'requirement'], true)) {
            return true;
        }

        foreach (['temperature', 'germination', 'water', 'salinity', 'drying', 'storage'] as $factualFactor) {
            if (in_array($factualFactor, $factors, true)) {
                return true;
            }
        }

        return false;
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

    private function isDirectClass(ScientificEvidenceItem $item): bool
    {
        return ScientificEvidenceDirectnessAssessor::rankingClass(
            (string) ($item->qualityFactors['evidence_directness'] ?? ''),
        ) === 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function observationBag(ScientificSearchResult $result): array
    {
        $raw = is_array($result->rawMetadata) ? $result->rawMetadata : [];
        foreach (['observation', 'statistical', 'faostat', 'structured'] as $key) {
            if (is_array($raw[$key] ?? null) && $raw[$key] !== []) {
                return $raw[$key];
            }
        }
        $meta = is_array($result->relevanceMetadata) ? $result->relevanceMetadata : [];
        if (is_array($meta['observation'] ?? null) && $meta['observation'] !== []) {
            return $meta['observation'];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function preservedUpstreamFacts(ScientificSearchResult $result): array
    {
        $meta = is_array($result->relevanceMetadata) ? $result->relevanceMetadata : [];
        $facts = [];
        $bag = $this->observationBag($result);
        if ($bag !== []) {
            $facts['observation'] = $bag;
        }
        if (array_key_exists('species_relation', $meta)) {
            $facts['species_relation'] = $meta['species_relation'];
        }
        if ($result->relevanceScore !== null) {
            $facts['stage3_relevance_score'] = $result->relevanceScore;
        }
        if (isset($meta['evidence_directness'])) {
            $facts['stage3_directness'] = $meta['evidence_directness'];
        }
        if (isset($meta['document_geo_scope'])) {
            $facts['document_geo_scope'] = $meta['document_geo_scope'];
        }

        return $facts;
    }

    /**
     * @return array<string, mixed>
     */
    private function provenanceFromResult(ScientificSearchResult $result): array
    {
        $raw = is_array($result->rawMetadata) ? $result->rawMetadata : [];
        $meta = is_array($result->relevanceMetadata) ? $result->relevanceMetadata : [];
        $existing = [];
        if (is_array($raw['provenance'] ?? null)) {
            $existing = $raw['provenance'];
        } elseif (is_array($meta['provenance'] ?? null)) {
            $existing = $meta['provenance'];
        }

        $merged = array_merge($existing, [
            'found_by_sources' => $result->foundBySources,
            'source_key' => $result->sourceKey,
            'source_identifier' => $result->sourceIdentifier,
        ]);
        $pdfUrl = trim((string) ($meta['open_access_pdf_url'] ?? $meta['pdf_url'] ?? $merged['open_access_pdf_url'] ?? ''));
        if ($pdfUrl !== '') {
            $merged['open_access_pdf_url'] = $pdfUrl;
        }

        return $merged;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<array<string, mixed>>
     */
    private function itemDispositions(array $items): array
    {
        $dispositions = [];
        foreach ($items as $item) {
            $disposition = match (true) {
                $item->hasConflict => 'conflicting',
                $item->isUsable() => 'usable',
                $item->isRejected() => 'rejected',
                $item->validationStatus === EvidenceValidationStatus::METADATA_VALID => 'metadata_valid',
                $item->claimRelationship === ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE => 'insufficient',
                default => $item->validationStatus,
            };
            $dispositions[] = [
                'evidence_id' => $item->evidenceId,
                'source_key' => $item->sourceKey,
                'validation_status' => $item->validationStatus,
                'disposition' => $disposition,
                'evidence_directness' => $item->qualityFactors['evidence_directness'] ?? null,
                'evidence_modality' => $item->qualityFactors['evidence_modality'] ?? null,
            ];
        }

        return $dispositions;
    }
}
