<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceModality;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Search\ScientificStructuredObservation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceRegistry;
use App\Services\Agriculture\ScientificSourceValidator;

/**
 * Stage 5 evidence-bound answer synthesis.
 *
 * Does not browse the Internet, invent evidence, or bypass Stage 4 validation.
 * Background-only leftovers and off-topic abstract sentences are not answers.
 *
 * R6 disposition: Composer may embed Home lifecycle metadata by delegating to
 * {@see HomeEvidenceLifecycleDisposition}; it must not redefine disposition rules.
 * Authoritative post-compose writer remains applyToSynthesis on Home/Agent/Universal.
 */
class AnswerComposer
{
    public function __construct(
        private ScientificSourceValidator $sourceValidator,
        private ScientificEvidenceRelevanceGate $relevanceGate,
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
        private EvidenceVerificationLayer $evidenceVerificationLayer,
        private ScientificStatisticalClaimAligner $statisticalClaimAligner,
        private HomeEvidenceLifecycleDisposition $homeEvidenceLifecycleDisposition = new HomeEvidenceLifecycleDisposition,
        private AnswerExpressionAccuracyGate $expressionAccuracyGate = new AnswerExpressionAccuracyGate,
    ) {}

    public function compose(
        KnowledgeQueryPlan $plan,
        EvidenceValidationExecutionReport $validationReport,
    ): AnswerSynthesisExecutionReport {
        // R2: answer language follows the question contract only — never UI/app locale.
        $language = $this->resolveComposeAnswerLanguage($plan);
        $query = $plan->normalizedQuery->originalQuestion;

        if (in_array($validationReport->status, ['needs_clarification', 'no_search_results'], true)) {
            return $this->insufficientReport(
                status: $validationReport->status,
                reason: $validationReport->status,
                language: $language,
                query: $query,
                plan: $plan,
                validationReport: $validationReport,
            );
        }

        $usable = array_values(array_filter(
            $validationReport->validatedEvidence,
            fn (ScientificEvidenceItem $item): bool => $this->isSynthesizable($item, $plan),
        ));

        if ($usable === []) {
            $supportingLabeled = array_values(array_filter(
                $validationReport->validatedEvidence,
                function (ScientificEvidenceItem $item): bool {
                    $directness = (string) ($item->qualityFactors['evidence_directness']
                        ?? $item->sourceAttribution['evidence_directness']
                        ?? '');

                    return in_array($directness, [
                        ScientificEvidenceDirectnessAssessor::SUPPORTING,
                        ScientificEvidenceDirectnessAssessor::SUPPORTED,
                    ], true);
                },
            ));
            if ($supportingLabeled !== [] && $this->requiresFactualDirectEvidence($plan)) {
                return $this->supportingOnlyInsufficientReport(
                    $plan,
                    $supportingLabeled,
                    $validationReport,
                    [
                        'sufficient' => false,
                        'partial' => false,
                        'reason' => 'insufficient_direct_evidence',
                        'mode' => 'insufficient_direct_evidence',
                        'direct_count' => 0,
                        'supporting_count' => count($supportingLabeled),
                    ],
                    $language,
                    $query,
                );
            }

            return $this->insufficientReport(
                status: 'no_validated_evidence',
                reason: 'no_relevant_validated_evidence',
                language: $language,
                query: $query,
                plan: $plan,
                rejectedCount: $validationReport->rejectedCount,
                validationReport: $validationReport,
            );
        }

        $sufficiency = $this->assessSynthesisSufficiency($usable, $validationReport, $plan);
        if (! $sufficiency['sufficient']) {
            // Supporting-only: clear insufficient-direct framing + optional additional info (never a confident main answer).
            if ($this->isSupportingOnlySufficiency($sufficiency)) {
                return $this->supportingOnlyInsufficientReport(
                    $plan,
                    $usable,
                    $validationReport,
                    $sufficiency,
                    $language,
                    $query,
                );
            }

            return $this->insufficientReport(
                status: 'insufficient_evidence',
                reason: $sufficiency['reason'],
                language: $language,
                query: $query,
                plan: $plan,
                rejectedCount: $validationReport->rejectedCount,
                validationReport: $validationReport,
            );
        }

        $citations = $this->buildCitations($usable, $sufficiency);
        $claims = $this->buildClaims($usable, $plan, $validationReport);
        if ($claims === []) {
            return $this->insufficientReport(
                status: 'insufficient_evidence',
                reason: 'no_grounded_claims',
                language: $language,
                query: $query,
                plan: $plan,
                rejectedCount: $validationReport->rejectedCount,
                validationReport: $validationReport,
            );
        }

        $conflicts = $this->buildConflicts($usable, $language, $plan);
        $supportingOnly = $this->isSupportingOnlySufficiency($sufficiency);
        // Findings may include supporting for metadata/additional; main answer body gates DIRECT-only.
        $keyFindings = $this->buildKeyFindings($claims, $usable, $plan, $language);
        $keyFindings = $this->appendDirectStatisticalMeasurements($keyFindings, $usable, $plan);
        if ($this->requiresSupportedMeasurement($plan)) {
            $keyFindings = $this->rejectFindingsWithUnsupportedNumbers($keyFindings, $plan);
            if ($this->collectedNumericalValues($keyFindings, $plan) === []) {
                $accuracyBlocked = false;
                foreach ($claims as $claim) {
                    if ($this->hasAccuracyLimitation($claim->limitations)) {
                        $accuracyBlocked = true;
                        break;
                    }
                }
                // B3: keep claim-traceable accuracy limitations instead of wiping the report.
                if (! $accuracyBlocked) {
                    return $this->insufficientReport(
                        status: 'insufficient_evidence',
                        reason: 'no_supported_property_measurement',
                        language: $language,
                        query: $query,
                        plan: $plan,
                        rejectedCount: $validationReport->rejectedCount,
                        validationReport: $validationReport,
                    );
                }
            }
        }
        $limitations = $this->buildLimitations($validationReport, $usable, $language, $sufficiency);
        foreach ($claims as $claim) {
            foreach ($claim->limitations as $limitation) {
                $limitation = trim((string) $limitation);
                if ($limitation === '') {
                    continue;
                }
                $phrased = $this->phraseUserFacingLimitation($language, $limitation);
                $entry = $phrased;
                if (! in_array($entry, $limitations, true)) {
                    $limitations[] = $entry;
                }
            }
        }
        $uncertainty = $this->resolveUncertainty(
            $validationReport,
            $usable,
            $conflicts,
            $language,
            $sufficiency,
            $keyFindings,
        );
        $confidence = $this->overallConfidence($claims, $validationReport, $sufficiency);
        $evidenceReferences = array_map(
            static fn (ScientificEvidenceItem $item): array => [
                'evidence_id' => $item->evidenceId,
                'source_id' => $item->sourceId,
                'publication_title' => $item->publicationTitle,
                'claim_relationship' => $item->claimRelationship,
                'validation_status' => $item->validationStatus,
                'has_conflict' => $item->hasConflict,
                'evidence_directness' => $item->qualityFactors['evidence_directness']
                    ?? ($item->sourceAttribution['evidence_directness'] ?? null),
            ],
            $usable,
        );

        $mainAnswerBody = $this->buildMainAnswerBody($keyFindings, $plan, $language, $sufficiency);
        $conciseSummary = $this->buildConciseSummary($keyFindings, $uncertainty, $language, $sufficiency, $plan);
        $additionalSection = $this->buildAdditionalInformationSection(
            $usable,
            // Supporting-only: main answer is the insufficient-direct message, so do not suppress additional against supporting findings.
            $supportingOnly ? [] : $keyFindings,
            $plan,
            $language,
            $supportingOnly,
        );
        $primarySourcesSection = $this->formatPrimarySourcesSection(
            $citations,
            $language,
            ($sufficiency['mode'] ?? '') === 'supported_answer',
        );
        $detailedExplanation = $this->buildDetailedExplanation(
            $primarySourcesSection,
            $additionalSection,
            $conflicts,
            $language,
        );
        $answer = $this->buildAnswer(
            $mainAnswerBody,
            $primarySourcesSection,
            $additionalSection,
            $uncertainty,
            $language,
        );
        $additionalInformation = trim($additionalSection) !== '' ? $additionalSection : null;

        $nonConflictClaims = count(array_filter(
            $claims,
            static fn (ResearchAnswerClaim $claim): bool => $claim->claimRelationship !== ClaimEvidenceRelationship::CONFLICTING,
        ));
        $status = match (true) {
            $conflicts !== [] && $nonConflictClaims === 0 && $keyFindings === [] => 'synthesis_completed_with_conflicts',
            $conflicts !== [] && $nonConflictClaims === 0 => 'synthesis_completed_with_partial_conflicts',
            $conflicts !== [] => 'synthesis_completed_with_partial_conflicts',
            $supportingOnly || ($sufficiency['partial'] ?? false) => 'synthesis_completed_partial',
            count($claims) < count($usable) => 'synthesis_completed_partial',
            default => 'synthesis_completed',
        };

        return new AnswerSynthesisExecutionReport(
            status: $status,
            performed: true,
            answer: $answer,
            conciseSummary: $conciseSummary,
            detailedExplanation: $detailedExplanation,
            keyFindings: $keyFindings,
            claims: $claims,
            citations: $citations,
            evidenceReferences: $evidenceReferences,
            confidence: $confidence,
            limitations: $limitations,
            uncertainty: $uncertainty,
            conflicts: $conflicts,
            language: $language,
            researchMetadata: [
                'query' => $query,
                'normalized_query' => $plan->normalizedQuery->normalizedQuestion,
                'research_intent' => $plan->researchIntent,
                'agricultural_domain' => $plan->agriculturalDomain,
                'scientific_sense' => $plan->normalizedQuery->constraints['scientific_sense'] ?? null,
                'scientific_intent_qualifier' => $plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? null,
                'subject_entity' => $plan->subjectEntity,
                'validation_status' => $validationReport->status,
                'evidence_sufficient' => $validationReport->evidenceSufficient && $sufficiency['sufficient']
                    && (
                        ((int) ($sufficiency['direct_count'] ?? 0)) >= 1
                        || ($sufficiency['mode'] ?? '') === 'supported_answer'
                    ),
                'internet_first' => $plan->isInternetFirst(),
                'synthesized_at' => now()->toIso8601String(),
                'direct_evidence_count' => $sufficiency['direct_count'],
                'supporting_evidence_count' => $sufficiency['supporting_count'],
                'answer_eligible_count' => $sufficiency['answer_eligible_count'] ?? 0,
                'sufficiency_mode' => $sufficiency['mode'] ?? $sufficiency['reason'],
                'answer_presentation_mode' => $this->resolvePresentationMode($plan),
                'question_type' => $plan->normalizedQuery->constraints['question_type'] ?? null,
                'required_evidence_type' => $plan->normalizedQuery->constraints['required_evidence_type'] ?? null,
                'direct_evidence_gate' => ((int) ($sufficiency['direct_count'] ?? 0)) >= 1
                    ? 'PASSED'
                    : (($sufficiency['mode'] ?? '') === 'supported_answer'
                        ? 'SUPPORTED_ANSWER_ELIGIBLE'
                        : 'INSUFFICIENT_DIRECT_EVIDENCE'),
                'primary_evidence' => array_map(
                    static fn (ResearchAnswerCitation $citation): array => [
                        'title' => $citation->title,
                        'authors' => $citation->authors,
                        'year' => $citation->publicationYear,
                        'doi' => $citation->doi,
                        'url' => $citation->url,
                        'provider' => $citation->sourceType,
                    ],
                    $citations,
                ),
            ] + $this->homeEvidenceLifecycleMetadata(
                $plan,
                $validationReport,
                composerEligibleCount: count($usable),
                disposition: 'composer_used',
            ),
            observability: [
                'usable_evidence_count' => count($usable),
                'claims_generated' => count($claims),
                'citations_mapped' => count($citations),
                'conflicts_detected' => count($conflicts),
                'independent_search' => false,
                'validation_bypassed' => false,
                'evidence_directness_filter' => true,
            ] + $this->phase5QuestionClaimMatrix($plan, $validationReport, $usable, $claims)
              + $this->homeEvidenceLifecycleMetadata(
                $plan,
                $validationReport,
                composerEligibleCount: count($usable),
                disposition: 'composer_used',
            ),
            additionalInformation: $additionalInformation,
        );
    }

    /**
     * Phase-5 Unit A — embed Question→Claims→Evidence→AnswerStatement matrix.
     * Does not own Phase-4 axes or R5 persistence.
     * Optional $claims overlay reports already-computed B3 accuracy_* codes onto traces.
     *
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  list<ResearchAnswerClaim>  $claims
     * @return array<string, mixed>
     */
    private function phase5QuestionClaimMatrix(
        KnowledgeQueryPlan $plan,
        ?EvidenceValidationExecutionReport $validationReport,
        array $usable = [],
        array $claims = [],
    ): array {
        $report = $validationReport ?? new EvidenceValidationExecutionReport(
            status: 'no_valid_evidence',
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: 0,
            validatedCount: 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: false,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
        $matrix = (new QuestionClaimSynthesisContract)->build($plan, $report, $usable);
        $traces = $this->overlayB3AccuracyOutcomesOnTraces(
            is_array($matrix['answer_statement_traces'] ?? null) ? $matrix['answer_statement_traces'] : [],
            $claims,
        );

        return [
            'phase5_unit_a' => true,
            'question_claims' => $matrix['question_claims'],
            'claim_evidence_bindings' => $matrix['claim_evidence_bindings'],
            'answer_statement_traces' => $traces,
            'question_claim_matrix_version' => $matrix['matrix_version'],
        ];
    }

    /**
     * Report B3 accuracy_* codes on traces. Does not re-run the gate or change Unit A status.
     *
     * @param  list<array<string, mixed>>  $traces
     * @param  list<ResearchAnswerClaim>  $claims
     * @return list<array<string, mixed>>
     */
    private function overlayB3AccuracyOutcomesOnTraces(array $traces, array $claims): array
    {
        $outcomesByClaim = [];
        foreach ($claims as $claim) {
            $questionClaimId = trim((string) $claim->questionClaimId);
            if ($questionClaimId === '') {
                continue;
            }
            foreach ($claim->limitations as $limitation) {
                $code = trim((string) $limitation);
                if ($code !== '' && str_starts_with($code, 'accuracy_')) {
                    $outcomesByClaim[$questionClaimId][] = $code;
                }
            }
        }

        foreach ($traces as $index => $trace) {
            if (! is_array($trace)) {
                continue;
            }
            $questionClaimId = (string) ($trace['question_claim_id'] ?? '');
            $outcomes = array_values(array_unique($outcomesByClaim[$questionClaimId] ?? []));
            $traces[$index]['accuracy_outcomes'] = $outcomes;
        }

        return $traces;
    }

    /**
     * R2: Composer consumes plan answer_language (question language).
     * Never uses app()/UI/platform locale. Missing answer_language falls back to
     * normalizedQuery.language only; unknown/empty aligns with QUS und→en.
     */
    private function resolveComposeAnswerLanguage(KnowledgeQueryPlan $plan): string
    {
        $fromContract = strtolower(substr(trim((string) ($plan->normalizedQuery->constraints['answer_language'] ?? '')), 0, 2));
        if (in_array($fromContract, ['en', 'ar', 'tr', 'fr'], true)) {
            return $fromContract;
        }

        $fromQuestion = strtolower(substr(trim((string) ($plan->normalizedQuery->language ?? '')), 0, 2));
        if (in_array($fromQuestion, ['en', 'ar', 'tr', 'fr'], true)) {
            return $fromQuestion;
        }

        // Contract gap: upstream should populate answer_language. Do not use UI locale.
        return 'en';
    }

    /**
     * Home-only lifecycle metadata — delegates to the single disposition owner (R6 / RC-E).
     *
     * @return array<string, mixed>
     */
    private function homeEvidenceLifecycleMetadata(
        KnowledgeQueryPlan $plan,
        ?EvidenceValidationExecutionReport $validationReport,
        int $composerEligibleCount,
        ?string $disposition = null,
    ): array {
        return $this->homeEvidenceLifecycleDisposition->classify(
            $plan,
            $validationReport,
            $composerEligibleCount,
            $disposition,
        );
    }

    /**
     * Preserve source identity for Home when evidence was retrieved but not used in the main answer.
     *
     * @return list<array<string, mixed>>
     */
    private function homeUnusedEvidenceReferences(
        KnowledgeQueryPlan $plan,
        EvidenceValidationExecutionReport $validationReport,
    ): array {
        return $this->homeEvidenceLifecycleDisposition->unusedEvidenceReferences($plan, $validationReport);
    }

    private function isSynthesizable(ScientificEvidenceItem $item, KnowledgeQueryPlan $plan): bool
    {
        if (! $item->isUsable()) {
            return false;
        }

        if ($this->isDirectStatisticalEvidence($item)) {
            if (! $this->directStatisticalObservationSupportsClaim($item, $plan)) {
                return false;
            }

            return $this->resolveDirectness($item, $plan) === ScientificEvidenceDirectnessAssessor::DIRECT;
        }

        if (! in_array($item->claimRelationship, [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ClaimEvidenceRelationship::CONFLICTING,
        ], true)) {
            return false;
        }

        if (! $this->relevanceGate->isRelevant(
            $plan,
            $item->publicationTitle,
            $item->evidenceText,
            $item->doi,
            $item->claimTopic !== '' ? $item->claimTopic : null,
        )) {
            return false;
        }

        $directness = $this->resolveDirectness($item, $plan);
        $strict = $this->requiresStrictGrounding($plan);
        if ($strict && in_array($directness, [
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
        ], true)) {
            return false;
        }

        // Synthesis may use DIRECT / SUPPORTING / SUPPORTED internally.
        // Primary citations[] stay DIRECT-only via isPrimaryCitationEligible().
        if (! in_array($directness, [
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        ], true)) {
            return false;
        }

        $propertyTerms = $plan->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        $propertyKey = trim((string) ($plan->normalizedQuery->constraints['requested_property'] ?? ''));
        $directnessIsPrimary = in_array($directness, [
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        ], true);
        if ($directnessIsPrimary && is_array($propertyTerms) && $propertyTerms !== []
            && ! in_array($propertyKey, ['general', 'definition', ''], true)) {
            $hay = mb_strtolower(trim(implode(' ', array_filter([
                $item->publicationTitle,
                $item->evidenceText,
            ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));
            if (! $this->haystackAddressesPropertyTerms($hay, $propertyTerms)) {
                return false;
            }
        }

        return true;
    }

    private function isDirectStatisticalEvidence(ScientificEvidenceItem $item): bool
    {
        $type = strtoupper(trim((string) ($item->qualityFactors['evidence_type'] ?? '')));
        $directness = strtolower(trim((string) ($item->qualityFactors['evidence_directness']
            ?? $item->sourceAttribution['evidence_directness']
            ?? '')));
        $sourceType = strtolower(trim((string) ($item->sourceType ?? '')));

        return ($item->qualityFactors['not_literature'] ?? false) === true
            || $type === ScientificEvidenceModality::DIRECT_STATISTICAL
            || $directness === 'direct_statistical'
            || $sourceType === 'official_statistics';
    }

    private function directStatisticalObservationSupportsClaim(
        ScientificEvidenceItem $item,
        KnowledgeQueryPlan $plan,
    ): bool {
        $observation = ScientificStructuredObservation::fromEvidenceItem($item);
        if ($observation === null || ! $observation->isComplete()) {
            return false;
        }

        $alignment = $this->statisticalClaimAligner->assess($plan, $observation);
        if (! $alignment['relevant']) {
            return false;
        }

        $value = preg_replace('/[^\d.,]/', '', $observation->value) ?? '';
        $hay = mb_strtolower(trim(implode(' ', array_filter([
            $item->publicationTitle,
            (string) $item->evidenceText,
        ]))));

        return $value !== '' && str_contains($hay, mb_strtolower($value));
    }

    private function requiresStrictGrounding(KnowledgeQueryPlan $plan): bool
    {
        $subjectType = is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null;
        $hasEntity = $plan->normalizedQuery->isEntityDependent()
            || $plan->normalizedQuery->cropId !== null
            || $plan->normalizedQuery->scientificName !== null
            || in_array((string) $subjectType, ['crop', 'named_entity', 'animal', 'plant_family'], true);
        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));

        if ($sense === 'plant_family_members' || $subjectType === 'plant_family') {
            return true;
        }

        return $hasEntity && is_array($factors) && $factors !== [];
    }

    /**
     * Factual / direct questions must not become confident answers from SUPPORTING-only piles.
     * Covers classification, temperature, timing, requirement, and recommended-range intents.
     * Entity-less general/industry/hydro questions stay eligible for limited supporting-only framing.
     */
    private function requiresFactualDirectEvidence(KnowledgeQueryPlan $plan): bool
    {
        if ($this->requiresStrictGrounding($plan)) {
            return true;
        }

        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));
        if (in_array($questionType, [
            'classification', 'quantity', 'range', 'timing', 'causes', 'symptoms',
            'comparison', 'species', 'definition', 'requirements',
        ], true)) {
            return true;
        }

        $requiredEvidence = trim((string) ($plan->normalizedQuery->constraints['required_evidence_type'] ?? ''));
        if (in_array($requiredEvidence, [
            'classification_or_types_inventory',
            'numeric_rate_or_quantity',
            'numeric_range_or_optimal_value',
            'temporal_window_or_season',
            'causal_relationship',
            'symptom_description',
            'species_list_or_taxonomy',
            'requirement_specification',
        ], true)) {
            return true;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));
        if ($sense === 'land_classification') {
            return true;
        }
        if ($sense === 'salinity_physiology' && $qualifier === 'effect') {
            return true;
        }

        $hasEntity = $plan->normalizedQuery->cropId !== null
            || $plan->normalizedQuery->scientificName !== null
            || ((is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null) === 'crop')
            || ((is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null) === 'plant_family');
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
            'plant_family_members',
            'varieties',
        ], true)) {
            return true;
        }

        if (in_array($qualifier, ['optimal_range', 'requirement'], true)) {
            return true;
        }

        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        foreach (['temperature', 'germination', 'water', 'salinity', 'drying', 'storage'] as $factualFactor) {
            if (in_array($factualFactor, $factors, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Seed-germination + temperature + optimal/suitable range cannot be certified
     * from SUPPORTING-only piles. Other factual families keep the supporting hatch.
     */
    private function requiresThermalGerminationDirectEvidence(KnowledgeQueryPlan $plan): bool
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $plan->normalizedQuery->normalizedQuestion,
            $plan->normalizedQuery->originalQuestion,
        ]))));

        if (AgriculturalEntityCatalog::isThermalGerminationRangeQuestion($haystack, $sense, $qualifier)) {
            return true;
        }

        return $sense === 'seed_germination'
            && $qualifier === 'optimal_range'
            && in_array('temperature', $factors, true);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $usable
     * @return array{
     *     sufficient: bool,
     *     partial: bool,
     *     reason: string,
     *     mode: string,
     *     direct_count: int,
     *     supporting_count: int
     * }
     */
    private function assessSynthesisSufficiency(
        array $usable,
        EvidenceValidationExecutionReport $validationReport,
        KnowledgeQueryPlan $plan,
    ): array {
        $directCount = 0;
        $supportingCount = 0;
        $answerEligibleSupporting = 0;
        $identitySafeDirect = 0;
        $entityDependent = $plan->normalizedQuery->isEntityDependent();
        foreach ($usable as $item) {
            $directness = $this->resolveDirectness($item, $plan);
            $identityCompatible = $this->evidenceIdentityCompatible($item, $plan, $directness);
            if ($directness === ScientificEvidenceDirectnessAssessor::DIRECT) {
                $directCount++;
                if ($identityCompatible) {
                    $identitySafeDirect++;
                }
            } elseif (in_array($directness, [
                ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ScientificEvidenceDirectnessAssessor::SUPPORTED,
            ], true)) {
                $supportingCount++;
                if ($identityCompatible && $this->isAnswerEligibleSupportingItem($item, $plan)) {
                    $answerEligibleSupporting++;
                }
            }
        }

        if ($entityDependent) {
            $directCount = $identitySafeDirect;
        }

        // Never treat many SUPPORTING as DIRECT.
        if ($directCount >= 1) {
            return [
                'sufficient' => true,
                'partial' => false,
                'reason' => 'sufficient_direct_evidence',
                'mode' => 'sufficient_direct_evidence',
                'direct_count' => $directCount,
                'supporting_count' => $supportingCount,
                'answer_eligible_count' => $directCount + $answerEligibleSupporting,
            ];
        }

        // Factual questions: multiple answer-eligible SUPPORTING → supported_answer (not DIRECT).
        // Thermal germination optimal-range still requires real DIRECT evidence.
        if ($this->requiresFactualDirectEvidence($plan)) {
            if ($answerEligibleSupporting >= 2 && ! $this->requiresThermalGerminationDirectEvidence($plan)) {
                return [
                    'sufficient' => false,
                    'partial' => true,
                    'reason' => 'sufficient_supporting_evidence',
                    'mode' => 'supported_answer',
                    'direct_count' => $directCount,
                    'supporting_count' => $supportingCount,
                    'answer_eligible_count' => $answerEligibleSupporting,
                ];
            }

            return [
                'sufficient' => false,
                'partial' => false,
                'reason' => $supportingCount >= 1 ? 'insufficient_direct_evidence' : 'insufficient_evidence',
                'mode' => $supportingCount >= 1 ? 'insufficient_direct_evidence' : 'insufficient_evidence',
                'direct_count' => $directCount,
                'supporting_count' => $supportingCount,
                'answer_eligible_count' => $answerEligibleSupporting,
            ];
        }

        // Non-factual (e.g. hydroponics benefits): allow limited supporting-only framing, never full confidence.
        if ($supportingCount >= 1 || $usable !== []) {
            return [
                'sufficient' => false,
                'partial' => true,
                'reason' => 'supporting_only',
                'mode' => 'supporting_only',
                'direct_count' => $directCount,
                'supporting_count' => max($supportingCount, count($usable)),
                'answer_eligible_count' => $answerEligibleSupporting,
            ];
        }

        return [
            'sufficient' => false,
            'partial' => false,
            'reason' => 'insufficient_evidence',
            'mode' => 'insufficient_evidence',
            'direct_count' => $directCount,
            'supporting_count' => $supportingCount,
            'answer_eligible_count' => $answerEligibleSupporting,
        ];
    }

    private function evidenceIdentityCompatible(
        ScientificEvidenceItem $item,
        KnowledgeQueryPlan $plan,
        string $directness,
    ): bool {
        if ($this->isDirectStatisticalEvidence($item)) {
            return $this->directStatisticalObservationSupportsClaim($item, $plan);
        }

        if (! $plan->normalizedQuery->isEntityDependent()) {
            return true;
        }

        $relation = (string) ($item->qualityFactors['species_relation'] ?? '');
        if ($relation === '') {
            $assessed = $this->relevanceGate->assess(
                $plan,
                $item->publicationTitle,
                $item->evidenceText,
                $item->doi,
            );
            $relation = (string) ($assessed['species_relation'] ?? '');
        }

        if (in_array($relation, ['different_species', 'entity_less', 'genus_only'], true)) {
            return false;
        }

        return $directness !== ScientificEvidenceDirectnessAssessor::IRRELEVANT;
    }

    private function isAnswerEligibleSupportingItem(ScientificEvidenceItem $item, KnowledgeQueryPlan $plan): bool
    {
        if (($item->qualityFactors['answer_eligible'] ?? null) === true
            && ($item->qualityFactors['evidence_directness'] ?? null) !== ScientificEvidenceDirectnessAssessor::DIRECT) {
            return true;
        }

        $directness = $this->resolveDirectness($item, $plan);
        if (! in_array($directness, [
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        ], true)) {
            return false;
        }
        if (! in_array($item->claimRelationship, [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        ], true)) {
            return false;
        }
        if ($item->evidenceText === null || trim($item->evidenceText) === '') {
            return false;
        }

        $assessment = [
            'entity_matched' => (bool) ($item->qualityFactors['entity_matched'] ?? false),
            'topic_matched' => (bool) ($item->qualityFactors['topic_matched'] ?? false),
            'sense_coverage' => (bool) ($item->qualityFactors['sense_coverage'] ?? false),
            'factor_coverage' => (float) ($item->qualityFactors['factor_coverage'] ?? 0.0),
            'verification_label' => $item->qualityFactors['verification_label'] ?? null,
        ];

        // When match flags were not persisted (fixture tests), re-assess from title/text.
        if (! array_key_exists('entity_matched', $item->qualityFactors)
            && ! array_key_exists('topic_matched', $item->qualityFactors)) {
            $reassessed = $this->evidenceVerificationLayer->assess(
                $plan,
                $item->publicationTitle,
                $item->evidenceText,
                $item->doi,
            );
            $assessment = [
                'entity_matched' => (bool) ($reassessed['entity_matched'] ?? false),
                'topic_matched' => (bool) ($reassessed['topic_matched'] ?? false),
                'sense_coverage' => (bool) ($reassessed['sense_coverage'] ?? false),
                'factor_coverage' => (float) ($reassessed['factor_coverage'] ?? 0.0),
                'verification_label' => $reassessed['verification_label'] ?? null,
            ];
            // Reassessed SUPPORTING that Directness marks for missing answerability is not answer-eligible.
            if (in_array('missing_required_evidence_answerability', $reassessed['reasons'] ?? [], true)
                || in_array('missing_intent_qualifier_answerability', $reassessed['reasons'] ?? [], true)
                || in_array('missing_qualifier_answerability', $reassessed['reasons'] ?? [], true)
                || ($reassessed['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::RELATED
                || ($reassessed['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::IRRELEVANT
                || ($reassessed['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH
                || ($reassessed['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::BACKGROUND) {
                return false;
            }
        }

        return $this->evidenceVerificationLayer->isAnswerEligibleSupporting($directness, $assessment);
    }

    private function resolveDirectness(ScientificEvidenceItem $item, KnowledgeQueryPlan $plan): string
    {
        $stored = $item->qualityFactors['evidence_directness']
            ?? $item->sourceAttribution['evidence_directness']
            ?? null;
        if (is_string($stored) && $stored !== '') {
            return $this->normalizeDirectnessLabel($stored, $item);
        }

        if ($this->isDirectStatisticalEvidence($item)) {
            return ScientificEvidenceDirectnessAssessor::DIRECT;
        }

        return $this->directnessAssessor->assess(
            $plan,
            $item->publicationTitle,
            $item->evidenceText,
            $item->doi,
        )['directness'];
    }

    private function normalizeDirectnessLabel(string $stored, ScientificEvidenceItem $item): string
    {
        $normalized = strtolower(trim($stored));
        if (in_array($normalized, ['direct_statistical', 'direct_statistical_evidence'], true)
            || strtoupper($stored) === ScientificEvidenceModality::DIRECT_STATISTICAL
            || $this->isDirectStatisticalEvidence($item)) {
            return ScientificEvidenceDirectnessAssessor::DIRECT;
        }

        return $stored;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<ResearchAnswerCitation>
     */
    private function buildCitations(array $items, array $sufficiency = []): array
    {
        $supportedAnswer = (($sufficiency['mode'] ?? '') === 'supported_answer');
        $citations = [];
        foreach ($items as $item) {
            $reference = $this->citationFromEvidence($item, $supportedAnswer);
            if ($reference === null) {
                continue;
            }
            $citations[] = $reference;
        }

        return $citations;
    }

    private function citationFromEvidence(ScientificEvidenceItem $item, bool $supportedAnswer = false): ?ResearchAnswerCitation
    {
        $directness = $this->normalizeDirectnessLabel((string) ($item->qualityFactors['evidence_directness']
            ?? $item->sourceAttribution['evidence_directness']
            ?? ''), $item);
        // Primary citations[]: DIRECT only, unless supported_answer mode allows eligible SUPPORTING.
        if ($supportedAnswer) {
            if (! $this->evidenceVerificationLayer->isSupportedAnswerCitationEligible($directness)) {
                return null;
            }
        } elseif (! $this->evidenceVerificationLayer->isPrimaryCitationEligible($directness)) {
            return null;
        }

        $sourceType = $item->sourceType ?? 'supporting_verified';
        if ($this->isDirectStatisticalEvidence($item)
            && ! ScientificSourceRegistry::isApprovedSourceType((string) $sourceType)) {
            $sourceType = 'international_organization';
        }
        $reference = [
            'source_type' => $sourceType,
            'organization' => $item->institution ?? ($item->sourceAttribution['organization'] ?? ''),
            'title' => $item->publicationTitle,
        ];

        if ($item->url !== null && $item->url !== '') {
            $reference['url'] = $item->url;
        }

        if ($item->doi !== null && $item->doi !== '') {
            $reference['doi'] = $item->doi;
        }

        if (! $this->sourceValidator->isVerifiedSource($reference)) {
            return null;
        }

        return new ResearchAnswerCitation(
            citationId: 'cite-'.$item->evidenceId,
            sourceId: $item->sourceId,
            evidenceId: $item->evidenceId,
            title: $item->publicationTitle,
            authors: $item->authors,
            organization: is_string($reference['organization']) ? $reference['organization'] : null,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            sourceType: $item->sourceType,
        );
    }

    /**
     * Phase-5 Unit B2 — build ResearchAnswerClaims from QuestionClaim → Evidence traces.
     * Consumes Unit-A matrix / Phase-4 relationships; does not recalculate claim_relation.
     *
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<ResearchAnswerClaim>
     */
    private function buildClaims(
        array $items,
        KnowledgeQueryPlan $plan,
        ?EvidenceValidationExecutionReport $validationReport = null,
    ): array {
        $report = $validationReport ?? new EvidenceValidationExecutionReport(
            status: 'no_valid_evidence',
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: 0,
            validatedCount: 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: false,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $matrix = (new QuestionClaimSynthesisContract)->build($plan, $report, $items);
        $itemsById = [];
        foreach ($items as $item) {
            $itemsById[$item->evidenceId] = $item;
        }
        $questionClaimsById = [];
        foreach ($matrix['question_claims'] as $questionClaimRow) {
            if (! is_array($questionClaimRow)) {
                continue;
            }
            $qcId = (string) ($questionClaimRow['claim_id'] ?? '');
            if ($qcId !== '') {
                $questionClaimsById[$qcId] = $questionClaimRow;
            }
        }

        $claims = [];
        foreach ($matrix['answer_statement_traces'] as $trace) {
            $questionClaimId = (string) ($trace['question_claim_id'] ?? '');
            if ($questionClaimId === '') {
                continue;
            }

            $relationship = (string) ($trace['aggregate_claim_relationship']
                ?? ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE);
            $answerEligible = (bool) ($trace['answer_eligible'] ?? false);
            $evidenceIds = array_values(array_filter(
                array_map('strval', is_array($trace['evidence_ids'] ?? null) ? $trace['evidence_ids'] : []),
            ));
            $sourceIds = array_values(array_filter(
                array_map('strval', is_array($trace['source_ids'] ?? null) ? $trace['source_ids'] : []),
            ));
            $limitations = array_values(array_filter(
                array_map('strval', is_array($trace['limitations'] ?? null) ? $trace['limitations'] : []),
            ));
            $questionClaim = $questionClaimsById[$questionClaimId] ?? [
                'claim_id' => $questionClaimId,
                'property' => null,
                'location' => $plan->normalizedQuery->location,
                'time' => null,
            ];

            $claimText = '';
            $numericalValues = [];
            $confidence = 0.0;
            $validationStatus = 'insufficient_evidence';
            $conditions = null;

            if ($answerEligible
                && $relationship !== ClaimEvidenceRelationship::CONFLICTING
                && $relationship !== ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE
            ) {
                foreach ($evidenceIds as $evidenceId) {
                    $item = $itemsById[$evidenceId] ?? null;
                    if ($item === null || $item->evidenceText === null || trim($item->evidenceText) === '') {
                        continue;
                    }
                    if ($item->claimRelationship === ClaimEvidenceRelationship::CONFLICTING || $item->hasConflict) {
                        continue;
                    }

                    $evidenceBody = $this->isDirectStatisticalEvidence($item)
                        ? trim($item->publicationTitle."\n".$item->evidenceText)
                        : $item->evidenceText;
                    $groundedText = $this->selectGroundedSnippet($evidenceBody, $plan, $item->publicationTitle);
                    if ($groundedText === '') {
                        continue;
                    }

                    // B3: expression accuracy gate (Catalog-free) before factual prose.
                    $accuracy = $this->expressionAccuracyGate->evaluate(
                        $item,
                        $plan,
                        $questionClaim,
                        $groundedText,
                        $relationship,
                        fn (ScientificEvidenceItem $evidence, KnowledgeQueryPlan $queryPlan, string $directness): bool => $this->evidenceIdentityCompatible($evidence, $queryPlan, $directness),
                        fn (ScientificEvidenceItem $evidence, KnowledgeQueryPlan $queryPlan): string => $this->resolveDirectness($evidence, $queryPlan),
                        fn (string $text, KnowledgeQueryPlan $queryPlan): array => $this->extractSupportedPropertyValues($text, $queryPlan),
                        fn (KnowledgeQueryPlan $queryPlan): bool => $this->requiresSupportedMeasurement($queryPlan),
                    );

                    if (! ($accuracy['allowed'] ?? false)) {
                        foreach (($accuracy['reasons'] ?? []) as $reason) {
                            $reason = trim((string) $reason);
                            if ($reason !== '' && ! in_array($reason, $limitations, true)) {
                                $limitations[] = $reason;
                            }
                        }
                        $claimText = '';
                        $numericalValues = [];
                        $confidence = 0.0;
                        // Keep claim identity; do not emit rejected factual values.
                        continue;
                    }

                    $claimText = (string) ($accuracy['claim_text'] ?? $groundedText);
                    $numericalValues = array_values(array_filter(
                        (array) ($accuracy['numerical_values'] ?? []),
                        static fn ($value): bool => is_string($value) && trim($value) !== '',
                    ));
                    $confidence = $item->confidence;
                    $validationStatus = $item->validationStatus;
                    $conditions = is_array($item->conditions) ? json_encode($item->conditions) : null;

                    if ($item->claimRelationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED
                        && ! in_array('partial_evidence_support', $limitations, true)) {
                        $limitations[] = 'partial_evidence_support';
                    }
                    $directness = $this->resolveDirectness($item, $plan);
                    if ($directness === ScientificEvidenceDirectnessAssessor::SUPPORTING
                        && ! in_array('supporting_not_direct_evidence', $limitations, true)) {
                        $limitations[] = 'supporting_not_direct_evidence';
                    }
                    break;
                }

                // Eligible but no grounded/accurate snippet — keep claim identity without factual prose.
                if ($claimText === '') {
                    $answerEligible = false;
                    if (! in_array('insufficient_validated_evidence_for_question_claim', $limitations, true)
                        && ! $this->hasAccuracyLimitation($limitations)) {
                        $limitations[] = 'insufficient_validated_evidence_for_question_claim';
                    }
                    $relationship = ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE;
                }
            } elseif ($relationship === ClaimEvidenceRelationship::CONFLICTING) {
                // Preserve conflict identity without selecting a convenient conflicting value as fact.
                $claimText = '';
                $validationStatus = 'conflicting';
                if (! in_array('conflicting_evidence_for_question_claim', $limitations, true)) {
                    $limitations[] = 'conflicting_evidence_for_question_claim';
                }
            } else {
                $claimText = '';
                if (! in_array('insufficient_validated_evidence_for_question_claim', $limitations, true)) {
                    $limitations[] = 'insufficient_validated_evidence_for_question_claim';
                }
            }

            $claims[] = new ResearchAnswerClaim(
                claimId: 'claim-'.$questionClaimId,
                claimText: $claimText,
                evidenceIds: $evidenceIds,
                sourceIds: $sourceIds,
                validationStatus: $validationStatus,
                claimRelationship: $relationship,
                confidence: $confidence,
                numericalValues: $numericalValues,
                limitations: array_values(array_unique($limitations)),
                conditions: $conditions,
                questionClaimId: $questionClaimId,
            );
        }

        return $claims;
    }

    private function selectGroundedSnippet(string $text, KnowledgeQueryPlan $plan, string $title): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $needles = $this->groundingNeedles($plan);
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));
        $preferTemperatureAnswer = $this->prefersTemperatureAnswerSnippet($plan);
        $sentences = preg_split('/(?<=[.!?؟])\s+/u', $text) ?: [$text];
        $best = '';
        $bestScore = -1.0;

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }
            $hay = mb_strtolower($sentence);
            $score = 0.0;
            foreach ($needles as $needle) {
                if ($needle !== '' && AgriculturalEntityCatalog::containsTerm($hay, $needle)) {
                    $score += mb_strlen($needle);
                }
            }
            // Prefer sentences that also align with title topic words.
            foreach ($this->terms($title) as $titleTerm) {
                if (str_contains($hay, $titleTerm)) {
                    $score += 1.0;
                }
            }

            // Optimal / range questions: prefer numeric °C / range findings over study-aim lead-ins.
            if ($qualifier === 'optimal_range') {
                if (preg_match('/\d+(?:[.,]\d+)?\s*(?:°\s*)?[cCfF]\b/u', $hay) === 1
                    || preg_match('/\b\d+(?:[.,]\d+)?\s*[-–—]\s*\d+(?:[.,]\d+)?/u', $hay) === 1
                    || preg_match('/\b(?:optimal|optimum|optima|ideal|range)\b/u', $hay) === 1) {
                    $score += 28.0;
                }
            }

            if ($qualifier === 'effect'
                && preg_match('/\b(?:affect|effect|impact|influenc|increas|decreas|reduc|improv|respons)\w*\b/u', $hay) === 1) {
                $score += 10.0;
            }

            // Temperature / optimal / germination: prefer thermal answer sentences over yield/oil/biomass.
            if ($preferTemperatureAnswer) {
                $hasTempAnswer = $this->sentenceHasTemperatureAnswer($hay);
                $hasSecondaryMetric = $this->sentenceHasSecondaryMetric($hay);
                if ($hasTempAnswer) {
                    $score += 36.0;
                    if (preg_match('/\b(?:optimal|optimum|optima|germination\s+temperature)\b/u', $hay) === 1) {
                        $score += 12.0;
                    }
                    if (preg_match('/\d+(?:[.,]\d+)?\s*(?:°\s*)?[cCfF]\b/u', $hay) === 1) {
                        $score += 14.0;
                    }
                }
                if ($hasSecondaryMetric && ! $hasTempAnswer) {
                    $score -= 30.0;
                } elseif ($hasSecondaryMetric && $hasTempAnswer) {
                    // Keep temperature evidence but demote yield/oil-led framing.
                    $score -= 8.0;
                }
            }

            // Prefer findings over study-aim / overview lead-ins for all scientific answers.
            if (preg_match('/\b(?:this study|the (?:aim|objective|purpose)|aimed to|were (?:investigated|evaluated|examined)|we (?:investigated|examined)|the (?:current|present) (?:overview|review|study)|will briefly discuss)\b/u', $hay) === 1) {
                $score -= 14.0;
            }
            if (preg_match('/\b(?:result(?:s)?|found|showed|concluded|significantly|increased|decreased|reduced|improved)\b/u', $hay) === 1) {
                $score += 8.0;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $sentence;
            }
        }

        // If no sentence overlaps structured intent, refuse off-topic abstract lead-ins
        // only for crop+topic questions; general queries may use the best available sentence.
        if ($bestScore <= 0.0 && $needles !== [] && $this->requiresStrictGrounding($plan)) {
            return '';
        }

        return $best !== '' ? $best : $this->firstSentence($text);
    }

    private function prefersTemperatureAnswerSnippet(KnowledgeQueryPlan $plan): bool
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];

        if ($sense === 'seed_germination' || in_array('germination', $factors, true)) {
            return true;
        }
        if (in_array($qualifier, ['optimal_range', 'effect', 'requirement'], true)
            && in_array('temperature', $factors, true)) {
            return true;
        }

        return in_array('temperature', $factors, true);
    }

    private function sentenceHasTemperatureAnswer(string $hay): bool
    {
        foreach (AgriculturalEntityCatalog::temperatureAnswerSignals() as $signal) {
            $normalized = mb_strtolower(trim($signal));
            if ($normalized !== '' && (
                AgriculturalEntityCatalog::containsTerm($hay, $normalized)
                || mb_strpos($hay, $normalized) !== false
            )) {
                return true;
            }
        }

        return preg_match('/\d+(?:[.,]\d+)?\s*(?:°\s*)?[cCfF]\b/u', $hay) === 1;
    }

    private function sentenceHasSecondaryMetric(string $hay): bool
    {
        foreach (AgriculturalEntityCatalog::secondaryMetricMarkers() as $marker) {
            $normalized = mb_strtolower(trim($marker));
            if ($normalized !== '' && (
                AgriculturalEntityCatalog::containsTerm($hay, $normalized)
                || mb_strpos($hay, $normalized) !== false
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function groundingNeedles(KnowledgeQueryPlan $plan): array
    {
        $needles = [];
        $propertyTerms = $plan->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        if (is_array($propertyTerms)) {
            foreach ($propertyTerms as $term) {
                $needles[] = mb_strtolower(trim((string) $term));
            }
        }
        $propertySurface = trim((string) ($plan->normalizedQuery->constraints['requested_property_surface'] ?? ''));
        if ($propertySurface !== '') {
            $needles[] = mb_strtolower($propertySurface);
        }
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        if (is_array($topics)) {
            foreach ($topics as $topic) {
                $needles[] = mb_strtolower(trim((string) $topic));
            }
        }
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense !== '') {
            foreach (AgriculturalEntityCatalog::senseQueryTerms($sense) as $term) {
                $needles[] = mb_strtolower(trim($term));
            }
        }
        if ($plan->normalizedQuery->scientificName !== null) {
            $needles[] = mb_strtolower($plan->normalizedQuery->scientificName);
        }
        foreach (AgriculturalEntityCatalog::englishTermsForIntent($plan->researchIntent) as $term) {
            if (! in_array($term, ['agriculture', 'farming'], true)) {
                $needles[] = mb_strtolower($term);
            }
        }

        return array_values(array_unique(array_filter($needles)));
    }

    /**
     * Catalog-free property-term address (Phase 5 B3).
     *
     * @param  list<mixed>  $propertyTerms
     */
    private function haystackAddressesPropertyTerms(string $haystack, array $propertyTerms): bool
    {
        return $this->expressionAccuracyGate->haystackAddressesPropertyTerms($haystack, $propertyTerms);
    }

    private function measurementWindowAddressesProperty(string $text, string $number, string $propertyKey): bool
    {
        return $this->expressionAccuracyGate->measurementWindowAddressesProperty($text, $number, $propertyKey);
    }

    /**
     * @param  list<string>  $limitations
     */
    private function hasAccuracyLimitation(array $limitations): bool
    {
        foreach ($limitations as $limitation) {
            if (str_starts_with((string) $limitation, 'accuracy_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Map B3 accuracy limitation codes to localized phrases.
     */
    private function phraseAccuracyLimitation(string $language, string $code): ?string
    {
        $phrased = $this->phraseUserFacingLimitation($language, $code);

        return $phrased;
    }

    /**
     * Localized user-facing limitation. Never returns a raw internal key.
     */
    private function phraseUserFacingLimitation(string $language, string $code): string
    {
        $key = match ($code) {
            'accuracy_entity_incompatible' => 'limitation_accuracy_entity',
            'accuracy_property_unsupported' => 'limitation_accuracy_property',
            'accuracy_geography_unsupported', 'accuracy_geography_mismatch' => 'limitation_accuracy_geography',
            'accuracy_period_unsupported', 'accuracy_period_mismatch' => 'limitation_accuracy_period',
            'accuracy_numeric_unsupported', 'accuracy_numeric_ambiguous' => 'limitation_accuracy_numeric',
            'insufficient_validated_evidence_for_question_claim' => 'limitation_insufficient_claim_evidence',
            'partial_evidence_support' => 'limitation_partial_claim_support',
            'validation_evidence_insufficient' => 'limitation_validation_insufficient',
            'conflicting_evidence_for_question_claim' => 'limitation_claim_conflict',
            'supporting_not_direct_evidence' => 'limitation_supporting_not_direct',
            'comparison_decomposition_unsupported' => 'limitation_comparison_incomplete',
            default => 'limitation_generic',
        };

        return AnswerComposerPhrases::get($language, $key);
    }

    /**
     * @return list<string>
     */
    private function extractNumericalValues(string $text): array
    {
        return $this->extractMeasurementAssertions($text, null);
    }

    /**
     * @return list<string>
     */
    private function extractSupportedPropertyValues(string $text, KnowledgeQueryPlan $plan): array
    {
        return $this->extractMeasurementAssertions($text, $plan);
    }

    /**
     * Numeric values may become answers only when they are asserted measurements
     * of the requested property — never bibliographic metadata or bare integers.
     *
     * @return list<string>
     */
    private function extractMeasurementAssertions(string $text, ?KnowledgeQueryPlan $plan): array
    {
        $text = trim($text);
        if ($text === '' || $this->rejectsNumericAnswers($plan)) {
            return [];
        }

        $pattern = '/(?<![\/.\w])(\d+(?:[.,]\d+)?(?:\s*[-–]\s*\d+(?:[.,]\d+)?)?)\s*(?:million|billion)?\s*(%|kg\/ha|kg\s*ha-1|t\/ha|mg\/l|mg\/kg|kg\/day|kg\s*d-1|l\/day|mm\/day|m3\/ha|°c|deg c|celsius|kelvin|ppm|ph|tons?|tonnes?|hectares?|t|kg|g|mg|l|ml|mm|cm|m|ha|days?|weeks?|months?|c)\b/iu';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        $matches = is_array($matches) ? $matches : [];

        $values = [];
        foreach ($matches as $match) {
            $raw = trim((string) ($match[0] ?? ''));
            $number = trim((string) ($match[1] ?? ''));
            $unit = mb_strtolower(trim((string) ($match[2] ?? '')));
            if ($raw === '' || $number === '' || $unit === '') {
                continue;
            }
            if ($this->isBibliographicNumericContext($text, $number, $unit)) {
                continue;
            }
            if ($plan !== null && ! $this->measurementMatchesRequestedProperty($plan, $text, $number, $unit, $this->textLooksLikeStructuredStatisticalMeasurement($text))) {
                continue;
            }
            if (! in_array($raw, $values, true)) {
                $values[] = $raw;
            }
        }

        return $values;
    }

    private function rejectsNumericAnswers(?KnowledgeQueryPlan $plan): bool
    {
        if ($plan === null) {
            return false;
        }

        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));

        return in_array($questionType, ['classification', 'species', 'definition', 'causes', 'symptoms', 'comparison'], true);
    }

    private function isBibliographicNumericContext(string $text, string $number, string $unit): bool
    {
        $hay = mb_strtolower($text);
        $digits = preg_replace('/[^\d]/', '', $number) ?? '';

        if (preg_match('/\b(?:10\.\d{4,}|doi:|https?:\/\/doi)/i', $hay) === 1
            && preg_match('/^10(?:[.,]\d+)?$/', trim($number)) === 1) {
            return true;
        }

        if (preg_match('/\b(?:vol(?:ume)?\.?|issue|pp?\.|pages?|issn|isbn|pmid|pmc)\b/i', $hay) === 1
            && in_array($unit, ['mm', 'cm', 'm', 'g', 'l'], true)
            && mb_strlen($digits) <= 4) {
            return true;
        }

        if (preg_match('/\b((?:19|20)\d{2})\b/', $number) === 1
            || (mb_strlen($digits) === 4 && (int) $digits >= 1900 && (int) $digits <= 2100 && $unit === '')) {
            if (preg_match('/\b(?:published|publication|copyright|cited|citations?|year|©)\b/i', $hay) === 1) {
                return true;
            }
        }

        if (preg_match('/\b(?:cited|citations?|citation count|times cited)\b/i', $hay) === 1
            && preg_match('/(?:cited|citations?|times cited)\D{0,12}'.preg_quote($digits, '/').'/i', $hay) === 1) {
            return true;
        }

        return false;
    }

    private function measurementMatchesRequestedProperty(
        KnowledgeQueryPlan $plan,
        string $text,
        string $number,
        string $unit,
        bool $structuredStatistical = false,
    ): bool {
        $propertyKey = trim((string) ($plan->normalizedQuery->constraints['requested_property'] ?? ''));
        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));
        $target = $propertyKey !== '' ? $propertyKey : $questionType;
        $unitClass = $this->measurementUnitClass($unit);
        $allowed = $this->requestedPropertyUnitClasses($target);
        if ($allowed !== null && ! in_array($unitClass, $allowed, true)) {
            return false;
        }

        if ($structuredStatistical) {
            return $number !== '';
        }

        $propertyTerms = $plan->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        $surface = trim((string) ($plan->normalizedQuery->constraints['requested_property_surface'] ?? ''));
        if ($surface !== '' && is_array($propertyTerms) && $propertyTerms !== []) {
            $hay = mb_strtolower($text);
            if (! $this->haystackAddressesPropertyTerms($hay, $propertyTerms)) {
                return false;
            }
        }

        // Claim/property lexical window around the measurement (Catalog-free).
        if ($propertyKey !== '' && ! $this->measurementWindowAddressesProperty($text, $number, $propertyKey)) {
            return false;
        }

        return $number !== '';
    }

    private function textLooksLikeStructuredStatisticalMeasurement(string $text): bool
    {
        return preg_match('/value:\s*\d/iu', $text) === 1;
    }

    private function measurementUnitClass(string $unit): string
    {
        $unit = mb_strtolower(trim($unit));

        return match (true) {
            in_array($unit, ['°c', 'deg c', 'celsius', 'kelvin', 'c'], true) => 'temperature',
            in_array($unit, ['ppm', 'mg/l', 'mg/kg', '%', 'ph'], true) => 'concentration',
            in_array($unit, ['kg/ha', 'kg ha-1', 't/ha', 'kg/day', 'kg d-1', 'l/day', 'mm/day', 'm3/ha', 'mm'], true) => 'rate',
            in_array($unit, ['ha', 'hectare', 'hectares'], true) => 'area',
            in_array($unit, ['kg', 'g', 'mg', 'l', 'ml', 'tons', 'ton', 'tonnes', 'tonne', 't'], true) => 'quantity',
            in_array($unit, ['days', 'day', 'weeks', 'week', 'months', 'month'], true) => 'time',
            default => 'quantity',
        };
    }

    /**
     * @return list<string>|null
     */
    private function requestedPropertyUnitClasses(string $target): ?array
    {
        return match ($target) {
            'temperature', 'range' => ['temperature'],
            'concentration' => ['concentration'],
            'quantity', 'yield', 'rate', 'production', 'irrigation', 'requirements' => ['quantity', 'rate'],
            'area' => ['area'],
            'classification', 'species', 'definition', 'causes', 'symptoms', 'comparison' => [],
            default => null,
        };
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<array<string, mixed>>
     */
    private function buildConflicts(array $items, string $language, KnowledgeQueryPlan $plan): array
    {
        $conflicts = [];
        foreach ($items as $item) {
            if (! $item->hasConflict && $item->claimRelationship !== ClaimEvidenceRelationship::CONFLICTING) {
                continue;
            }

            $conflicts[] = [
                'evidence_id' => $item->evidenceId,
                'source_id' => $item->sourceId,
                'publication_title' => $item->publicationTitle,
                'evidence_text' => $item->evidenceText,
                'publication_year' => $item->publicationYear,
                'conditions' => $item->conditions,
                'numerical_values' => $this->extractSupportedPropertyValues((string) $item->evidenceText, $plan),
                'message' => AnswerComposerPhrases::get($language, 'conflict_message'),
            ];
        }

        return $conflicts;
    }

    /**
     * Prefer DIRECT / non-conflicting claims. When usable DIRECT evidence exists for
     * the user intent, surface it even if secondary items are marked conflicting.
     * Generic conflict boilerplate is used only when no substantive finding remains.
     *
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  list<ScientificEvidenceItem>  $usable
     * @return list<string>
     */
    /**
     * Complete aligned DIRECT_STATISTICAL observations already carry the
     * requested measurement. Surface that value so quantity questions are
     * not wiped when claim prose omitted the number.
     *
     * @param  list<string>  $findings
     * @param  list<ScientificEvidenceItem>  $usable
     * @return list<string>
     */
    private function appendDirectStatisticalMeasurements(array $findings, array $usable, KnowledgeQueryPlan $plan): array
    {
        foreach ($usable as $item) {
            if (! $this->isDirectStatisticalEvidence($item)) {
                continue;
            }
            $observation = ScientificStructuredObservation::fromEvidenceItem($item);
            if ($observation === null || ! $observation->isComplete()) {
                continue;
            }
            if (! $this->directStatisticalObservationSupportsClaim($item, $plan)) {
                continue;
            }
            $line = trim(sprintf(
                '%s %s %s %s. Value: %s %s',
                $observation->entity,
                $observation->property,
                $observation->location,
                $observation->year,
                $observation->value,
                $observation->unit,
            ));
            if ($line !== '' && ! in_array($line, $findings, true)) {
                $findings[] = $line;
            }
        }

        return $findings;
    }

    private function buildKeyFindings(array $claims, array $usable, KnowledgeQueryPlan $plan, string $language): array
    {
        if ($claims === []) {
            return [];
        }

        $directnessByEvidenceId = [];
        foreach ($usable as $item) {
            $directnessByEvidenceId[$item->evidenceId] = $this->resolveDirectness($item, $plan);
        }

        $ordered = $claims;
        $preferTemperature = $this->prefersTemperatureAnswerSnippet($plan);
        usort($ordered, function (ResearchAnswerClaim $a, ResearchAnswerClaim $b) use ($directnessByEvidenceId, $preferTemperature): int {
            $aDirect = $this->claimIsDirect($a, $directnessByEvidenceId);
            $bDirect = $this->claimIsDirect($b, $directnessByEvidenceId);
            if ($aDirect !== $bDirect) {
                return $aDirect ? -1 : 1;
            }

            $aConflict = $a->claimRelationship === ClaimEvidenceRelationship::CONFLICTING;
            $bConflict = $b->claimRelationship === ClaimEvidenceRelationship::CONFLICTING;
            if ($aConflict !== $bConflict) {
                return $aConflict ? 1 : -1;
            }

            if ($preferTemperature) {
                $aTemp = $this->sentenceHasTemperatureAnswer(mb_strtolower($a->claimText));
                $bTemp = $this->sentenceHasTemperatureAnswer(mb_strtolower($b->claimText));
                if ($aTemp !== $bTemp) {
                    return $aTemp ? -1 : 1;
                }
                $aSecondary = $this->sentenceHasSecondaryMetric(mb_strtolower($a->claimText));
                $bSecondary = $this->sentenceHasSecondaryMetric(mb_strtolower($b->claimText));
                if ($aSecondary !== $bSecondary) {
                    return $aSecondary ? 1 : -1;
                }
            }

            return $b->confidence <=> $a->confidence;
        });

        $hasDirect = in_array(ScientificEvidenceDirectnessAssessor::DIRECT, $directnessByEvidenceId, true);

        $findings = [];
        // Pass 1: non-conflicting, claim-eligible factual statements only.
        foreach ($ordered as $claim) {
            if ($claim->questionClaimId === null || trim($claim->questionClaimId) === '') {
                continue;
            }
            if ($claim->claimRelationship === ClaimEvidenceRelationship::CONFLICTING
                || $claim->claimRelationship === ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE) {
                continue;
            }
            if (trim($claim->claimText) === '') {
                continue;
            }
            if ($hasDirect && ! $this->claimIsDirect($claim, $directnessByEvidenceId)) {
                continue;
            }
            $sentence = $this->firstSentence($claim->claimText);
            if ($sentence !== '' && ! in_array($sentence, $findings, true)) {
                $findings[] = $sentence;
            }
        }

        // Pass 2: usable DIRECT claims even if marked conflicting (secondary noise).
        if ($findings === []) {
            foreach ($ordered as $claim) {
                if ($claim->questionClaimId === null || trim($claim->questionClaimId) === '') {
                    continue;
                }
                if (trim($claim->claimText) === '') {
                    continue;
                }
                if (! $this->claimIsDirect($claim, $directnessByEvidenceId)) {
                    continue;
                }
                $sentence = $this->firstSentence($claim->claimText);
                if ($sentence !== '' && ! in_array($sentence, $findings, true)) {
                    $findings[] = $sentence;
                }
            }
        }

        // Pass 3: no DIRECT — keep supporting findings for metadata/additional (main answer still gated).
        if ($findings === [] && ! $hasDirect) {
            foreach ($ordered as $claim) {
                if ($claim->questionClaimId === null || trim($claim->questionClaimId) === '') {
                    continue;
                }
                if ($claim->claimRelationship === ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE
                    || $claim->claimRelationship === ClaimEvidenceRelationship::CONFLICTING) {
                    continue;
                }
                if (trim($claim->claimText) === '') {
                    continue;
                }
                $sentence = $this->firstSentence($claim->claimText);
                if ($sentence !== '' && ! in_array($sentence, $findings, true)) {
                    $findings[] = $sentence;
                }
            }
        }

        // Genuine no-usable-substance path only — never prefer meta conflict line when DIRECT exists.
        if ($findings === [] && $hasDirect) {
            $findings[] = AnswerComposerPhrases::get($language, 'limited_conflicting');
        }

        return array_slice($findings, 0, 5);
    }

    /**
     * @param  array<string, string>  $directnessByEvidenceId
     */
    private function claimIsDirect(ResearchAnswerClaim $claim, array $directnessByEvidenceId): bool
    {
        foreach ($claim->evidenceIds as $evidenceId) {
            if (($directnessByEvidenceId[$evidenceId] ?? null) === ScientificEvidenceDirectnessAssessor::DIRECT) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  array<string, mixed>  $sufficiency
     * @return list<string>
     */
    private function buildLimitations(
        EvidenceValidationExecutionReport $validationReport,
        array $usable,
        string $language,
        array $sufficiency,
    ): array {
        $limitations = [];

        if ($validationReport->rejectedCount > 0) {
            $limitations[] = AnswerComposerPhrases::get($language, 'limitation_rejected', [
                ':count' => (string) $validationReport->rejectedCount,
            ]);
        }

        $partialCount = count(array_filter(
            $usable,
            fn (ScientificEvidenceItem $item): bool => $item->claimRelationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        ));
        if ($partialCount > 0) {
            $limitations[] = AnswerComposerPhrases::get($language, 'limitation_partial', [
                ':count' => (string) $partialCount,
            ]);
        }

        if (($sufficiency['partial'] ?? false) === true
            || in_array((string) ($sufficiency['mode'] ?? $sufficiency['reason'] ?? ''), [
                'supporting_only',
                'supporting_evidence_only',
                'multiple_supporting_evidence',
                'general_query_usable_evidence',
                'insufficient_direct_evidence',
                'supported_answer',
                'sufficient_supporting_evidence',
            ], true)) {
            $limitations[] = AnswerComposerPhrases::get($language, 'limitation_supporting');
        }

        if ($validationReport->conflictingCount > 0) {
            $limitations[] = AnswerComposerPhrases::get($language, 'limitation_disagreement');
        }

        return $limitations;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  list<array<string, mixed>>  $conflicts
     * @param  array<string, mixed>  $sufficiency
     * @param  list<string>  $keyFindings
     */
    private function resolveUncertainty(
        EvidenceValidationExecutionReport $validationReport,
        array $usable,
        array $conflicts,
        string $language,
        array $sufficiency,
        array $keyFindings = [],
    ): ?string {
        if (! $validationReport->evidenceSufficient || ! ($sufficiency['sufficient'] ?? false)) {
            return AnswerComposerPhrases::get($language, 'uncertainty_insufficient');
        }

        $hasDirect = ((int) ($sufficiency['direct_count'] ?? 0)) >= 1;
        $usableNonConflict = count(array_filter(
            $usable,
            static fn (ScientificEvidenceItem $item): bool => ! $item->hasConflict
                && $item->claimRelationship !== ClaimEvidenceRelationship::CONFLICTING,
        ));

        // Partial secondary conflicts with usable non-conflicting DIRECT findings → softer uncertainty.
        // When every usable item is conflicting, keep the hard conflict framing.
        if ($conflicts !== [] && $hasDirect && $usableNonConflict > 0) {
            return AnswerComposerPhrases::get($language, 'uncertainty_secondary_conflicts');
        }

        if ($conflicts !== []) {
            return AnswerComposerPhrases::get($language, 'uncertainty_conflicting');
        }

        if (in_array((string) ($sufficiency['mode'] ?? ''), ['supported_answer', 'sufficient_supporting_evidence'], true)) {
            return AnswerComposerPhrases::get($language, 'uncertainty_aggregated');
        }

        if (($sufficiency['direct_count'] ?? 0) === 0 && ($sufficiency['supporting_count'] ?? 0) >= 1) {
            return AnswerComposerPhrases::get($language, 'uncertainty_supporting_only');
        }

        $supported = count(array_filter(
            $usable,
            fn (ScientificEvidenceItem $item): bool => $item->claimRelationship === ClaimEvidenceRelationship::SUPPORTED,
        ));

        if ($supported === 1 && count($usable) === 1) {
            return AnswerComposerPhrases::get($language, 'uncertainty_single_source');
        }

        return null;
    }

    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  array<string, mixed>  $sufficiency
     */
    private function overallConfidence(
        array $claims,
        EvidenceValidationExecutionReport $validationReport,
        array $sufficiency = [],
    ): float {
        if ($claims === []) {
            return 0.0;
        }

        $total = array_sum(array_map(fn (ResearchAnswerClaim $claim): float => $claim->confidence, $claims));
        $confidence = min(0.95, $total / count($claims));

        // Supporting-only must never look like a high-confidence DIRECT answer.
        if (((int) ($sufficiency['direct_count'] ?? 0)) === 0) {
            $confidence = min($confidence, 0.42);
        }

        // Accuracy-filtered claims contribute 0 confidence already; when every claim
        // was blocked by an accuracy limitation, confidence remains zero.
        $expressible = array_values(array_filter(
            $claims,
            static fn (ResearchAnswerClaim $claim): bool => trim($claim->claimText) !== ''
                && $claim->claimRelationship !== ClaimEvidenceRelationship::CONFLICTING
                && $claim->claimRelationship !== ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
        ));
        if ($expressible === []) {
            $hasAccuracyBlock = false;
            foreach ($claims as $claim) {
                if ($this->hasAccuracyLimitation($claim->limitations)) {
                    $hasAccuracyBlock = true;
                    break;
                }
            }
            if ($hasAccuracyBlock) {
                return 0.0;
            }
        } else {
            // Confidence tracks remaining expressible claims only (no invented constants).
            $expressibleTotal = array_sum(array_map(
                static fn (ResearchAnswerClaim $claim): float => $claim->confidence,
                $expressible,
            ));
            $confidence = min($confidence, min(0.95, $expressibleTotal / count($expressible)));
            if (((int) ($sufficiency['direct_count'] ?? 0)) === 0) {
                $confidence = min($confidence, 0.42);
            }
        }

        return round($confidence, 3);
    }

    /**
     * @param  array<string, mixed>  $sufficiency
     */
    private function isSupportingOnlySufficiency(array $sufficiency): bool
    {
        return ((int) ($sufficiency['direct_count'] ?? 0)) === 0
            && in_array((string) ($sufficiency['mode'] ?? $sufficiency['reason'] ?? ''), [
                'supporting_only',
                'supporting_evidence_only',
                'multiple_supporting_evidence',
                'general_query_usable_evidence',
                'insufficient_direct_evidence',
                'supported_answer',
            ], true);
    }

    private function insufficientDirectMessage(string $language): string
    {
        return AnswerComposerPhrases::get($language, 'insufficient_direct');
    }

    /**
     * @param  list<string>  $keyFindings
     * @param  array<string, mixed>  $sufficiency
     */
    private function buildConciseSummary(
        array $keyFindings,
        ?string $uncertainty,
        string $language,
        array $sufficiency = [],
        ?KnowledgeQueryPlan $plan = null,
    ): string {
        if ($this->isSupportingOnlySufficiency($sufficiency)) {
            return $this->insufficientDirectMessage($language);
        }

        if ($keyFindings === []) {
            return $uncertainty ?? $this->insufficientDirectMessage($language);
        }

        $values = $this->collectedNumericalValues($keyFindings, $plan);
        if ($values !== []) {
            return AnswerComposerPhrases::get($language, 'range_evidence', [
                ':label' => AnswerComposerPhrases::get($language, 'label_supported_value'),
                ':values' => implode(', ', array_slice($values, 0, 4)),
            ]);
        }

        return AnswerComposerPhrases::get($language, 'explanatory_evidence');
    }

    /**
     * @param  list<string>  $keyFindings
     * @param  array<string, mixed>  $sufficiency
     */
    private function buildMainAnswerBody(
        array $keyFindings,
        KnowledgeQueryPlan $plan,
        string $language,
        array $sufficiency,
    ): string {
        // Supporting-only must never become a confident main answer narrative.
        if ($this->isSupportingOnlySufficiency($sufficiency)) {
            return $this->insufficientDirectMessage($language);
        }

        if ($keyFindings === []) {
            return $this->insufficientDirectMessage($language);
        }

        $mode = $this->resolvePresentationMode($plan);
        $heading = $this->mainAnswerHeading($plan, $language);

        return match ($mode) {
            'list' => $this->formatAsNumberedList($keyFindings, $heading, $language),
            'process' => $this->formatAsNumberedSteps($keyFindings, $heading, $language),
            'range' => $this->formatAsLabeledRange($keyFindings, $plan, $language),
            'comparison' => $this->formatAsComparison($keyFindings, $heading, $language),
            default => $this->formatAsExplanatory($keyFindings, $heading, $language, $plan),
        };
    }

    private function resolvePresentationMode(KnowledgeQueryPlan $plan): string
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));
        $intent = trim((string) $plan->researchIntent);
        $question = mb_strtolower(trim($plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion));

        if ($sense === 'land_classification'
            || preg_match('/\b(?:types?|categories|species|classes|kinds?|inventory)\b/u', $question) === 1
            || preg_match('/(?:انواع|أنواع|تصنيف|اصناف|أصناف|اجناس|أجناس)/u', $question) === 1) {
            return 'list';
        }

        if (preg_match('/\b(?:steps?|procedure|process|protocol|how\s+to)\b/u', $question) === 1
            || preg_match('/(?:خطوات|طريقة|كيفية|اجراء|إجراء)/u', $question) === 1) {
            return 'process';
        }

        if (preg_match('/\b(?:compar(?:e|ison)|versus|vs\.?)\b/u', $question) === 1
            || preg_match('/(?:مقارنة|مقابل)/u', $question) === 1) {
            return 'comparison';
        }

        if (in_array($qualifier, ['optimal_range', 'requirement'], true)
            || in_array($sense, ['seed_germination', 'crop_water_requirement'], true)
            || preg_match('/\b(?:rate|range|temperature|optimum|optimal)\b/u', $question) === 1
            || preg_match('/(?:درجة حرارة|معدل|احتياج|نطاق|مثلى|مثالي)/u', $question) === 1) {
            return 'range';
        }

        if (in_array($intent, ['cultivation', 'plant_nutrition', 'irrigation'], true)
            && preg_match('/\b(?:guide|practice|management)\b/u', $question) === 1) {
            return 'process';
        }

        return 'explanatory';
    }

    private function mainAnswerHeading(KnowledgeQueryPlan $plan, string $language): string
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));
        $intent = trim((string) $plan->researchIntent);
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        $topic = is_array($topics) && $topics !== [] ? trim((string) $topics[0]) : '';

        // Entity-family member inventory heading (stage separately from land/timing).
        if ($sense === 'plant_family_members'
            || $intent === 'plant_family_members'
            || $questionType === 'species') {
            return AnswerComposerPhrases::get($language, 'heading_family_members');
        }

        if ($sense === 'land_classification') {
            return AnswerComposerPhrases::get($language, 'heading_land_types');
        }

        if ($sense === 'planting_timing' || $questionType === 'timing') {
            return AnswerComposerPhrases::get($language, 'heading_planting_date');
        }

        if ($sense === 'varieties' || $intent === 'varieties') {
            return AnswerComposerPhrases::get($language, 'heading_varieties');
        }

        // Prefer a localized generic heading over leaking internal sense keys
        // (e.g. "plant growth", "scientific_generated") into the user answer.
        $blockedTopicLeak = in_array(mb_strtolower($topic), [
            'plant growth', 'growth', 'physiology', 'scientific generated',
            'direct', 'supporting', 'related', 'irrelevant',
        ], true);
        if ($topic !== '' && ! $blockedTopicLeak && ! str_contains($topic, '_') && $language === 'en') {
            return $topic;
        }

        $localizedSense = match ($sense) {
            'plant_growth' => AnswerComposerPhrases::get($language, 'heading_plant_growth'),
            'seed_germination' => AnswerComposerPhrases::get($language, 'heading_seed_germination'),
            'crop_water_requirement' => AnswerComposerPhrases::get($language, 'heading_water_requirement'),
            'plant_nutrition' => AnswerComposerPhrases::get($language, 'heading_plant_nutrition'),
            default => '',
        };
        if ($localizedSense !== '') {
            return $localizedSense;
        }

        return AnswerComposerPhrases::get($language, 'heading_answer');
    }

    /**
     * @param  list<string>  $keyFindings
     */
    private function formatAsNumberedList(array $keyFindings, string $heading, string $language): string
    {
        $items = $this->factItemsFromFindings($keyFindings);
        $lines = ['### '.$heading, AnswerComposerPhrases::get($language, 'list_evidence')];
        if ($items === []) {
            return implode("\n", $lines);
        }
        foreach ($items as $index => $item) {
            $lines[] = ($index + 1).'. **'.$item.'**';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $keyFindings
     */
    private function formatAsNumberedSteps(array $keyFindings, string $heading, string $language): string
    {
        $items = $this->factItemsFromFindings($keyFindings);
        $stepLabel = AnswerComposerPhrases::get($language, 'step');
        $lines = ['### '.$heading, AnswerComposerPhrases::get($language, 'process_evidence')];
        foreach ($items as $index => $item) {
            $lines[] = ($index + 1).'. '.$stepLabel.' '.($index + 1).': '.$item;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $keyFindings
     */
    private function formatAsLabeledRange(array $keyFindings, KnowledgeQueryPlan $plan, string $language): string
    {
        $values = $this->collectedNumericalValues($keyFindings, $plan);
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));
        $label = match (true) {
            $qualifier === 'optimal_range' => AnswerComposerPhrases::get($language, 'label_optimal_range'),
            $qualifier === 'requirement' => AnswerComposerPhrases::get($language, 'label_requirement'),
            default => AnswerComposerPhrases::get($language, 'label_supported_value'),
        };

        $lines = [];
        if ($values !== []) {
            $valueText = implode(', ', array_slice($values, 0, 4));
            $lines[] = $label.': '.$valueText;
            $lines[] = AnswerComposerPhrases::get($language, 'range_evidence', [
                ':label' => $label,
                ':values' => $valueText,
            ]);
        } else {
            $lines[] = AnswerComposerPhrases::get($language, 'explanatory_evidence');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $keyFindings
     */
    private function formatAsComparison(array $keyFindings, string $heading, string $language): string
    {
        $items = $this->factItemsFromFindings($keyFindings);
        $lines = ['### '.$heading, AnswerComposerPhrases::get($language, 'comparison_evidence')];
        $prefix = AnswerComposerPhrases::get($language, 'aspect');
        foreach ($items as $index => $finding) {
            $lines[] = ($index + 1).'. '.$prefix.' '.($index + 1).': '.$finding;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $keyFindings
     */
    private function formatAsExplanatory(array $keyFindings, string $heading, string $language, ?KnowledgeQueryPlan $plan = null): string
    {
        $values = $this->collectedNumericalValues($keyFindings, $plan);
        $items = $this->factItemsFromFindings($keyFindings);
        $lines = [];
        if ($heading !== '' && $heading !== AnswerComposerPhrases::get($language, 'heading_answer')) {
            $lines[] = '### '.$heading;
            $lines[] = '';
        }
        if ($values !== []) {
            $lines[] = AnswerComposerPhrases::get($language, 'range_evidence', [
                ':label' => AnswerComposerPhrases::get($language, 'label_supported_value'),
                ':values' => implode(', ', array_slice($values, 0, 4)),
            ]);
        } else {
            $lines[] = AnswerComposerPhrases::get($language, 'explanatory_evidence');
        }
        foreach ($items as $index => $item) {
            $lines[] = ($index + 1).'. **'.$item.'**';
        }

        return implode("\n", $lines);
    }

    /**
     * Quantity/rate answers require a validated measurement of the requested property.
     */
    private function requiresSupportedMeasurement(KnowledgeQueryPlan $plan): bool
    {
        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));
        $propertyKey = trim((string) ($plan->normalizedQuery->constraints['requested_property'] ?? ''));

        // Quantity/range answers need a validated measurement. Agronomic
        // "requirements" (requirement_specification / crop farming-needs) are
        // not quantity questions — treating them as measurement-required wiped
        // otherwise-sufficient crop profiles (no_supported_property_measurement).
        if (in_array($questionType, ['quantity', 'range'], true)) {
            return true;
        }

        return in_array($propertyKey, ['quantity', 'yield', 'rate', 'production', 'irrigation', 'concentration'], true)
            && ! in_array($questionType, ['causes', 'symptoms', 'comparison', 'classification', 'definition', 'requirements'], true);
    }

    /**
     * @param  list<string>  $findings
     * @return list<string>
     */
    private function rejectFindingsWithUnsupportedNumbers(array $findings, KnowledgeQueryPlan $plan): array
    {
        $kept = [];
        foreach ($findings as $finding) {
            if ($this->findingContainsUnsupportedNumeric((string) $finding, $plan)) {
                continue;
            }
            $kept[] = $finding;
        }

        return $kept;
    }

    private function findingContainsUnsupportedNumeric(string $text, KnowledgeQueryPlan $plan): bool
    {
        preg_match_all('/\b\d+(?:[.,]\d+)?\b/u', $text, $matches);
        $candidates = array_values(array_unique($matches[0] ?? []));
        if ($candidates === []) {
            return false;
        }

        $supported = $this->extractMeasurementAssertions($text, $plan);
        foreach ($candidates as $raw) {
            $digits = preg_replace('/[^\d]/', '', (string) $raw) ?? '';
            if ($digits === '' || $this->isBibliographicNumericContext($text, (string) $raw, '')) {
                continue;
            }
            if (preg_match('/^(?:19|20)\d{2}$/', $digits) === 1) {
                continue;
            }
            $covered = false;
            foreach ($supported as $value) {
                if (str_contains($value, (string) $raw)) {
                    $covered = true;
                    break;
                }
            }
            if (! $covered) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $keyFindings
     * @return list<string>
     */
    private function collectedNumericalValues(array $keyFindings, ?KnowledgeQueryPlan $plan = null): array
    {
        $values = [];
        foreach ($keyFindings as $finding) {
            foreach ($this->extractMeasurementAssertions($finding, $plan) as $value) {
                if (! in_array($value, $values, true)) {
                    $values[] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * Extract short factual items from evidence findings. Full source-language
     * sentences stay in citations, not in the explanatory body.
     *
     * @param  list<string>  $keyFindings
     * @return list<string>
     */
    private function factItemsFromFindings(array $keyFindings): array
    {
        $items = [];
        foreach ($this->extractListItems($keyFindings) as $item) {
            if ($this->isSourceProseSentence($item)) {
                continue;
            }
            if (! in_array($item, $items, true)) {
                $items[] = $item;
            }
        }

        return array_slice($items, 0, 12);
    }

    private function isSourceProseSentence(string $text): bool
    {
        $clean = trim($text);
        if ($clean === '') {
            return true;
        }
        $words = preg_split('/\s+/u', $clean) ?: [];
        if (count($words) >= 12) {
            return true;
        }

        return count($words) >= 8
            && preg_match('/\b(the|this|study|measures|were|was|including|carried|under)\b/i', $clean) === 1;
    }

    /**
     * @param  list<string>  $keyFindings
     * @return list<string>
     */
    private function extractListItems(array $keyFindings): array
    {
        if (count($keyFindings) > 1) {
            return array_values(array_filter(array_map(
                fn (string $finding): string => trim($this->stripListPrefix($finding)),
                $keyFindings,
            )));
        }

        $text = trim($keyFindings[0] ?? '');
        if ($text === '') {
            return [];
        }

        // Split prose enumerations: "A, B, C, and D" / "A؛ B؛ C" / "A و B و C".
        if (preg_match('/\b(?:include|includes|are|comprise|comprising)\b[:\s]+(.+)$/iu', $text, $m) === 1) {
            $text = trim($m[1]);
        }

        $parts = preg_split('/\s*(?:,|;|؛|،|\band\b|\bor\b| و )\s*/u', $text) ?: [$text];
        $items = [];
        foreach ($parts as $part) {
            $clean = trim($this->stripListPrefix((string) $part), " \t\n\r\0\x0B.");
            if ($clean !== '' && mb_strlen($clean) >= 2) {
                $items[] = $clean;
            }
        }

        // Avoid oversplitting a normal sentence into tiny fragments.
        if (count($items) < 2) {
            return [$this->stripListPrefix($keyFindings[0])];
        }

        return array_slice($items, 0, 12);
    }

    private function stripListPrefix(string $text): string
    {
        return trim((string) preg_replace('/^(?:\d+[\.\):\-]\s*|[\-\*•]\s*)/u', '', trim($text)));
    }

    /**
     * @param  list<ResearchAnswerCitation>  $citations
     */
    private function formatPrimarySourcesSection(
        array $citations,
        string $language,
        bool $supportedAnswer = false,
    ): string {
        if ($citations === []) {
            return '';
        }

        if ($supportedAnswer) {
            $heading = AnswerComposerPhrases::get($language, 'sources');
            $disclaimer = AnswerComposerPhrases::get($language, 'sources_disclaimer');
            $lines = [$heading, $disclaimer];
            foreach ($citations as $index => $citation) {
                $lines[] = ($index + 1).'. '.$this->formatCitationBlock($citation, $language);
            }

            return implode("\n", $lines);
        }

        $heading = count($citations) === 1
            ? AnswerComposerPhrases::get($language, 'primary_source')
            : AnswerComposerPhrases::get($language, 'primary_sources');

        $lines = [$heading];
        foreach ($citations as $index => $citation) {
            $lines[] = ($index + 1).'. '.$this->formatCitationBlock($citation, $language);
        }

        return implode("\n", $lines);
    }

    private function formatCitationBlock(ResearchAnswerCitation $citation, string $language): string
    {
        $parts = [];
        $titleLabel = AnswerComposerPhrases::get($language, 'title');
        $parts[] = $titleLabel.': '.$citation->title;

        if ($citation->authors !== []) {
            $authorsLabel = AnswerComposerPhrases::get($language, 'authors');
            $parts[] = $authorsLabel.': '.implode(', ', array_slice($citation->authors, 0, 8));
        }

        if ($citation->journal !== null && trim($citation->journal) !== '') {
            $journalLabel = AnswerComposerPhrases::get($language, 'journal');
            $parts[] = $journalLabel.': '.$citation->journal;
        } elseif ($citation->organization !== null && trim($citation->organization) !== '') {
            $orgLabel = AnswerComposerPhrases::get($language, 'organization');
            $parts[] = $orgLabel.': '.$citation->organization;
        }

        if ($citation->publicationYear !== null) {
            $yearLabel = AnswerComposerPhrases::get($language, 'year');
            $parts[] = $yearLabel.': '.$citation->publicationYear;
        }

        if ($citation->doi !== null && trim($citation->doi) !== '') {
            $parts[] = 'DOI: '.$citation->doi;
        }

        // Never invent URLs — only display an original URL already present on the evidence model.
        if ($citation->url !== null && trim($citation->url) !== '') {
            $urlLabel = AnswerComposerPhrases::get($language, 'original_url');
            $parts[] = $urlLabel.': '.$citation->url;
        }

        return implode("\n   ", $parts);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  list<string>  $mainFindings
     */
    private function buildAdditionalInformationSection(
        array $usable,
        array $mainFindings,
        KnowledgeQueryPlan $plan,
        string $language,
        bool $supportingOnlyContext,
    ): string {
        $entries = [];
        foreach ($usable as $item) {
            if (! $this->isUsefulAdditionalEvidence($item, $mainFindings, $plan)) {
                continue;
            }

            $snippet = $this->selectGroundedSnippet((string) $item->evidenceText, $plan, $item->publicationTitle);
            if ($snippet === '') {
                continue;
            }

            $sourceBits = array_values(array_filter([
                $item->publicationTitle !== '' ? $item->publicationTitle : null,
                $item->publicationYear !== null ? (string) $item->publicationYear : null,
                ($item->doi !== null && $item->doi !== '') ? 'DOI: '.$item->doi : null,
                ($item->url !== null && $item->url !== '') ? $item->url : null,
            ]));

            $label = AnswerComposerPhrases::get($language, 'supporting_info');
            $sourceLabel = AnswerComposerPhrases::get($language, 'source');
            $entries[] = '- '.$label.': '.$snippet
                .($sourceBits !== [] ? "\n  ".$sourceLabel.': '.implode(' | ', $sourceBits) : '');
        }

        if ($entries === []) {
            return '';
        }

        $heading = AnswerComposerPhrases::get($language, 'additional_information');
        $intro = $supportingOnlyContext
            ? AnswerComposerPhrases::get($language, 'additional_supporting_intro')
            : AnswerComposerPhrases::get($language, 'additional_related_intro');

        return implode("\n", array_merge([$heading, $intro], $entries));
    }

    /**
     * @param  list<string>  $mainFindings
     */
    private function isUsefulAdditionalEvidence(
        ScientificEvidenceItem $item,
        array $mainFindings,
        KnowledgeQueryPlan $plan,
    ): bool {
        $directness = $this->resolveDirectness($item, $plan);
        if ($directness === ScientificEvidenceDirectnessAssessor::DIRECT) {
            return false;
        }
        if (! in_array($directness, [
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        ], true)) {
            return false;
        }
        if (! in_array($item->claimRelationship, [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        ], true)) {
            return false;
        }
        if ($item->evidenceText === null || trim($item->evidenceText) === '') {
            return false;
        }

        $snippet = $this->selectGroundedSnippet($item->evidenceText, $plan, $item->publicationTitle);
        if ($snippet === '') {
            return false;
        }

        $snippetNorm = mb_strtolower(trim($snippet));
        foreach ($mainFindings as $finding) {
            $findingNorm = mb_strtolower(trim($finding));
            if ($findingNorm !== '' && (
                $snippetNorm === $findingNorm
                || str_contains($snippetNorm, $findingNorm)
                || str_contains($findingNorm, $snippetNorm)
            )) {
                return false;
            }
        }

        if (! $this->evidenceIdentityCompatible($item, $plan, $directness)) {
            return false;
        }

        if ($directness === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH) {
            return false;
        }

        $requestedYear = trim((string) ($plan->normalizedQuery->constraints['year']
            ?? $plan->normalizedQuery->constraints['year_code']
            ?? $plan->normalizedQuery->constraints['time']
            ?? ''));
        if ($requestedYear !== '' && preg_match('/^(?:19|20)\d{2}$/', $requestedYear) === 1) {
            $observationYear = $this->expressionAccuracyGate->observationYearForItem($item);
            // Observation year is authoritative. Publication year must not satisfy a year requirement.
            if ($observationYear === '' || $observationYear !== $requestedYear) {
                return false;
            }
        }

        $propertyKey = trim((string) ($plan->normalizedQuery->constraints['requested_property'] ?? ''));
        $propertyTerms = $plan->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        if ($propertyKey !== ''
            && ! in_array($propertyKey, ['general', 'definition'], true)
            && is_array($propertyTerms)
            && $propertyTerms !== []) {
            $hay = mb_strtolower(trim(implode(' ', array_filter([
                $item->publicationTitle,
                (string) $item->evidenceText,
            ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));
            if (! $this->haystackAddressesPropertyTerms($hay, $propertyTerms)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $conflicts
     */
    private function buildDetailedExplanation(
        string $primarySourcesSection,
        string $additionalSection,
        array $conflicts,
        string $language,
    ): string {
        $parts = array_values(array_filter([
            $primarySourcesSection,
            $additionalSection,
        ], static fn (string $part): bool => trim($part) !== ''));

        if ($conflicts !== []) {
            $parts[] = AnswerComposerPhrases::get($language, 'conflict_note').($conflicts[0]['message'] ?? '');
        }

        return implode("\n\n", $parts);
    }

    private function buildAnswer(
        string $mainAnswerBody,
        string $primarySourcesSection,
        string $additionalSection,
        ?string $uncertainty,
        string $language,
    ): string {
        // Semantic contract: `answer` is the main body only.
        // Sources stay in citations[]; additional text in additional_information;
        // uncertainty stays on the uncertainty field. Do not concatenate.
        unset($primarySourcesSection, $additionalSection, $uncertainty, $language);

        return trim($mainAnswerBody);
    }

    /**
     * Factual/supporting-only insufficient path: never a confident main answer; optional معلومات إضافية.
     *
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  array<string, mixed>  $sufficiency
     */
    private function supportingOnlyInsufficientReport(
        KnowledgeQueryPlan $plan,
        array $usable,
        EvidenceValidationExecutionReport $validationReport,
        array $sufficiency,
        string $language,
        string $query,
    ): AnswerSynthesisExecutionReport {
        $message = $this->insufficientDirectMessage($language);
        $additionalSection = $this->buildAdditionalInformationSection($usable, [], $plan, $language, true);
        $answer = $this->buildAnswer($message, '', $additionalSection, $message, $language);

        return new AnswerSynthesisExecutionReport(
            status: 'insufficient_evidence',
            performed: true,
            answer: $answer,
            conciseSummary: $message,
            detailedExplanation: $additionalSection !== '' ? $additionalSection : $message,
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: array_map(
                static fn (ScientificEvidenceItem $item): array => [
                    'evidence_id' => $item->evidenceId,
                    'source_id' => $item->sourceId,
                    'publication_title' => $item->publicationTitle,
                    'claim_relationship' => $item->claimRelationship,
                    'validation_status' => $item->validationStatus,
                    'has_conflict' => $item->hasConflict,
                    'evidence_directness' => $item->qualityFactors['evidence_directness']
                        ?? ($item->sourceAttribution['evidence_directness'] ?? null),
                ],
                $usable,
            ),
            confidence: 0.0,
            limitations: $this->buildLimitations($validationReport, $usable, $language, $sufficiency),
            uncertainty: $message,
            conflicts: [],
            language: $language,
            researchMetadata: [
                'query' => $query,
                'research_intent' => $plan->researchIntent,
                'agricultural_domain' => $plan->agriculturalDomain,
                'failure_reason' => (string) ($sufficiency['reason'] ?? 'supporting_only'),
                'internet_first' => $plan->isInternetFirst(),
                'direct_evidence_count' => (int) ($sufficiency['direct_count'] ?? 0),
                'supporting_evidence_count' => (int) ($sufficiency['supporting_count'] ?? 0),
                'sufficiency_mode' => $sufficiency['mode'] ?? $sufficiency['reason'] ?? 'insufficient_direct_evidence',
                'evidence_sufficient' => false,
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'failure_status' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'answer_presentation_mode' => $this->resolvePresentationMode($plan),
            ] + $this->homeEvidenceLifecycleMetadata(
                $plan,
                $validationReport,
                composerEligibleCount: count($usable),
                disposition: 'insufficient_direct_supporting_retained',
            ),
            observability: [
                'usable_evidence_count' => count($usable),
                'claims_generated' => 0,
                'citations_mapped' => 0,
                'conflicts_detected' => 0,
                'independent_search' => false,
                'validation_bypassed' => false,
                'failure_reason' => (string) ($sufficiency['reason'] ?? 'supporting_only'),
            ] + $this->phase5QuestionClaimMatrix($plan, $validationReport, $usable)
              + $this->homeEvidenceLifecycleMetadata(
                $plan,
                $validationReport,
                composerEligibleCount: count($usable),
                disposition: 'insufficient_direct_supporting_retained',
            ),
            additionalInformation: trim($additionalSection) !== '' ? $additionalSection : null,
        );
    }

    private function firstSentence(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (preg_match('/^(.+?[.!?؟])(\s|$)/u', $text, $matches)) {
            return trim($matches[1]);
        }

        return mb_strlen($text) > 240 ? mb_substr($text, 0, 237).'...' : $text;
    }

    /** @return list<string> */
    private function terms(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        $parts = preg_split('/\s+/u', $normalized) ?: [];

        return array_values(array_filter($parts, static fn (string $part): bool => mb_strlen($part) >= 4));
    }

    private function insufficientReport(
        string $status,
        string $reason,
        string $language,
        string $query,
        KnowledgeQueryPlan $plan,
        int $rejectedCount = 0,
        ?EvidenceValidationExecutionReport $validationReport = null,
    ): AnswerSynthesisExecutionReport {
        $message = ($reason === 'no_relevant_validated_evidence' || $reason === 'background_or_weak_evidence_only')
            ? AnswerComposerPhrases::get($language, 'insufficient_irrelevant')
            : AnswerComposerPhrases::get($language, 'uncertainty_insufficient');

        $lifecycle = $this->homeEvidenceLifecycleMetadata(
            $plan,
            $validationReport,
            composerEligibleCount: 0,
            disposition: $status === 'no_search_results' || $status === 'needs_clarification'
                ? ($status === 'needs_clarification' ? 'needs_clarification' : null)
                : null,
        );
        if ($status === 'no_search_results' && $lifecycle !== []) {
            $lifecycle['evidence_lifecycle_disposition'] = 'no_results_retrieved';
        }

        $evidenceReferences = $validationReport !== null
            ? $this->homeUnusedEvidenceReferences($plan, $validationReport)
            : [];

        return new AnswerSynthesisExecutionReport(
            status: $status,
            performed: true,
            answer: $message,
            conciseSummary: $message,
            detailedExplanation: $message,
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: $evidenceReferences,
            confidence: 0.0,
            limitations: $rejectedCount > 0
                ? [AnswerComposerPhrases::get($language, 'rejected_during_validation', [
                    ':count' => (string) $rejectedCount,
                ])]
                : [],
            uncertainty: $message,
            conflicts: [],
            language: $language,
            researchMetadata: [
                'query' => $query,
                'research_intent' => $plan->researchIntent,
                'agricultural_domain' => $plan->agriculturalDomain,
                'failure_reason' => $reason,
                'internet_first' => $plan->isInternetFirst(),
                'evidence_sufficient' => false,
            ] + $lifecycle,
            observability: [
                'usable_evidence_count' => 0,
                'claims_generated' => 0,
                'citations_mapped' => 0,
                'conflicts_detected' => 0,
                'independent_search' => false,
                'validation_bypassed' => false,
                'failure_reason' => $reason,
            ] + $this->phase5QuestionClaimMatrix($plan, $validationReport, [])
              + $lifecycle,
        );
    }
}
