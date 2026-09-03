<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

use App\Services\Agriculture\Diagnosis\CandidateDiagnosis;
use App\Services\Agriculture\Diagnosis\DiagnosisConfidenceBand;
use App\Services\Agriculture\Diagnosis\DiagnosisEvidence;
use App\Services\Agriculture\Diagnosis\Knowledge\HeuristicDiagnosisKnowledgeSupport;
use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Stage 7 knowledge-base-backed DiagnosisKnowledgeSupportInterface adapter.
 * Extends heuristic support for fallback when KB matches are insufficient.
 * Independent of Research Agent stack.
 */
class KnowledgeBaseDiagnosisKnowledgeSupport extends HeuristicDiagnosisKnowledgeSupport
{
    public function __construct(
        private readonly DiagnosisKnowledgeRetrievalService $retrieval,
        private readonly DiagnosisKnowledgeObservabilityLogger $logger,
    ) {}

    /**
     * @param  list<VisionObservation>  $observations
     * @return list<CandidateDiagnosis>
     */
    public function suggestCandidates(PlantContext $context, array $observations): array
    {
        $query = new DiagnosisKnowledgeQuery(
            context: $context,
            observations: $observations,
            symptoms: $context->symptomsDescribed,
            cropKey: $context->cropType,
            commonName: $context->plantName,
            verifiedOnly: true,
        );

        $matches = $this->retrieval->retrieve($query);

        $this->logger->info('suggest_candidates', [
            'observation_count' => count($observations),
            'match_count' => count($matches),
            'has_context' => $context->hasUsefulContext(),
            'top_record_id' => $matches[0]->record->id ?? null,
            'top_safety' => $matches[0]->safetyStatus ?? null,
        ]);

        if ($matches === []) {
            return parent::suggestCandidates($context, $observations);
        }

        $candidates = [];
        foreach ($matches as $match) {
            if ($match->insufficientEvidence && $match->matchScore < 0.2) {
                continue;
            }

            $candidates[] = $this->toCandidate($match);
        }

        if ($candidates === []) {
            return parent::suggestCandidates($context, $observations);
        }

        // Merge heuristic fallbacks that do not duplicate KB ids, for broader differentials.
        $existingIds = array_map(static fn (CandidateDiagnosis $c): string => $c->id, $candidates);
        foreach (parent::suggestCandidates($context, $observations) as $fallback) {
            if (in_array($fallback->id, $existingIds, true)) {
                continue;
            }
            // Keep at most one heuristic supplement when KB already produced candidates.
            if (count($candidates) >= 5) {
                break;
            }
            $candidates[] = $fallback;
            break;
        }

        return $candidates;
    }

    private function toCandidate(DiagnosisKnowledgeMatchResult $match): CandidateDiagnosis
    {
        $record = $match->record;
        $score = DiagnosisConfidenceBand::clampImageAloneScore($match->matchScore);
        $band = DiagnosisKnowledgeConfidenceBand::toStage6Band($match->confidenceBand);

        $differentials = [];
        foreach ($record->differentials as $diff) {
            $prefix = match ($diff->relation) {
                DiagnosisDifferentialEntry::RELATION_CONTRADICTING => 'Contradicting: ',
                DiagnosisDifferentialEntry::RELATION_SUPPORTING => 'Supporting: ',
                default => '',
            };
            $differentials[] = $prefix.$diff->commonName.($diff->notes ? ' — '.$diff->notes : '');
        }

        $evidence = $match->evidence;
        foreach ($record->safetyNotices as $notice) {
            $evidence[] = new DiagnosisEvidence(
                kind: 'safety_notice',
                summary: $notice,
                observationIds: $match->matchedObservationIds,
                sourceLabel: 'diagnosis_knowledge_base',
                fromKnowledgeSupport: true,
            );
        }

        $scientificVerified = $record->scientificNameVerified
            && is_string($record->scientificName)
            && trim($record->scientificName) !== ''
            && $record->sources !== []
            && $score >= 0.45;

        $rationale = 'Knowledge-base match for '.$record->commonName
            .' ('.$record->causalClass.' / '.$record->category.').'
            .' Safety: '.$match->safetyStatus.'.';

        if ($match->supportingEvidence !== []) {
            $rationale .= ' Supporting: '.implode(' ', array_slice($match->supportingEvidence, 0, 2));
        }

        return new CandidateDiagnosis(
            id: $record->id,
            commonName: $record->commonName,
            scientificName: $scientificVerified ? $record->scientificName : null,
            scientificNameVerified: $scientificVerified,
            confidenceScore: $score,
            confidenceBand: $band,
            rank: 0,
            rationale: $rationale,
            evidence: $evidence,
            differentialNotes: $differentials,
            category: $record->category,
        );
    }
}
