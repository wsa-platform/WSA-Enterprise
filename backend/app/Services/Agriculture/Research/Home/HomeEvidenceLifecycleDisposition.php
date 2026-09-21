<?php

namespace App\Services\Agriculture\Research\Home;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Home/Free-Question evidence lifecycle disposition (Phase 10C / R6 disposition axis).
 *
 * Ownership (P2-C11 / R5–R6 / Phase-4 RC-E):
 * - THIS CLASS is the sole authoritative writer of Home lifecycle disposition labels.
 * - AnswerComposer may only *delegate* to classify()/unusedEvidenceReferences(); it must
 *   not reimplement disposition match rules.
 * - UniversalAnswerOrchestrator + AgriculturalResearchAgent call applyToSynthesis() as the
 *   post-compose authoritative merge into researchMetadata/observability.
 * - EvidenceVerificationLayer + EvidenceValidationExecutionReport.evidenceSufficient
 *   own scientific verification / library save eligibility (not Composer alone).
 * - R6 axes remain independent: directness ≠ claim_relationship ≠ disposition.
 *
 * Crop profile plans are out of scope: every public method returns a no-op
 * when {@see KnowledgeQueryPlan} maps to crop_profile intent.
 */
final class HomeEvidenceLifecycleDisposition
{
    public const NO_RESULTS_RETRIEVED = 'no_results_retrieved';

    public const RETRIEVED_BUT_REJECTED = 'retrieved_but_rejected';

    public const VALIDATED_NOT_COMPOSER_ELIGIBLE = 'validated_not_composer_eligible';

    public const COMPOSER_USED = 'composer_used';

    public const INSUFFICIENT_DIRECT_SUPPORTING_RETAINED = 'insufficient_direct_supporting_retained';

    public const INSUFFICIENT_FOR_SYNTHESIS = 'insufficient_for_synthesis';

    public const PROVIDER_FAILED = 'provider_failed';

    public const NEEDS_CLARIFICATION = 'needs_clarification';

    public function appliesTo(KnowledgeQueryPlan $plan): bool
    {
        return ! $plan->toAgriculturalResearchPlan()->isCropProfileIntent();
    }

    /**
     * @return array<string, mixed> Empty for Crop profile plans.
     */
    public function classify(
        KnowledgeQueryPlan $plan,
        ?EvidenceValidationExecutionReport $validationReport,
        int $composerEligibleCount,
        ?string $disposition = null,
    ): array {
        if (! $this->appliesTo($plan)) {
            return [];
        }

        $sourcesReceived = $validationReport?->sourcesReceived ?? 0;
        $validatedCount = $validationReport !== null ? count($validationReport->validatedEvidence) : 0;
        $rejectedCount = $validationReport?->rejectedCount ?? 0;
        $searchStatus = trim((string) ($validationReport?->searchSummary['search_status'] ?? ''));
        $failedSources = $validationReport?->searchSummary['failed_sources'] ?? [];
        $successfulSources = $validationReport?->searchSummary['successful_sources'] ?? [];

        if ($disposition === null) {
            $disposition = match (true) {
                is_array($failedSources) && $failedSources !== []
                    && (! is_array($successfulSources) || $successfulSources === [])
                    && $sourcesReceived < 1 && $validatedCount < 1 => self::PROVIDER_FAILED,
                $searchStatus === 'no_results'
                    || ($sourcesReceived < 1 && $validatedCount < 1 && $rejectedCount < 1) => self::NO_RESULTS_RETRIEVED,
                $validatedCount < 1 && $rejectedCount > 0 => self::RETRIEVED_BUT_REJECTED,
                $validatedCount > 0 && $composerEligibleCount < 1 => self::VALIDATED_NOT_COMPOSER_ELIGIBLE,
                $composerEligibleCount > 0 => self::COMPOSER_USED,
                default => self::INSUFFICIENT_FOR_SYNTHESIS,
            };
        }

        return [
            'evidence_lifecycle_disposition' => $disposition,
            // S1 contract: Home-only alias of disposition; does not replace Composer status.
            'lifecycle_status' => $disposition,
            'sources_received' => $sourcesReceived,
            'validated_evidence_count' => $validatedCount,
            'rejected_evidence_count' => $rejectedCount,
            'composer_eligible_count' => $composerEligibleCount,
            'search_status' => $searchStatus !== '' ? $searchStatus : null,
            // Phase-4: surface Phase-3 FAOSTAT taxonomy when present (null when absent).
            'faostat_pipeline_outcome' => $validationReport?->searchSummary['faostat_pipeline_outcome'] ?? null,
        ];
    }

    /**
     * Preserve identity for Home when evidence was retrieved but not used in the main answer.
     *
     * @return list<array<string, mixed>>
     */
    public function unusedEvidenceReferences(
        KnowledgeQueryPlan $plan,
        EvidenceValidationExecutionReport $validationReport,
    ): array {
        if (! $this->appliesTo($plan)) {
            return [];
        }

        $refs = [];
        foreach ([...$validationReport->validatedEvidence, ...$validationReport->rejectedEvidence] as $item) {
            if (! $item instanceof ScientificEvidenceItem) {
                continue;
            }
            $refs[] = [
                'evidence_id' => $item->evidenceId,
                'source_id' => $item->sourceId,
                'source_key' => $item->sourceKey,
                'publication_title' => $item->publicationTitle,
                'doi' => $item->doi,
                'url' => $item->url,
                'claim_relationship' => $item->claimRelationship,
                'validation_status' => $item->validationStatus,
                'evidence_directness' => $item->qualityFactors['evidence_directness']
                    ?? ($item->sourceAttribution['evidence_directness'] ?? null),
                'crop_or_entity' => $item->cropOrEntity,
                'used_in_answer' => false,
            ];
        }

        return $refs;
    }

    /**
     * Decorate a Stage 5 synthesis report with Home disposition metadata.
     * Crop profile plans are returned unchanged.
     */
    public function applyToSynthesis(
        KnowledgeQueryPlan $plan,
        EvidenceValidationExecutionReport $validationReport,
        AnswerSynthesisExecutionReport $synthesis,
    ): AnswerSynthesisExecutionReport {
        if (! $this->appliesTo($plan)) {
            return $synthesis;
        }

        $composerEligibleCount = $this->resolveComposerEligibleCount($synthesis);
        $disposition = $this->resolveDispositionOverride($synthesis, $composerEligibleCount);

        $lifecycle = $this->classify(
            $plan,
            $validationReport,
            $composerEligibleCount,
            $disposition,
        );

        $evidenceReferences = $synthesis->evidenceReferences;
        if ($evidenceReferences === [] && $composerEligibleCount < 1) {
            $evidenceReferences = $this->unusedEvidenceReferences($plan, $validationReport);
        }

        return new AnswerSynthesisExecutionReport(
            status: $synthesis->status,
            performed: $synthesis->performed,
            answer: $synthesis->answer,
            conciseSummary: $synthesis->conciseSummary,
            detailedExplanation: $synthesis->detailedExplanation,
            keyFindings: $synthesis->keyFindings,
            claims: $synthesis->claims,
            citations: $synthesis->citations,
            evidenceReferences: $evidenceReferences,
            confidence: $synthesis->confidence,
            limitations: $synthesis->limitations,
            uncertainty: $synthesis->uncertainty,
            conflicts: $synthesis->conflicts,
            language: $synthesis->language,
            researchMetadata: array_merge($synthesis->researchMetadata, $lifecycle),
            observability: array_merge($synthesis->observability, $lifecycle),
        );
    }

    private function resolveComposerEligibleCount(AnswerSynthesisExecutionReport $synthesis): int
    {
        $fromObservability = (int) ($synthesis->observability['usable_evidence_count'] ?? 0);
        if ($fromObservability > 0) {
            return $fromObservability;
        }

        if ($synthesis->citations !== []) {
            return count($synthesis->citations);
        }

        return 0;
    }

    private function resolveDispositionOverride(
        AnswerSynthesisExecutionReport $synthesis,
        int $composerEligibleCount,
    ): ?string {
        if ($synthesis->status === 'needs_clarification') {
            return self::NEEDS_CLARIFICATION;
        }

        if ($synthesis->status === 'no_search_results') {
            return self::NO_RESULTS_RETRIEVED;
        }

        $gate = (string) ($synthesis->researchMetadata['direct_evidence_gate'] ?? '');
        if ($gate === 'INSUFFICIENT_DIRECT_EVIDENCE') {
            return self::INSUFFICIENT_DIRECT_SUPPORTING_RETAINED;
        }

        if ($gate === 'PASSED' && $composerEligibleCount > 0) {
            return self::COMPOSER_USED;
        }

        return null;
    }
}
