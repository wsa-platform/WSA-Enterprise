<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceModality;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;

/**
 * FAOSTAT may support a quantitative claim. It must not support mechanism/causal/disease claims.
 * Partial support is never promoted to full support. No implicit unit conversion.
 */
final class FaoStatClaimSupportAssessor
{
    public function __construct(
        private FaoStatObservationRelevanceGate $relevanceGate,
    ) {}

    /**
     * @return array{relationship: string, confidence: float, factors: array<string, mixed>}
     */
    public function assess(KnowledgeQueryPlan $plan, ScientificSearchResult $result): array
    {
        $gate = $this->relevanceGate->assess($plan, $result);
        $observation = is_array($result->rawMetadata['faostat'] ?? null) ? $result->rawMetadata['faostat'] : [];
        $semantics = FaoStatClaimSemantics::inspect($plan);

        $factors = [
            'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
            'not_literature' => true,
            'faostat_relevance' => $gate['decision'],
            'faostat_relevance_reason' => $gate['reason'],
            'faostat_claim_kind' => $semantics['kind'],
            'query_element_code' => $observation['query_element_code'] ?? ($result->relevanceMetadata['query_element_code'] ?? null),
            'response_element_code' => $observation['response_element_code'] ?? ($result->relevanceMetadata['response_element_code'] ?? null),
            'evidence_modality' => ScientificEvidenceModality::DIRECT_STATISTICAL,
            'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            'observation_year' => $observation['year'] ?? null,
            'publication_year_ignored' => $result->publicationYear,
        ];

        $decision = $this->decide($plan, $result, $gate, $observation, $semantics);
        $factors['faostat_support_state'] = $decision['state'];
        $factors['faostat_support_reason'] = $decision['reason'];

        return [
            'relationship' => $decision['relationship'],
            'confidence' => $decision['confidence'],
            'factors' => $factors,
        ];
    }

    /**
     * @param  array{decision: string, relevant: bool, reason: string}  $gate
     * @param  array<string, mixed>  $observation
     * @param  array{kind: string, years: list<string>, unit: ?string, area: ?string, item: ?string, element: ?string}  $semantics
     * @return array{state: string, relationship: string, confidence: float, reason: string}
     */
    private function decide(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        array $gate,
        array $observation,
        array $semantics,
    ): array {
        if ($result->sourceKey !== 'fao_stat') {
            return $this->state(FaoStatSupportState::NOT_APPLICABLE, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'not_faostat');
        }

        if ($observation === [] || trim((string) ($observation['value'] ?? '')) === '') {
            return $this->state(FaoStatSupportState::UNVERIFIED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'observation_unavailable');
        }

        if ($semantics['kind'] === FaoStatClaimSemantics::CAUSAL) {
            return $this->state(FaoStatSupportState::NOT_APPLICABLE, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'causal_claim_not_statistical');
        }
        if ($semantics['kind'] === FaoStatClaimSemantics::RECOMMENDATION) {
            return $this->state(FaoStatSupportState::NOT_APPLICABLE, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'recommendation_claim_not_statistical');
        }

        $domainMismatch = $this->domainMismatch($plan, $observation);
        if ($domainMismatch !== null) {
            return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, $domainMismatch);
        }

        $itemMismatch = $this->labelMismatch($semantics['item'], $observation, ['item', 'item_code']);
        if ($itemMismatch) {
            return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'item_mismatch');
        }

        $areaMismatch = $this->labelMismatch($semantics['area'], $observation, ['area', 'area_code']);
        if ($areaMismatch) {
            return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'area_mismatch');
        }

        $elementMismatch = $this->elementMismatch($plan, $observation, $semantics['element']);
        if ($elementMismatch) {
            return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'element_mismatch');
        }

        $unitMismatch = $this->unitMismatch($semantics['unit'], (string) ($observation['unit'] ?? ''));
        if ($unitMismatch) {
            return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'unit_mismatch');
        }

        $obsYear = trim((string) ($observation['year'] ?? $observation['year_code'] ?? ''));
        $claimYears = $semantics['years'];
        if ($semantics['kind'] === FaoStatClaimSemantics::COMPARISON) {
            if (count($claimYears) >= 2 && ! in_array($obsYear, $claimYears, true)) {
                return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'year_mismatch');
            }
            if (count($claimYears) >= 2 && in_array($obsYear, $claimYears, true)) {
                return $this->state(
                    FaoStatSupportState::PARTIALLY_SUPPORTED,
                    ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                    0.41,
                    'missing_comparison_year',
                );
            }

            return $this->state(
                FaoStatSupportState::PARTIALLY_SUPPORTED,
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                0.41,
                'comparison_incomplete',
            );
        }

        if ($claimYears !== [] && $obsYear !== '' && ! in_array($obsYear, $claimYears, true)) {
            return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, 'year_mismatch');
        }

        if (! $gate['relevant']) {
            $reason = (string) $gate['reason'];
            if (str_starts_with($reason, 'dimension_mismatch:')) {
                return $this->state(FaoStatSupportState::NOT_SUPPORTED, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, $reason);
            }
            if ($semantics['kind'] !== FaoStatClaimSemantics::QUANTITATIVE || $reason !== 'claim_not_statistical') {
                return $this->state(FaoStatSupportState::NOT_APPLICABLE, ClaimEvidenceRelationship::NOT_VALIDATED, 0.0, $reason);
            }
        }

        return $this->state(
            FaoStatSupportState::SUPPORTED,
            ClaimEvidenceRelationship::SUPPORTED,
            0.82,
            'observation_matches_quantitative_claim',
        );
    }

    /**
     * @return array{state: string, relationship: string, confidence: float, reason: string}
     */
    private function state(string $state, string $relationship, float $confidence, string $reason): array
    {
        return [
            'state' => $state,
            'relationship' => $relationship,
            'confidence' => $confidence,
            'reason' => $reason,
        ];
    }

    /**
     * @param  array<string, mixed>  $observation
     */
    private function domainMismatch(KnowledgeQueryPlan $plan, array $observation): ?string
    {
        $need = FaoStatSearchOptionsResolver::fromPlan($plan);
        $wanted = strtoupper(trim((string) ($need['domain'] ?? '')));
        $got = strtoupper(trim((string) ($observation['domain_code'] ?? '')));
        if ($wanted === '' || $got === '' || $wanted === $got) {
            return null;
        }

        return 'domain_mismatch';
    }

    /**
     * @param  array<string, mixed>  $observation
     * @param  list<string>  $keys
     */
    private function labelMismatch(?string $wanted, array $observation, array $keys): bool
    {
        $wanted = trim((string) $wanted);
        if ($wanted === '') {
            return false;
        }
        $wantedNorm = mb_strtolower($wanted);
        $hadValue = false;
        foreach ($keys as $key) {
            $got = trim((string) ($observation[$key] ?? ''));
            if ($got === '') {
                continue;
            }
            $hadValue = true;
            if (mb_strtolower($got) === $wantedNorm) {
                return false;
            }
        }

        return $hadValue;
    }

    /**
     * @param  array<string, mixed>  $observation
     */
    private function elementMismatch(KnowledgeQueryPlan $plan, array $observation, ?string $claimElement): bool
    {
        $need = FaoStatSearchOptionsResolver::fromPlan($plan);
        $wantedCode = trim((string) ($need['element'] ?? ''));
        if ($wantedCode !== '') {
            foreach (['query_element_code', 'element_code', 'response_element_code'] as $key) {
                if (trim((string) ($observation[$key] ?? '')) === $wantedCode) {
                    return false;
                }
            }

            return true;
        }

        $claimElement = mb_strtolower(trim((string) $claimElement));
        if ($claimElement === '') {
            return false;
        }
        $got = mb_strtolower(trim((string) ($observation['element'] ?? '')));

        return $got !== '' && ! str_contains($got, $claimElement) && ! str_contains($claimElement, $got);
    }

    private function unitMismatch(?string $claimUnit, string $observationUnit): bool
    {
        if ($claimUnit === null || trim($claimUnit) === '' || trim($observationUnit) === '') {
            return false;
        }

        return FaoStatClaimSemantics::normalizeUnit($claimUnit)
            !== FaoStatClaimSemantics::normalizeUnit($observationUnit);
    }
}
